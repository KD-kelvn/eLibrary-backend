<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\LoginSlide;
use Modules\Settings\Services\LoginSlideService;

class LoginSlideSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'title' => 'Best forests to visit in North America',
                'category' => 'nature',
                'external_image_url' => 'https://images.unsplash.com/photo-1508193638397-1c4234db14d8?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1200&q=80',
                'sort_order' => 10,
            ],
            [
                'title' => 'Hawaii beaches review: better than you think',
                'category' => 'beach',
                'external_image_url' => 'https://images.unsplash.com/photo-1559494007-9f5847c49d94?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1200&q=80',
                'sort_order' => 20,
            ],
            [
                'title' => 'Mountains at night: 12 best locations to enjoy the view',
                'category' => 'nature',
                'external_image_url' => 'https://images.unsplash.com/photo-1608481337062-4093bf3ed404?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1200&q=80',
                'sort_order' => 30,
            ],
            [
                'title' => 'Aurora in Norway: when to visit for best experience',
                'category' => 'nature',
                'external_image_url' => 'https://images.unsplash.com/photo-1507272931001-fc06c17e4f43?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1200&q=80',
                'sort_order' => 40,
            ],
        ];

        foreach ($defaults as $slide) {
            LoginSlide::query()->firstOrCreate(
                ['title' => $slide['title']],
                [...$slide, 'is_active' => true],
            );
        }

        app(LoginSlideService::class)->forgetCache();
    }
}
