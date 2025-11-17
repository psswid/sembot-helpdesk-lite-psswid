<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ExternalUserService
{
    public const CACHE_KEY = 'external_users.jsonplaceholder.all';
    public const CACHE_TTL_MINUTES = 10;

    protected bool $lastFromCache = false;

    public function usedCache(): bool
    {
        return $this->lastFromCache;
    }

    /**
     * Fetch users from JSONPlaceholder with caching and fallback.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUsers(): array
    {
        $this->lastFromCache = false;
        try {
            $response = Http::timeout(6)
                ->acceptJson()
                ->get('https://jsonplaceholder.typicode.com/users');

            if ($response->successful()) {
                $data = $response->json();
                Cache::put(self::CACHE_KEY, $data, now()->addMinutes(self::CACHE_TTL_MINUTES));
                return $data;
            }

            // Non-2xx: fall back to cache if available
            $cached = Cache::get(self::CACHE_KEY);
            if ($cached) {
                $this->lastFromCache = true;
                return $cached;
            }

            abort(503, 'External users service unavailable.');
        } catch (\Throwable $e) {
            $cached = Cache::get(self::CACHE_KEY);
            if ($cached) {
                $this->lastFromCache = true;
                return $cached;
            }
            abort(503, 'External users service error: '.$e->getMessage());
        }
    }

    /**
     * Sync selected external users by IDs into local Users table.
     * Assigns default role 'reporter' when available.
     *
     * @param array<int,int> $ids
     * @return array<int, array<string, mixed>> List of synced local users (id, name, email)
     */
    public function syncByIds(array $ids): array
    {
        $externalUsers = collect($this->getUsers());
        $selected = $externalUsers->whereIn('id', $ids);

        $synced = [];
        $defaultRole = Role::query()->where('name', 'reporter')->first();

        foreach ($selected as $eu) {
            $email = $eu['email'] ?? null;
            $name = $eu['name'] ?? null;
            if (! $email || ! $name) {
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make(Str::random(24)),
                ]
            );

            // Update name if changed
            if ($user->name !== $name) {
                $user->name = $name;
                $user->save();
            }

            if ($defaultRole) {
                $user->role()->associate($defaultRole);
                $user->save();
                $user->syncRoles(['reporter']);
            }

            $synced[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return $synced;
    }
}
