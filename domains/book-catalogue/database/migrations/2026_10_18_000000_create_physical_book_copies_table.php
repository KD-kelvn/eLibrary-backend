<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\BookBorrowing\Enums\BorrowingProcessCodeEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_catalog.physical_book_copies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('physical_book_id');
            $table->string('accession_number')->unique();
            $table->string('status', 20)->default('AVAILABLE');
            $table->auditableWithDeletes();
            $table->timestamps();
            $table->softDeletes();

            $table
                ->foreign('physical_book_id')
                ->references('id')
                ->on('book_catalog.physical_books')
                ->cascadeOnDelete();
            $table->index(['physical_book_id', 'status'], 'idx_physical_copy_status');
        });

        DB::table('book_catalog.physical_books')
            ->orderBy('id')
            ->chunkById(100, function ($physicalBooks): void {
                foreach ($physicalBooks as $physicalBook) {
                    $copies = [];
                    $count = max(0, (int) $physicalBook->copies);
                    $prefix = mb_substr((string) $physicalBook->code_no, 0, 220);

                    for ($number = 1; $number <= $count; $number++) {
                        $copies[] = [
                            'physical_book_id' => $physicalBook->id,
                            'accession_number' => sprintf('%s-%d-%08d', $prefix, $physicalBook->id, $number),
                            'status' => 'AVAILABLE',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if ($copies !== []) {
                        DB::table('book_catalog.physical_book_copies')->insert($copies);
                    }
                }
            });

        Schema::table('book_borrowing.borrowing_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('physical_book_copy_id')->nullable()->after('book_type_id');
            $table
                ->foreign('physical_book_copy_id')
                ->references('id')
                ->on('book_catalog.physical_book_copies')
                ->nullOnDelete();
            $table->index('physical_book_copy_id', 'idx_brrw_req_physical_copy');
        });

        $activeRequests = DB::table('book_borrowing.borrowing_requests as requests')
            ->joinSub(
                DB::table('book_borrowing.borrowing_progress')
                    ->selectRaw('borrowing_request_id, MAX(id) as latest_progress_id')
                    ->groupBy('borrowing_request_id'),
                'latest',
                'latest.borrowing_request_id',
                '=',
                'requests.id',
            )
            ->join('book_borrowing.borrowing_progress as progress', 'progress.id', '=', 'latest.latest_progress_id')
            ->where('progress.status_code', BorrowingProcessCodeEnum::Approved->value)
            ->where('requests.book_type', 'Modules\\BookCatalogue\\Models\\PhysicalBook')
            ->select(['requests.id', 'requests.book_type_id'])
            ->orderBy('requests.id')
            ->get();

        foreach ($activeRequests as $request) {
            $copy = DB::table('book_catalog.physical_book_copies')
                ->where('physical_book_id', $request->book_type_id)
                ->where('status', 'AVAILABLE')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();

            if (! $copy) {
                $physicalBook = DB::table('book_catalog.physical_books')
                    ->where('id', $request->book_type_id)
                    ->first();

                if (! $physicalBook) {
                    throw new RuntimeException(
                        "Cannot assign a physical copy to active borrowing request {$request->id}: its inventory record is missing.",
                    );
                }

                $number = (int) DB::table('book_catalog.physical_book_copies')
                    ->where('physical_book_id', $physicalBook->id)
                    ->count() + 1;
                $copyId = DB::table('book_catalog.physical_book_copies')->insertGetId([
                    'physical_book_id' => $physicalBook->id,
                    'accession_number' => sprintf(
                        '%s-%d-%08d',
                        mb_substr((string) $physicalBook->code_no, 0, 220),
                        $physicalBook->id,
                        $number,
                    ),
                    'status' => 'ON_LOAN',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('book_catalog.physical_books')
                    ->where('id', $physicalBook->id)
                    ->update(['copies' => (int) $physicalBook->copies + 1]);
                DB::table('book_borrowing.borrowing_requests')
                    ->where('id', $request->id)
                    ->update(['physical_book_copy_id' => $copyId]);

                continue;
            }

            DB::table('book_catalog.physical_book_copies')
                ->where('id', $copy->id)
                ->update(['status' => 'ON_LOAN', 'updated_at' => now()]);
            DB::table('book_borrowing.borrowing_requests')
                ->where('id', $request->id)
                ->update(['physical_book_copy_id' => $copy->id]);
        }
    }

    public function down(): void
    {
        Schema::table('book_borrowing.borrowing_requests', function (Blueprint $table) {
            $table->dropForeign(['physical_book_copy_id']);
            $table->dropIndex('idx_brrw_req_physical_copy');
            $table->dropColumn('physical_book_copy_id');
        });

        Schema::dropIfExists('book_catalog.physical_book_copies');
    }
};
