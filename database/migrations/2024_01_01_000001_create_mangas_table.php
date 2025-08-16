<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateMangasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mangas', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('original_title', 255)->nullable();
            $table->text('other_name')->nullable(); // Multiple names separated by commas
            $table->text('description')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->string('banner_image', 500)->nullable();
            $table->enum('type', ['manga', 'manhwa', 'manhua', 'webtoon'])->default('manga');
            $table->enum('status', ['ongoing', 'completed', 'hiatus', 'cancelled'])->default('ongoing');
            $table->enum('demographic', ['shounen', 'seinen', 'josei', 'shoujo', 'kodomomuke', 'general'])->default('general');
            $table->enum('reading_direction', ['ltr', 'rtl', 'vertical'])->default('ltr'); // vertical for webtoons
            $table->year('publication_year')->nullable();
            $table->integer('total_chapters')->nullable(); // Can be null if unknown
            $table->integer('total_volumes')->nullable(); // Can be null if no volumes or unknown
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->bigInteger('view_count')->default(0);
            $table->bigInteger('view_day')->default(0);
            $table->bigInteger('view_week')->default(0);
            $table->bigInteger('view_month')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->boolean('is_adult_content')->default(false);
            
            // Maintain compatibility with existing update tracking
            $table->string('update_handler', 1024)->nullable();
            $table->string('update_identity', 2048)->nullable();
            $table->string('update_checksum', 2048)->nullable();
            
            // User tracking
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->fullText(['title', 'original_title', 'other_name', 'description']);
            $table->index(['type', 'status']);
            $table->index('demographic');
            $table->index('publication_year');
            $table->index('view_count');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mangas');
    }
}