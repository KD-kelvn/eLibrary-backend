<?php

namespace App\AuditResolvers;

use App\Traits\GetDeviceTrait;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

class ClientOsResorver implements Resolver
{
    use GetDeviceTrait;

    public static function resolve(Auditable $auditable)
    {

        $agent = new Agent;

        $agent->setUserAgent(Request::header('User-Agent'));
        $agent->setHttpHeaders(Request::header());

        return $agent->platform();
    }
}
