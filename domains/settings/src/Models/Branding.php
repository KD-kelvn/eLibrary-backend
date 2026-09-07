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
        'primary_color' => '#F05454',
        'secondary_color' => '#45CFDD',
        'tertiary_color' => '#4ECCA3',
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
