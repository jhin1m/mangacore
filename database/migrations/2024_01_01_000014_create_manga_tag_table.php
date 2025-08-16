<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMangaTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manga_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manga_id')->index();
            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            $table->unsignedBigInteger('tag_id')->index();
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
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
        Schema::dropIfExists('manga_tag');
    }
}