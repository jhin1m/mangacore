<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * OBSOLETE: This migration is kept for reference only.
 * The movies table has been replaced by the mangas table in the manga refactor.
 * This migration should not be run in new installations.
 */
class AddFulltextIndexToMovies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // OBSOLETE: This migration is skipped as the movies table has been replaced by mangas table.
        // The manga refactor provides equivalent functionality with fulltext search on the mangas table.
        // This migration is preserved for reference and potential rollback scenarios only.
        return;

        // Original migration code preserved for reference:
        /*
        DB::statement('ALTER TABLE movies ADD FULLTEXT search_index (name, origin_name)');
        */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE movies DROP INDEX search_index');
    }
}
