<?php

namespace App\Http\Resources\Models;
use App\Models\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [

        ];
    }
}
