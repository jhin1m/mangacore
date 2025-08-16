<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateChaptersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manga_id');
            $table->unsignedBigInteger('volume_id')->nullable();
            $table->string('title', 255)->nullable();
            $table->string('slug', 255)->nullable();
            $table->decimal('chapter_number', 4, 1); // Supports fractional chapters like 4.5, 4.6
            $table->integer('volume_number')->nullable();
            $table->integer('page_count')->default(0);
            $table->bigInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            $table->foreign('volume_id')->references('id')->on('volumes')->onDelete('set null');
            
            // Unique constraint to prevent duplicate chapters
            $table->unique(['manga_id', 'chapter_number']);
            
            // Indexes for performance
            $table->index(['manga_id', 'chapter_number']);
            $table->index('published_at');
            $table->index(['manga_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chapters');
    }
}