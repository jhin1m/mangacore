<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OBSOLETE: This migration is kept for reference only.
 * The actors table has been replaced by the authors table in the manga refactor.
 * This migration should not be run in new installations.
 */
class CreateActorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // OBSOLETE: This migration is skipped as the actors table has been replaced by authors table.
        // The manga refactor provides equivalent functionality with the authors table structure.
        // This migration is preserved for reference and potential rollback scenarios only.
        return;

        // Original migration code preserved for reference:
        /*
        Schema::create('actors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_md5')->unique();
            $table->string('slug')->index();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('bio')->nullable();
            $table->string('thumb_url', 2048)->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_des')->nullable();
            $table->string('seo_key')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('actors');
    }
}
