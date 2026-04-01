<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

trait GetDeviceTrait
{
    protected Agent $agent;

    public function setAgent(Request $request): void
    {
        $this->agent = new Agent;

        $this->agent->setUserAgent($request->header('User-Agent'));
        $this->agent->setHttpHeaders($request->header());
    }

    public function getBrowser(): bool|string
    {
        return $this->agent->browser();
    }

    public function getDevice(): bool|string
    {
        return $this->agent->device();
    }

    public function getOs(): bool|string
    {
        return $this->agent->platform();
    }

    public function getDeviceDetails(): array
    {
        return [
            'device' => $this->getDevice(),
            'os' => $this->getOs(),
            'browser' => $this->getBrowser(),
        ];
    }
}
