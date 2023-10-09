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
        $types = [
            'event', 'audio', 'video', 'post'
        ];
        $inserts = [];
        for ($i = 1; $i <= 10; $i++) {
            foreach ($types as $type) {
                $inserts[] = [
                    'parentable_type' => $type,
                    'postable_type' => 'user',
                    'postable_id' => $i,
                    'parentable_id' => $i,
                ];
            }
        }

        Feed::insert($inserts);
    }
}
