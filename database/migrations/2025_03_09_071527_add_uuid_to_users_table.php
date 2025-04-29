<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Step 1: Add a new UUID column
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
        });

        // Step 2: Populate the UUID column with unique UUIDs for existing rows
        \DB::table('users')->get()->each(function ($user) {
            \DB::table('users')
                ->where('id', $user->id)
                ->update(['uuid' => Str::uuid()]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Step 1: Drop the UUID column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
