<?php

namespace Modules\Authentication\Services;

use Modules\Authentication\Enums\OneTimeTokenPurpose;
use Modules\Authentication\Enums\OneTimeTokenVia;
use Modules\Authentication\Models\User;
use Modules\Authentication\Notifications\OneTimePasswordNotification;

class OtpNotifier
{
    public function send(
        User $user,
        string $code,
        OneTimeTokenVia $via,
        OneTimeTokenPurpose $purpose = OneTimeTokenPurpose::Login,
    ): void {
        $user->notify(new OneTimePasswordNotification(
            code: $code,
            via: $via,
            expiryMinutes: (int) config('authentication.otp.expiry_minutes', 10),
            purpose: $purpose,
        ));
    }
}
