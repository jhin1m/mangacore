<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddUserPreferencesToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('reading_mode', ['single', 'double', 'vertical', 'horizontal'])->default('single');
            $table->enum('image_quality', ['low', 'medium', 'high'])->default('medium');
            $table->json('reading_preferences')->nullable(); // For additional preferences
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reading_mode', 'image_quality', 'reading_preferences']);
        });
    }
}