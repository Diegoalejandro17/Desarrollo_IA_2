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
        Schema::create('evidence', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', [
                'document',
                'image',
                'video',
                'audio',
                'testimony',
                'other'
            ])->default('document');
            $table->string('file_path')->nullable(); 
            $table->string('file_url')->nullable(); 
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->nullable(); 
            $table->json('analysis_result')->nullable();
            $table->boolean('is_analyzed')->default(false);
            $table->timestamp('analyzed_at')->nullable();
            $table->json('metadata')->nullable(); 
            $table->timestamps();
            $table->softDeletes();

            $table->index('legal_case_id');
            $table->index('type');
            $table->index('is_analyzed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};
