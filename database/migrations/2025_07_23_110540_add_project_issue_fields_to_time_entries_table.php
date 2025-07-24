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
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('issue_id')->nullable()->constrained()->onDelete('set null');
            
            // Add indexes for better query performance
            $table->index(['user_id', 'project_id']);
            $table->index(['user_id', 'issue_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['issue_id']);
            $table->dropIndex(['user_id', 'project_id']);
            $table->dropIndex(['user_id', 'issue_id']);
            $table->dropColumn(['project_id', 'issue_id']);
        });
    }
};
