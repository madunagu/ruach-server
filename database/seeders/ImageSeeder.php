<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Image;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Image::truncate();
        Image::factory(200)->create();

    }
}
