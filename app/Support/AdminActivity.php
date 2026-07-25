<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class AdminActivity
{
    public static function log(
        string $description,
        ?Model $subject = null,
        array $properties = [],
        string $logName = 'admin',
        ?string $event = null,
    ): void {
        $logger = activity($logName)->withProperties($properties);

        if ($event) {
            $logger->event($event);
        }

        if ($subject) {
            $logger->performedOn($subject);
        }

        if (auth()->check()) {
            $logger->causedBy(auth()->user());
        }

        $logger->log($description);
    }
}
