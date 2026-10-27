<?php

namespace Modules\Notifications\Providers;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Notifications\Channels\SmsChannel;
use Modules\Notifications\Contracts\SmsProvider;
use Modules\Notifications\Listeners\LogNotificationDelivery;
use Modules\Notifications\Models\DatabaseNotification;
use Modules\Notifications\Sms\SmsProviderManager;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Illuminate\Notifications\DatabaseNotification::class,
            DatabaseNotification::class,
        );

        $this->app->singleton(SmsProviderManager::class);
        $this->app->bind(SmsProvider::class, fn ($app) => $app->make(SmsProviderManager::class)->driver());
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Notification::resolved(function (ChannelManager $service): void {
            $service->extend('sms', fn ($app) => $app->make(SmsChannel::class));
            // Alias kept so older queued jobs / tests still resolve.
            $service->extend('log_sms', fn ($app) => $app->make(SmsChannel::class));
        });

        Event::listen(
            NotificationSent::class,
            LogNotificationDelivery::class,
        );

        Route::middleware('api')
            ->prefix('api/notifications')
            ->group(__DIR__.'/../../api-routes/notifications-routes.php');
    }
}
