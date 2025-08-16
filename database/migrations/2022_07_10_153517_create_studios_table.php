<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OBSOLETE: This migration is kept for reference only.
 * The studios table has been replaced by the publishers table in the manga refactor.
 * This migration should not be run in new installations.
 */
class CreateStudiosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // OBSOLETE: This migration is skipped as the studios table has been replaced by publishers table.
        // The manga refactor provides equivalent functionality with the publishers table structure.
        // This migration is preserved for reference and potential rollback scenarios only.
        return;

        // Original migration code preserved for reference:
        /*
        Schema::create('studios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_md5')->unique();
            $table->string('slug')->index();
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
        Schema::dropIfExists('studios');
    }
}
