<?php

namespace Modules\Settings\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Models\LoginSlide;

class LoginSlideService
{
    public const CACHE_KEY = 'settings.login_slides.active';

    public const CACHE_TTL_SECONDS = 60 * 60 * 24;

    /**
     * Active slides for the public login carousel (cached).
     *
     * @return list<array{id: int, image: string|null, title: string, category: string, sortOrder: int}>
     */
    public function activeCached(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return $this->active()
                ->map(fn (LoginSlide $slide) => [
                    'id' => $slide->id,
                    'image' => $slide->imageUrl(),
                    'title' => $slide->title,
                    'category' => $slide->category,
                    'sortOrder' => $slide->sort_order,
                ])
                ->values()
                ->all();
        });
    }

    /**
     * @return Collection<int, LoginSlide>
     */
    public function active(): Collection
    {
        return LoginSlide::query()
            ->with('media')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (LoginSlide $slide) => filled($slide->imageUrl()))
            ->values();
    }

    /**
     * @return Collection<int, LoginSlide>
     */
    public function all(): Collection
    {
        return LoginSlide::query()
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array{
     *     title: string,
     *     category: string,
     *     external_image_url?: string|null,
     *     sort_order?: int,
     *     is_active?: bool,
     *     image?: UploadedFile|null,
     * }  $data
     */
    public function create(array $data): LoginSlide
    {
        return DB::transaction(function () use ($data) {
            $slide = LoginSlide::query()->create([
                'title' => $data['title'],
                'category' => $data['category'],
                'external_image_url' => $data['external_image_url'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (($data['image'] ?? null) instanceof UploadedFile) {
                $slide->addMedia($data['image'])->toMediaCollection('image');
                $slide->external_image_url = null;
                $slide->save();
            }

            $this->forgetCache();

            return $slide->fresh(['media']);
        });
    }

    /**
     * @param  array{
     *     title?: string,
     *     category?: string,
     *     external_image_url?: string|null,
     *     sort_order?: int,
     *     is_active?: bool,
     *     image?: UploadedFile|null,
     *     remove_image?: bool,
     * }  $data
     */
    public function update(LoginSlide $slide, array $data): LoginSlide
    {
        return DB::transaction(function () use ($slide, $data) {
            $payload = [];

            foreach (['title', 'category', 'external_image_url', 'sort_order', 'is_active'] as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if ($payload !== []) {
                $slide->fill($payload);
                $slide->save();
            }

            if (! empty($data['remove_image'])) {
                $slide->clearMediaCollection('image');
            }

            if (($data['image'] ?? null) instanceof UploadedFile) {
                $slide->clearMediaCollection('image');
                $slide->addMedia($data['image'])->toMediaCollection('image');
                $slide->external_image_url = null;
                $slide->save();
            }

            $this->forgetCache();

            return $slide->fresh(['media']);
        });
    }

    public function delete(LoginSlide $slide): void
    {
        DB::transaction(function () use ($slide) {
            $slide->clearMediaCollection('image');
            $slide->delete();
            $this->forgetCache();
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
