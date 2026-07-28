<?php

namespace Modules\Settings\Models;

use App\Models\BaseModelWithAudits;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LoginSlide extends BaseModelWithAudits implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'settings.login_slides';

    protected $fillable = [
        'title',
        'category',
        'external_image_url',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function imageUrl(): ?string
    {
        $uploaded = $this->getFirstMediaUrl('image');

        if ($uploaded) {
            return $uploaded;
        }

        return $this->external_image_url ?: null;
    }
}
