<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VideoPost;

class VideoPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        VideoPost::truncate();
        VideoPost::factory(100)->create();
    }
}
