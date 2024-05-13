<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Image;
use Illuminate\Support\Facades\DB;

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

        $appURL = env('APP_URL');
        // Image::factory(200)->create();
        $datas = [
            [
                "id" => 51,
                "full" => "$appURL/storage/images/ministers/full/theophilus-sunday.jpg",
                "large" => "$appURL/storage/images/ministers/full/theophilus-sunday.jpg",
                "medium" => "$appURL/storage/images/ministers/medium/theophilus-sunday.jpg",
                "small" => "$appURL/storage/images/ministers/small/theophilus-sunday.jpg",
                "user_id" => 1,
            ],

            [
                "id" => 52,
                "full" => "$appURL/storage/images/ministers/full/arome-osayi.jpeg",
                "large" => "$appURL/storage/images/ministers/full/arome-osayi.jpeg",
                "medium" => "$appURL/storage/images/ministers/medium/arome-osayi.jpeg",
                "small" => "$appURL/storage/images/ministers/small/arome-osayi.jpeg",
                "user_id" => 1,
            ],

            [
                "id" => 53,
                "full" => "$appURL/storage/images/ministers/full/joshua-selman.jpeg",
                "large" => "$appURL/storage/images/ministers/full/joshua-selman.jpeg",
                "medium" => "$appURL/storage/images/ministers/medium/joshua-selman.jpeg",
                "small" => "$appURL/storage/images/ministers/small/joshua-selman.jpeg",
                "user_id" => 1,
            ],
            [
                "id" => 54,
                "full" => "$appURL/storage/images/ministers/full/ephraim-sanni.jpeg",
                "large" => "$appURL/storage/images/ministers/full/ephraim-sanni.jpeg",
                "medium" => "$appURL/storage/images/ministers/medium/ephraim-sanni.jpeg",
                "small" => "$appURL/storage/images/ministers/small/ephraim-sanni.jpeg",
                "user_id" => 1,
            ],

            [
                "id" => 55,
                "full" => "$appURL/storage/images/ministers/full/michael-orokpo.jpeg",
                "large" => "$appURL/storage/images/ministers/large/michael-orokpo.jpeg",
                "medium" => "$appURL/storage/images/ministers/medium/michael-orokpo.jpeg",
                "small" => "$appURL/storage/images/ministers/small/michael-orokpo.jpeg",
                "user_id" => 1,
            ],

            [
                "id" => 56,
                "full" => "$appURL/storage/images/ministers/full/charles-spurgeon.jpeg",
                "large" => "$appURL/storage/images/ministers/large/charles-spurgeon.jpeg",
                "medium" => "$appURL/storage/images/ministers/medium/charles-spurgeon.jpeg",
                "small" => "$appURL/storage/images/ministers/small/charles-spurgeon.jpeg",
                "user_id" => 1,
            ],
        ];

        $inserResult = Image::insert($datas);

        //Add Images to User
        $appendages =  [];
        for ($i = 51; $i < 57; $i++) {
            $appendages[] =    [
                "image_id" => $i,
                "imageable_id" => $i,
                "imageable_type" => "user"
            ];
        }
        //Add Images for Theophilus to 6 new audio
        for ($i = 0; $i < 7; $i++) {
            $appendages[] =    [
                "image_id" => 1,
                "imageable_id" => $i,
                "imageable_type" => "audio"
            ];
        }



        DB::table("imageables")->insert($appendages);
    }
}
