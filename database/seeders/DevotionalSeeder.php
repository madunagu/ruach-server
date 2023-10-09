<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Devotional;

class DevotionalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Devotional::truncate();
        Devotional::factory(100)->create();

    }
}
