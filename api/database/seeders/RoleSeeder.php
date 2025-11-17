<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        // Using default guard 'web' as configured in config/auth.php
        $guard = 'web';

        foreach (['admin', 'agent', 'reporter'] as $name) {
            Role::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }
    }
}
