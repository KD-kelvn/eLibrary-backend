<?php

namespace Modules\Authentication\Services;

use App\Support\AdminActivity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Academic\Models\Department;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Programme;
use Modules\Authentication\Exceptions\AuthenticationException;
use Modules\Authentication\Models\User;
use Modules\Authentication\Models\UserImport;
use Modules\Authentication\Models\UserImportRow;
use Modules\Authentication\Models\UserProfile;
use Modules\Authentication\Repositories\UserProfileRepository;
use Modules\Authentication\Repositories\UserRepository;
use Modules\Authentication\Support\PhoneNumberNormalizer;
use Modules\Authorization\Enums\RoleStatusEnum;
use Modules\Authorization\Models\Role;
use Modules\Authorization\Models\UserRole;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * V1-022 / FR-USR-03/04 — template, preview, commit, idempotent re-import.
 */
class UserImportService
{
    private const STUDENT_HEADERS = [
        'username',
        'email',
        'phone',
        'first_name',
        'middle_name',
        'last_name',
        'faculty_code',
        'department_code',
        'programme_code',
        'year_of_study',
    ];

    private const STAFF_HEADERS = [
        'username',
        'email',
        'phone',
        'first_name',
        'middle_name',
        'last_name',
        'role_code',
        'faculty_code',
        'department_code',
    ];

    public function __construct(
        private readonly UserRepository $users,
        private readonly UserProfileRepository $profiles,
        private readonly PhoneNumberNormalizer $phones,
    ) {}

