<?php

namespace Modules\Authentication\Enums;

enum OneTimeTokenPurpose: string
{
    case Login = 'login';
    case PasswordReset = 'password_reset';
    case AccountUnlock = 'account_unlock';
}
