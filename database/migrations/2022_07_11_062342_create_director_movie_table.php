<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OBSOLETE: This migration is kept for reference only.
 * The director_movie table has been replaced by the artist_manga table in the manga refactor.
 * This migration should not be run in new installations.
 */
class CreateDirectorMovieTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // OBSOLETE: This migration is skipped as the director_movie table has been replaced by artist_manga table.
        // The manga refactor provides equivalent functionality with the artist_manga table structure.
        // This migration is preserved for reference and potential rollback scenarios only.
        return;

        // Original migration code preserved for reference:
        /*
        Schema::create('director_movie', function (Blueprint $table) {
            $table->unsignedBigInteger('movie_id')->index();
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');

            $table->unsignedBigInteger('director_id')->index();
            $table->foreign('director_id')->references('id')->on('directors')->onDelete('cascade');
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
        Schema::dropIfExists('director_movie');
    }
}
