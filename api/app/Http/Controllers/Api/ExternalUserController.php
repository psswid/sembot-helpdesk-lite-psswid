<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExternalUsers\SyncRequest;
use App\Services\ExternalUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalUserController extends Controller
{
    public function __construct(private readonly ExternalUserService $service)
    {
    }

    /**
     * GET /api/external-users
     */
    public function index(): JsonResponse
    {
        $users = $this->service->getUsers();

        return response()->json([
            'data' => $users,
            'source' => $this->service->usedCache() ? 'cache' : 'remote',
        ]);
    }

    /**
     * POST /api/external-users/sync
     */
    public function sync(SyncRequest $request): JsonResponse
    {
        $synced = $this->service->syncByIds($request->validated()['ids']);

        return response()->json([
            'synced' => count($synced),
            'users' => $synced,
        ], 201);
    }
}
