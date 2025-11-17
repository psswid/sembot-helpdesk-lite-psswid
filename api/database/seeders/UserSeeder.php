<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

class UserSeeder extends Seeder
{
    /**
     * Seed users with single-role assignment and sync with Spatie roles.
     */
    public function run(): void
    {
        $roles = SpatieRole::whereIn('name', ['admin', 'agent', 'reporter'])
            ->get()
            ->keyBy('name');

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Agent User',
                'email' => 'agent@example.com',
                'password' => Hash::make('password'),
                'role' => 'agent',
            ],
            [
                'name' => 'Reporter User',
                'email' => 'reporter@example.com',
                'password' => Hash::make('password'),
                'role' => 'reporter',
            ],
        ];

        foreach ($users as $data) {
            $roleModel = $roles[$data['role']] ?? null;

            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'role_id' => $roleModel?->id,
                ],
            );

            // Ensure role_id is synced in case user existed
            if ($user->role_id !== ($roleModel?->id)) {
                $user->forceFill(['role_id' => $roleModel?->id])->save();
            }

            // Also sync Spatie role assignments (many-to-many) for permissions usage
            if ($roleModel) {
                $user->syncRoles([$roleModel->name]);
            }
        }
    }
}
