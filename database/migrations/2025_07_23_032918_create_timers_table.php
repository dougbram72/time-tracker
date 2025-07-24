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
        Schema::create('timers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('trackable_type'); // 'App\Models\Project' or 'App\Models\Issue'
            $table->unsignedBigInteger('trackable_id');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->integer('elapsed_seconds')->default(0);
            $table->enum('status', ['running', 'paused', 'stopped'])->default('stopped');
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['trackable_type', 'trackable_id']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timers');
    }
};
