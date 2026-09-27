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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->text('content');
            $table->json('platform_overrides')->nullable();
            $table->string('media_url')->nullable();
            $table->json('link_metadata')->nullable();
            $table->enum('status', [
                'draft',
                'scheduled',
                'publishing',
                'published',
                'partial_failure',
                'dlq',
            ])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('idempotency_key')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
