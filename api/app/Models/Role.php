<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Allow mass assignment by default for simplicity.
     * Adjust as needed when adding columns like slug/description.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
}
