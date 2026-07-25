<?php

namespace Modules\Settings\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Models\Branding;

class BrandingService
{
    public function current(): Branding
    {
        $branding = Branding::query()->with('media')->first();

        if ($branding) {
            return $branding;
        }

        return Branding::query()->create(Branding::DEFAULTS)->load('media');
    }

    /**
     * @param  array{
     *     app_name?: string,
     *     primary_color?: string,
     *     secondary_color?: string,
     *     tertiary_color?: string,
     *     logo?: UploadedFile|null,
     *     logo_dark?: UploadedFile|null,
     *     favicon?: UploadedFile|null,
     *     remove_logo?: bool,
     *     remove_logo_dark?: bool,
     *     remove_favicon?: bool,
     * }  $data
     */
    public function update(array $data): Branding
    {
        return DB::transaction(function () use ($data) {
            $branding = $this->current();

            $branding->fill(array_filter([
                'app_name' => $data['app_name'] ?? null,
                'primary_color' => $data['primary_color'] ?? null,
                'secondary_color' => $data['secondary_color'] ?? null,
                'tertiary_color' => $data['tertiary_color'] ?? null,
            ], fn ($value) => $value !== null));

            $branding->save();

            $this->syncMedia($branding, 'logo', $data['logo'] ?? null, (bool) ($data['remove_logo'] ?? false));
            $this->syncMedia($branding, 'logo_dark', $data['logo_dark'] ?? null, (bool) ($data['remove_logo_dark'] ?? false));
            $this->syncMedia($branding, 'favicon', $data['favicon'] ?? null, (bool) ($data['remove_favicon'] ?? false));

            return $branding->fresh(['media']);
        });
    }

    private function syncMedia(
        Branding $branding,
        string $collection,
        ?UploadedFile $file,
        bool $remove,
    ): void {
        if ($remove) {
            $branding->clearMediaCollection($collection);
        }

        if ($file instanceof UploadedFile) {
            $branding
                ->addMedia($file)
                ->toMediaCollection($collection);
        }
    }
}
