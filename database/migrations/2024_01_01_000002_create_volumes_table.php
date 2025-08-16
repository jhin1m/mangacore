<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateVolumesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('volumes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manga_id');
            $table->integer('volume_number');
            $table->string('title', 255)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->integer('chapter_count')->default(0);
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate volumes
            $table->unique(['manga_id', 'volume_number']);
            
            // Indexes for performance
            $table->index(['manga_id', 'volume_number']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('volumes');
    }
}