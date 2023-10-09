<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\AudioPost;

class AudioPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AudioPost::truncate();
        \App\Models\AudioPost::factory(100)->create();
    }
}
