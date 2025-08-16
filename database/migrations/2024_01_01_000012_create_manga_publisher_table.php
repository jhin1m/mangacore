<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMangaPublisherTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manga_publisher', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manga_id')->index();
            $table->foreign('manga_id')->references('id')->on('mangas')->onDelete('cascade');
            $table->unsignedBigInteger('publisher_id')->index();
            $table->foreign('publisher_id')->references('id')->on('publishers')->onDelete('cascade');
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
        Schema::dropIfExists('manga_publisher');
    }
}