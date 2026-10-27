<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Authentication\Models\User;
use Modules\Settings\Services\SettingsService;

class StoreUserPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = User::query()->find($this->route('user'));

        return $user && ($this->user()?->can('update', $user) ?? false);
    }

    public function rules(): array
    {
        $maxMb = (int) (app(SettingsService::class)->get('files.image_max_mb') ?? 2);
        $types = collect(app(SettingsService::class)->get('files.image_types') ?? ['jpeg', 'png', 'webp'])
            ->map(fn ($type) => strtolower((string) $type))
            ->flatMap(fn (string $type) => match ($type) {
                'jpeg' => ['jpeg', 'jpg'],
                default => [$type],
            })
            ->unique()
            ->implode(',');

        return [
            'photo' => [
                'required',
                'file',
                'mimes:'.($types !== '' ? $types : 'jpeg,jpg,png,webp'),
                'max:'.(max(1, $maxMb) * 1024),
            ],
        ];
    }
}
