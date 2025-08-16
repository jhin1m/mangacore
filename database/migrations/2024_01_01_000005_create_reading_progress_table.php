<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateReadingProgressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Nullable for guest users
            $table->unsignedBigInteger('manga_id');
            $table->unsignedBigInteger('chapter_id');
            $table->integer('page_number');
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_bookmarked')->default(false);
            $table->text('bookmark_note')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            $table->foreign('chapter_id')->references('id')->on('chapters')->onDelete('cascade');
            
            // Unique constraint to ensure one progress record per user per manga
            $table->unique(['user_id', 'manga_id']);
            
            // Indexes for performance
            $table->index(['user_id', 'updated_at']);
            $table->index('manga_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reading_progress');
    }
}