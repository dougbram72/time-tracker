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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('trackable_type'); // 'App\Models\Project' or 'App\Models\Issue'
            $table->unsignedBigInteger('trackable_id');
            $table->timestamp('started_at');
            $table->timestamp('ended_at');
            $table->integer('duration_seconds');
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['trackable_type', 'trackable_id']);
            $table->index(['user_id', 'started_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
