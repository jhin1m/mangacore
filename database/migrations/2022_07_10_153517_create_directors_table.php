<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OBSOLETE: This migration is kept for reference only.
 * The directors table has been replaced by the artists table in the manga refactor.
 * This migration should not be run in new installations.
 */
class CreateDirectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // OBSOLETE: This migration is skipped as the directors table has been replaced by artists table.
        // The manga refactor provides equivalent functionality with the artists table structure.
        // This migration is preserved for reference and potential rollback scenarios only.
        return;

        // Original migration code preserved for reference:
        /*
        Schema::create('directors', function (Blueprint $table) {
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
        Schema::dropIfExists('directors');
    }
}
