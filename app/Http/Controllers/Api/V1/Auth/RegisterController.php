<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

final class RegisterController extends Controller
{
    public function __construct(private UserService $users) {}

    /**
     * Handle user registration.
     */
    public function __invoke(RegisterUserRequest $request): JsonResponse
    {
        $result = $this->users->register($request->validated());
        return response()->json($result, 201);
    }
}
