<?php

namespace Database\Seeders;

use App\Models\Church;
use Illuminate\Database\Seeder;

class ChurchSeeder extends Seeder
{
    /**
     * Seed the baseline table. The named, scripture-centered demo churches are
     * added by FaithContentSeeder after the people and addresses exist.
     */
    public function run(): void
    {
        Church::truncate();
    }
}
