<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Tag::truncate();
        $tags = [['tag'=>'chants'], ['tag'=>'spirit'], ['tag'=>'acoustic'], ['tag'=>'message'], ['tag'=>'prayer'], ['tag'=>'prophecy']];
        Tag::insert($tags);
    }
}
