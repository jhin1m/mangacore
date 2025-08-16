<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OBSOLETE: This migration is kept for reference only.
 * The movie_tag table has been replaced by the manga_tag table in the manga refactor.
 * This migration should not be run in new installations.
 */
class CreateMovieTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // OBSOLETE: This migration is skipped as the movie_tag table has been replaced by manga_tag table.
        // The manga refactor provides equivalent functionality with the manga_tag table structure.
        // This migration is preserved for reference and potential rollback scenarios only.
        return;

        // Original migration code preserved for reference:
        /*
        Schema::create('movie_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('movie_id')->index();
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');

            $table->unsignedBigInteger('tag_id')->index();
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });
        */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movie_tag');
    }
}
