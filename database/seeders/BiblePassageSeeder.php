<?php


namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\BiblePassage;

class BiblePassageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        BiblePassage::truncate();

        $biblePassages = [
            [
                'chapter' => 3,
                'verse' => 16,
                'book' => 'John',
                'content' => 'For God  so loved the world that 
                he gave his only begotten son that whosoever 
                believeth in him will not perish but have everlasting life',
                'version' => 'kjv'
            ],
            [
                'chapter' => 91,
                'verse' => 1,
                'book' => 'Psalm',
                'content' => 'Whoever goes to the lord for safety whoever 
                remains under the protection of the almighty',
                'version' => 'kjv'
            ],
        ];

        BiblePassage::insert($biblePassages);
    }
}
