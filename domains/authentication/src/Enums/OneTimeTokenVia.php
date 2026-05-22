<?php

namespace Modules\Authentication\Enums;

enum OneTimeTokenVia: string
{
    case Email = 'email';
    case Phone = 'phone';
}
