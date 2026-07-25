<?php

namespace Modules\Settings\Models;

use App\Models\BaseModelWithAudits;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Branding extends BaseModelWithAudits implements HasMedia
{
    use InteractsWithMedia;

    public const DEFAULTS = [
        'app_name' => 'eLibrary',
        'primary_color' => '#863BFF',
        'secondary_color' => '#0EA5E9',
        'tertiary_color' => '#14B8A6',
    ];

    protected $table = 'settings.brandings';

    protected $fillable = [
        'app_name',
        'primary_color',
        'secondary_color',
        'tertiary_color',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('logo_dark')->singleFile();
        $this->addMediaCollection('favicon')->singleFile();
    }
}
