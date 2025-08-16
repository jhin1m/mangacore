<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateMangaUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manga_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manga_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('rating', 3, 1)->nullable(); // Rating from 1.0 to 10.0
            $table->text('review')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint to ensure one rating per user per manga
            $table->unique(['manga_id', 'user_id']);
            
            // Indexes for performance
            $table->index('manga_id');
            $table->index('user_id');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manga_user');
    }
}