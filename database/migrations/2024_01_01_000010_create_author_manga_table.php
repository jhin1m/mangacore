<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuthorMangaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('author_manga', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manga_id')->index();
            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            $table->unsignedBigInteger('author_id')->index();
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('author_manga');
    }
}