<?php

namespace Modules\Notifications\Providers;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Authentication\Notifications\Channels\LogSmsChannel;
use Modules\Notifications\Listeners\LogNotificationDelivery;
use Modules\Notifications\Models\DatabaseNotification;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Illuminate\Notifications\DatabaseNotification::class,
            DatabaseNotification::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        Notification::resolved(function (ChannelManager $service): void {
            $service->extend('log_sms', fn () => new LogSmsChannel);
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
