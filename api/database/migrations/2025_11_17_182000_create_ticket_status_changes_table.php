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
        Schema::create('ticket_status_changes', static function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ticket_id');
            $table->enum('old_status', ['open', 'in_progress', 'resolved', 'closed']);
            $table->enum('new_status', ['open', 'in_progress', 'resolved', 'closed']);
            $table->unsignedBigInteger('changed_by_user_id');
            $table->timestamp('changed_at');

            // Indexes
            $table->index('ticket_id');
            $table->index('changed_by_user_id');
            $table->index('changed_at');

            // Foreign keys with cascade delete as specified
            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->cascadeOnDelete();

            $table->foreign('changed_by_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_status_changes');
    }
};
