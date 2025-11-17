<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');

        Schema::table('users', static function (Blueprint $table) use ($rolesTable) {
            // Add nullable role_id referencing the roles table; set null on delete
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
            $table->foreign('role_id')
                ->references('id')
                ->on($rolesTable)
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
