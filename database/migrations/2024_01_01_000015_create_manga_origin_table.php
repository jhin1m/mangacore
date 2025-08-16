<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMangaOriginTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manga_origin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manga_id')->index();
            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            $table->unsignedBigInteger('origin_id')->index();
            $table->foreign('origin_id')->references('id')->on('origins')->onDelete('cascade');
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
        Schema::dropIfExists('manga_origin');
    }
}