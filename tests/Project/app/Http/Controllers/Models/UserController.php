<?php

namespace App\Http\Controllers\Models;

use App\Http\Controllers\Controller;
use App\Http\Requests\Models\UserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function index(){
        $models = User::query()->paginate($this->perPage ?? 10);
        return UserResource::collection($models);
    }

    public function store(UserRequest $request)
    {
        $model = User::create($request->validated());
        return UserResource::make($model);
    }

    public function update(UserRequest $request, User $user)
    {
        $user->update($request->validated());
        return UserResource::make($user);
    }

    public function delete(User $user){
        $user->delete();
        return response()->noContent();
    }
}
