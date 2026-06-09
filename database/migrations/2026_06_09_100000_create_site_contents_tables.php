<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_content_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('general'); // article, training, workflow, product, help
            $table->foreignId('parent_id')->nullable()->constrained('site_content_categories')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // article | training | workflow | product_content | help | procedure
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('status')->default('draft'); // draft | published | archived
            $table->boolean('ai_generated')->default(false);
            $table->string('ai_prompt')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('site_content_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('tags')->nullable();
            $table->json('meta')->nullable(); // extra data per type
            $table->string('panel_section')->nullable(); // for help type: which admin panel section
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
            $table->index('panel_section');
        });

        Schema::create('site_content_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('site_contents')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('media_path')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['content_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_content_steps');
        Schema::dropIfExists('site_contents');
        Schema::dropIfExists('site_content_categories');
    }
};
