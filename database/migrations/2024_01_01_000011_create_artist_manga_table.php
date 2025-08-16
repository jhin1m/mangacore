<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtistMangaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('artist_manga', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manga_id')->index();
            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            $table->unsignedBigInteger('artist_id')->index();
            $table->foreign('artist_id')->references('id')->on('artists')->onDelete('cascade');
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
        Schema::dropIfExists('artist_manga');
    }
}