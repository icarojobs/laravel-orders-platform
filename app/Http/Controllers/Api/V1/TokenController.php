<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IssueTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function store(IssueTokenRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $abilities = $user->role->canManageOrders() ? ['orders:read', 'orders:write'] : ['orders:read'];

        return response()->json([
            'token' => $user->createToken($request->string('device_name'), $abilities)->plainTextToken,
            'abilities' => $abilities,
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request): Response
    {
        $request->user()?->currentAccessToken()->delete();

        return response()->noContent();
    }
}
