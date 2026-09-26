<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FollowerSeeder extends Seeder
{
    /**
     * Clear the legacy follower sample. FaithContentSeeder creates a small,
     * connected set of relationships for the named demo pastors.
     */
    public function run(): void
    {
        DB::table('user_followers')->truncate();
    }
}
