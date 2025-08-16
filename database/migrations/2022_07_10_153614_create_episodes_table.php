<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * OBSOLETE: This migration is kept for reference only.
 * The episodes table has been replaced by the chapters table in the manga refactor.
 * This migration should not be run in new installations.
 */
class CreateEpisodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // OBSOLETE: This migration is skipped as the episodes table has been replaced by chapters table.
        // The manga refactor provides equivalent functionality with the chapters table structure.
        // This migration is preserved for reference and potential rollback scenarios only.
        return;

        // Original migration code preserved for reference:
        /*
        Schema::create('episodes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('movie_id')->index();
            $table->foreign('movie_id')->references('id')->on('movies')->onDelete('cascade');

            $table->string('server');
            $table->string('name');
            $table->string('slug');
            $table->string('type');
            $table->string('link')->nullable();
            $table->boolean('has_report')->default(false);
            $table->string('report_message', 512)->nullable();
            $table->timestamps();

            $table->index(['movie_id', 'slug']);
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
        Schema::dropIfExists('episodes');
    }
}