    public function downloadTemplate(string $type, string $format = 'xlsx'): StreamedResponse
    {
        $type = $this->assertType($type);
        $format = in_array($format, ['csv', 'xlsx'], true) ? $format : 'xlsx';
        $headers = $type === 'student' ? self::STUDENT_HEADERS : self::STAFF_HEADERS;
        $filename = "user-import-{$type}.{$format}";

        $path = storage_path('app/tmp/'.$filename);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = SimpleExcelWriter::create($path, $format);
        $writer->addHeader($headers);
        $writer->close();

        return response()->streamDownload(function () use ($path) {
            readfile($path);
            @unlink($path);
        }, $filename, [
            'Content-Type' => $format === 'csv'
                ? 'text/csv; charset=UTF-8'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{import_id: int, rows: list<array<string, mixed>>, errors: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function preview(UploadedFile $file, string $type, int $uploadedBy): array
    {
        $type = $this->assertType($type);
        $this->assertSpreadsheet($file);

        $stored = $file->storeAs(
            'user-imports/'.$uploadedBy,
            Str::uuid()->toString().'-'.$file->getClientOriginalName(),
            'local',
        );

        $absolute = Storage::disk('local')->path($stored);
        $reader = SimpleExcelReader::create($absolute)
            ->trimHeaderRow()
            ->headersToSnakeCase();

        $import = UserImport::query()->create([
            'type' => $type,
            'status' => 'preview',
            'original_filename' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'storage_path' => $stored,
            'uploaded_by' => $uploadedBy,
            'created_by' => $uploadedBy,
        ]);

        $rowNumber = 1; // header is row 1; data starts at 2
        $previewRows = [];
        $errorRows = [];
        $summary = ['valid' => 0, 'invalid' => 0];

        foreach ($reader->getRows() as $raw) {
            $rowNumber++;
            $normalized = $this->normalizeRow(is_array($raw) ? $raw : []);
            [$payload, $errors] = $this->validateRow($normalized, $type);

            $status = $errors === [] ? 'valid' : 'invalid';
            $summary[$status]++;

            UserImportRow::query()->create([
                'user_import_id' => $import->id,
                'row_number' => $rowNumber,
                'payload' => $payload,
                'status' => $status,
                'errors' => $errors === [] ? null : $errors,
            ]);

            $previewRows[] = [
                'row_number' => $rowNumber,
                'status' => $status,
                'data' => $payload,
                'errors' => $errors,
            ];

            if ($errors !== []) {
                $errorRows[] = [
                    'row_number' => $rowNumber,
                    'errors' => $errors,
                ];
            }
        }

        $import->update(['summary' => $summary]);

        return [
            'import_id' => $import->id,
            'rows' => $previewRows,
            'errors' => $errorRows,
            'summary' => $summary,
        ];
    }

    /**
     * @return array{import_id: int, created: int, updated: int, unchanged: int, failed: int}
     */
    public function commit(int $importId, int $actorId): array
    {
        $import = UserImport::query()->with('rows')->find($importId)
            ?? throw AuthenticationException::unprocessable('Import not found.');

        if ($import->isCommitted()) {
            return $this->commitResult($import);
        }

        $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => 0];

        DB::transaction(function () use ($import, $actorId, &$counts) {
            foreach ($import->rows as $row) {
                if ($row->status === 'invalid') {
                    $counts['failed']++;

                    continue;
                }

                try {
                    $outcome = $this->commitRow($import, $row, $actorId);
                    $row->update([
                        'status' => $outcome['status'],
                        'matched_user_id' => $outcome['user_id'],
                    ]);
                    $counts[$outcome['status']]++;
                } catch (\Throwable $exception) {
                    $row->update([
                        'status' => 'invalid',
                        'errors' => array_values(array_unique(array_filter([
                            ...($row->errors ?? []),
                            $exception->getMessage(),
                        ]))),
                    ]);
                    $counts['failed']++;
                }
            }

            $import->update([
                'status' => 'committed',
                'committed_at' => now(),
                'summary' => [
                    ...($import->summary ?? []),
                    ...$counts,
                ],
                'updated_by' => $actorId,
            ]);

            AdminActivity::log(
                'User import committed',
                $import,
                $counts,
                event: 'user.imported',
            );
        });

        return $this->commitResult($import->fresh());
    }

    public function downloadErrors(int $importId): StreamedResponse
    {
        $import = UserImport::query()->find($importId)
            ?? throw AuthenticationException::unprocessable('Import not found.');

        $rows = UserImportRow::query()
            ->where('user_import_id', $import->id)
            ->where(function ($query) {
                $query->where('status', 'invalid')
                    ->orWhereNotNull('errors');
            })
            ->orderBy('row_number')
            ->get();

        $filename = "user-import-{$import->id}-errors.csv";
        $path = storage_path('app/tmp/'.$filename);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = SimpleExcelWriter::create($path, 'csv');
        $writer->addHeader(['row_number', 'errors', 'username', 'email']);
        foreach ($rows as $row) {
            $payload = $row->payload ?? [];
            $writer->addRow([
                'row_number' => $row->row_number,
                'errors' => implode('; ', $row->errors ?? []),
                'username' => $payload['username'] ?? '',
                'email' => $payload['email'] ?? '',
            ]);
        }
        $writer->close();

        return response()->streamDownload(function () use ($path) {
            readfile($path);
            @unlink($path);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{import_id: int, created: int, updated: int, unchanged: int, failed: int}
     */
    private function commitResult(UserImport $import): array
    {
        $summary = $import->summary ?? [];

        return [
            'import_id' => $import->id,
            'created' => (int) ($summary['created'] ?? 0),
            'updated' => (int) ($summary['updated'] ?? 0),
            'unchanged' => (int) ($summary['unchanged'] ?? 0),
            'failed' => (int) ($summary['failed'] ?? 0),
        ];
    }

    /**
     * @return array{status: string, user_id: int}
     */
    private function commitRow(UserImport $import, UserImportRow $row, int $actorId): array
    {
        $data = $row->payload;
        $username = (string) $data['username'];
        $existing = User::query()
            ->with('profile')
            ->whereRaw('lower(username) = lower(?)', [$username])
            ->first();

        $roleCode = $import->type === 'student'
            ? 'student'
            : (string) ($data['role_code'] ?? '');

        $role = Role::query()
            ->where('code', $roleCode)
            ->where('status', RoleStatusEnum::Active->value)
            ->first()
            ?? throw AuthenticationException::unprocessable("Unknown role [{$roleCode}].");

        if ($existing) {
            $snapshot = $this->snapshot($existing);
            $desired = $this->desiredSnapshot($data, $roleCode);

            if ($snapshot === $desired) {
                return ['status' => 'unchanged', 'user_id' => $existing->id];
            }

            $this->users->update($existing, [
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ]);

            $this->profiles->updateOrCreateForUser($existing->id, [
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'fullname' => $this->composeName($data),
                'faculty_id' => $data['faculty_id'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'programme_id' => $data['programme_id'] ?? null,
                'year_of_study' => $roleCode === 'student' ? ($data['year_of_study'] ?? null) : null,
                'source' => 'import',
            ]);

            $this->ensureRole($existing, $role, $actorId);

            return ['status' => 'updated', 'user_id' => $existing->id];
        }

        $user = $this->users->create([
            'username' => $username,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Str::password(20),
            'is_blocked' => false,
            'is_active' => true,
            'locale' => 'en',
        ]);

        $this->profiles->createForUser($user->id, [
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'fullname' => $this->composeName($data),
            'faculty_id' => $data['faculty_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'programme_id' => $data['programme_id'] ?? null,
            'year_of_study' => $roleCode === 'student' ? ($data['year_of_study'] ?? null) : null,
            'source' => 'import',
        ]);

        $this->ensureRole($user, $role, $actorId);

        return ['status' => 'created', 'user_id' => $user->id];
    }

    private function ensureRole(User $user, Role $role, int $actorId): void
    {
        $has = UserRole::query()
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->active()
            ->exists();

        if ($has) {
            return;
        }

        UserRole::query()->create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'assigned_by' => $actorId,
            'assigned_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalizeRow(array $raw): array
    {
        $out = [];
        foreach ($raw as $key => $value) {
            $k = Str::snake(trim((string) $key));
            $out[$k] = is_string($value) ? trim($value) : $value;
            if ($out[$k] === '') {
                $out[$k] = null;
            }
        }

        if (isset($out['phone'])) {
            $out['phone'] = $this->phones->normalize(is_string($out['phone']) ? $out['phone'] : null);
        }

        if (isset($out['email']) && is_string($out['email'])) {
            $out['email'] = strtolower($out['email']);
        }

        if (isset($out['username']) && is_string($out['username'])) {
            $out['username'] = trim($out['username']);
        }

        if (isset($out['role_code']) && is_string($out['role_code'])) {
            $out['role_code'] = strtolower($out['role_code']);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function validateRow(array $row, string $type): array
    {
        $errors = [];
        $payload = $row;

        foreach (['username', 'email', 'first_name', 'last_name'] as $required) {
            if (! filled($row[$required] ?? null)) {
                $errors[] = "{$required} is required.";
            }
        }

        if (filled($row['email'] ?? null) && ! filter_var((string) $row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email is invalid.';
        }

        if (filled($row['phone'] ?? null) && ! $this->phones->isE164((string) $row['phone'])) {
            $errors[] = 'phone must be a valid E.164 number.';
        }

        if ($type === 'staff') {
            $roleCode = (string) ($row['role_code'] ?? '');
            if (! in_array($roleCode, ['faculty', 'librarian'], true)) {
                $errors[] = 'role_code must be faculty or librarian.';
            }
        } else {
            $payload['role_code'] = 'student';
            if (isset($row['year_of_study']) && $row['year_of_study'] !== null) {
                $year = (int) $row['year_of_study'];
                if ($year < 1 || $year > 10) {
                    $errors[] = 'year_of_study must be between 1 and 10.';
                } else {
                    $payload['year_of_study'] = $year;
                }
            }
        }

        [$payload, $academicErrors] = $this->resolveAcademicCodes($payload, $type);
        $errors = [...$errors, ...$academicErrors];

        // Duplicate credentials within DB for creates will be handled at commit;
        // still flag email collision with a different username.
        if (filled($payload['username'] ?? null) && filled($payload['email'] ?? null)) {
            $other = User::query()
                ->whereRaw('lower(email) = ?', [strtolower((string) $payload['email'])])
                ->whereRaw('lower(username) <> lower(?)', [(string) $payload['username']])
                ->exists();
            if ($other) {
                $errors[] = 'email already belongs to another user.';
            }
        }

        if (filled($payload['phone'] ?? null) && filled($payload['username'] ?? null)) {
            $otherPhone = User::query()
                ->where('phone', $payload['phone'])
                ->whereRaw('lower(username) <> lower(?)', [(string) $payload['username']])
                ->exists();
            if ($otherPhone) {
                $errors[] = 'phone already belongs to another user.';
            }
        }

        return [$payload, $errors];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function resolveAcademicCodes(array $payload, string $type): array
    {
        $errors = [];

        if (filled($payload['faculty_code'] ?? null)) {
            $faculty = Faculty::query()
                ->whereRaw('lower(code) = lower(?)', [(string) $payload['faculty_code']])
                ->where('is_active', true)
                ->first();
            if (! $faculty) {
                $errors[] = 'unknown faculty_code.';
            } else {
                $payload['faculty_id'] = $faculty->id;
            }
        }

        if (filled($payload['department_code'] ?? null)) {
            $department = Department::query()
                ->whereRaw('lower(code) = lower(?)', [(string) $payload['department_code']])
                ->where('is_active', true)
                ->first();
            if (! $department) {
                $errors[] = 'unknown department_code.';
            } else {
                $payload['department_id'] = $department->id;
                $payload['faculty_id'] = $payload['faculty_id'] ?? $department->faculty_id;
            }
        }

        if ($type === 'student' && filled($payload['programme_code'] ?? null)) {
            $programme = Programme::query()
                ->with('department')
                ->whereRaw('lower(code) = lower(?)', [(string) $payload['programme_code']])
                ->where('is_active', true)
                ->first();
            if (! $programme) {
                $errors[] = 'unknown programme_code.';
            } else {
                $payload['programme_id'] = $programme->id;
                $payload['department_id'] = $payload['department_id'] ?? $programme->department_id;
                $payload['faculty_id'] = $payload['faculty_id'] ?? $programme->department?->faculty_id;
            }
        }

        return [$payload, $errors];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(User $user): array
    {
        /** @var UserProfile|null $profile */
        $profile = $user->profile;

        return [
            'email' => strtolower((string) $user->email),
            'phone' => $user->phone,
            'first_name' => $profile?->first_name,
            'middle_name' => $profile?->middle_name,
            'last_name' => $profile?->last_name,
            'faculty_id' => $profile?->faculty_id,
            'department_id' => $profile?->department_id,
            'programme_id' => $profile?->programme_id,
            'year_of_study' => $profile?->year_of_study,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function desiredSnapshot(array $data, string $roleCode): array
    {
        return [
            'email' => strtolower((string) $data['email']),
            'phone' => $data['phone'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'faculty_id' => $data['faculty_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'programme_id' => $data['programme_id'] ?? null,
            'year_of_study' => $roleCode === 'student' ? ($data['year_of_study'] ?? null) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function composeName(array $data): string
    {
        return trim(implode(' ', array_filter([
            $data['first_name'] ?? null,
            $data['middle_name'] ?? null,
            $data['last_name'] ?? null,
        ], fn ($part) => filled($part))));
    }

    private function assertType(string $type): string
    {
        $type = strtolower(trim($type));
        if (! in_array($type, ['student', 'staff'], true)) {
            throw AuthenticationException::unprocessable('Import type must be student or staff.');
        }

        return $type;
    }

    private function assertSpreadsheet(UploadedFile $file): void
    {
        if (! $file->isValid()) {
            throw AuthenticationException::unprocessable('Invalid upload.');
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
            throw AuthenticationException::unprocessable('File must be CSV or XLSX.');
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            throw AuthenticationException::unprocessable('File must be at most 10 MB.');
        }
    }
}
