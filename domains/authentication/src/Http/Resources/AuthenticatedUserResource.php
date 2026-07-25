<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Request;
use Modules\Authorization\Http\Resources\NavigationMenuResource;
use Modules\Authorization\Http\Resources\RoleSummaryResource;
use Modules\Authorization\Services\AccessContextService;

class AuthenticatedUserResource extends UserResource
{
    public function toArray(Request $request): array
    {
        $access = app(AccessContextService::class)->forUser($this->resource);

        return [
            ...parent::toArray($request),
            'roles' => RoleSummaryResource::collection($access['roles']),
            'menus' => NavigationMenuResource::collection($access['menus']),
            'actions' => $access['actions']->pluck('code')->values(),
        ];
    }
}
