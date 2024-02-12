<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feed;

class FeedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Feed::truncate();
        $types = ['event', 'audio', 'video', 'post'];

        $inserts = [];

        for ($i = 1; $i <= 6; $i++) {
            $inserts[] = [
                'parentable_type' => 'audio',
                'parentable_id' => $i,
                'postable_type' => 'user',
                'postable_id' => 1,
            ];
        }

        Feed::insert($inserts);
    }
}
