<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chapter_id');
            $table->integer('page_number');
            $table->string('image_url', 500);
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('chapter_id')->references('id')->on('chapters')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate pages
            $table->unique(['chapter_id', 'page_number']);
            
            // Index for performance
            $table->index(['chapter_id', 'page_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pages');
    }
}