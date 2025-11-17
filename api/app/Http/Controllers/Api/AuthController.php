<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user and issue a token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        // User model casts will hash the password automatically ('hashed' cast)
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        // Assign default business role and Spatie role (reporter) if available
        $defaultRole = Role::query()->where('name', 'reporter')->first();
        if ($defaultRole) {
            $user->role()->associate($defaultRole);
            $user->save();
            $user->syncRoles(['reporter']);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login a user and return a token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            // Revoke only the current token if available; otherwise, fallback to revoking all tokens
            $token = $user->currentAccessToken();
            if ($token) {
                $token->delete();
            } else {
                // In some environments the currentAccessToken may be null (e.g., guard mismatch), ensure logout by deleting all tokens
                $user->tokens()->delete();
            }
        }

        return response()->json(null, 204);
    }

    /**
     * Return the authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
