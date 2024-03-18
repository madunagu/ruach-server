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
        // Image::factory(200)->create();
        $datas = [
            [
                'id' => 51,
                'full' => 'http://ruach.ziritetech.com/storage/images/ministers/full/theophilus_sunday.jpg',
                'large' => 'http://ruach.ziritetech.com/storage/images/ministers/full/theophilus_sunday.jpg',
                'medium' => 'http://ruach.ziritetech.com/storage/images/ministers/medium/theophilus_sunday.jpg',
                'small' => 'http://ruach.ziritetech.com/storage/images/ministers/small/theophilus_sunday.jpg',
                'user_id' => 1,
            ],

            [
                'id' => 52,
                'full' => 'http://ruach.ziritetech.com/storage/images/ministers/full/arome-osayi.jpeg',
                'large' => 'http://ruach.ziritetech.com/storage/images/ministers/full/arome-osayi.jpeg',
                'medium' => 'http://ruach.ziritetech.com/storage/images/ministers/medium/arome-osayi.jpeg',
                'small' => 'http://ruach.ziritetech.com/storage/images/ministers/small/arome-osayi.jpeg',
                'user_id' => 1,
            ],

            [
                'id' => 53,
                'full' => 'http://ruach.ziritetech.com/storage/images/ministers/full/joshua-selman.jpeg',
                'large' => 'http://ruach.ziritetech.com/storage/images/ministers/full/joshua-selman.jpeg',
                'medium' => 'http://ruach.ziritetech.com/storage/images/ministers/medium/joshua-selman.jpeg',
                'small' => 'http://ruach.ziritetech.com/storage/images/ministers/small/joshua-selman.jpeg',
                'user_id' => 1,
            ],
            [
                'id' => 54,
                'full' => 'http://ruach.ziritetech.com/storage/images/ministers/full/ephraim-sanni.jpeg',
                'large' => 'http://ruach.ziritetech.com/storage/images/ministers/full/ephraim-sanni.jpeg',
                'medium' => 'http://ruach.ziritetech.com/storage/images/ministers/medium/ephraim-sanni.jpeg',
                'small' => 'http://ruach.ziritetech.com/storage/images/ministers/small/ephraim-sanni.jpeg',
                'user_id' => 1,
            ],

            [
                'id' => 55,
                'full' => 'http://ruach.ziritetech.com/storage/images/ministers/full/michael-orokpo.jpeg',
                'large' => 'http://ruach.ziritetech.com/storage/images/ministers/large/michael-orokpo.jpeg',
                'medium' => 'http://ruach.ziritetech.com/storage/images/ministers/medium/michael-orokpo.jpeg',
                'small' => 'http://ruach.ziritetech.com/storage/images/ministers/small/michael-orokpo.jpeg',
                'user_id' => 1,
            ],

            [
                'id' => 56,
                'full' => 'http://ruach.ziritetech.com/storage/images/ministers/full/charles-spurgeon.jpeg',
                'large' => 'http://ruach.ziritetech.com/storage/images/ministers/large/charles-spurgeon.jpeg',
                'medium' => 'http://ruach.ziritetech.com/storage/images/ministers/medium/charles-spurgeon.jpeg',
                'small' => 'http://ruach.ziritetech.com/storage/images/ministers/small/charles-spurgeon.jpeg',
                'user_id' => 1,
            ],
        ];

        $inserResult = Image::insert($datas);

        //Add Images to User
        $appendages =  [];
        for ($i = 51; $i < 57; $i++) {
            $appendages[] =    [
                'image_id' => $i,
                'imageable_id' => $i,
                'imageable_type' => 'user'
            ];
        }
        //Add Images for Theophilus to 6 new audio
        for ($i = 0; $i < 7; $i++) {
            $appendages[] =    [
                'image_id' => 1,
                'imageable_id' => $i,
                'imageable_type' => 'audio'
            ];
        }



        DB::table('imageables')->insert($appendages);
    }
}
