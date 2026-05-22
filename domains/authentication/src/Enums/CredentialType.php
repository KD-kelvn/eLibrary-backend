<?php

namespace Modules\Authentication\Enums;

enum CredentialType: string
{
    case Username = 'username';
    case Email = 'email';
    case Phone = 'phone';
}
