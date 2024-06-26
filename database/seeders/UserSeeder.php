<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::truncate();

        User::factory()->create([
            'id' => 1,
            'avatar' => 'https://randomuser.me/api/portraits/men/1.jpg',
            'name' => 'Ruach Curated',
            'email' => 'contact.ruach.app@gmail.com',
            'password' => Hash::make('mercy'),
            'is_verified' => 1,
            'is_editor' => 1,
        ]);

        User::factory()->create([
            'id' => 2,
            'avatar' => 'https://randomuser.me/api/portraits/men/2.jpg',
            'name' => 'NB Gospel',
            'email' => 'gospelnb1@gmail.com',
            'password' => Hash::make('blessed'),
            'is_verified' => 1,
            'is_editor' => 1,
        ]);

        User::factory()->create([
            'id' => 3,
            'avatar' => 'https://randomuser.me/api/portraits/men/3.jpg',
            'name' => 'Ekene Madunagu',
            'email' => 'ekenemadunagu@gmail.com',
            'password' => Hash::make('mercy'),
            'is_verified' => 1,
            'is_editor' => 1,
        ]);

        $appURL = env('APP_URL');

        $datas = [
            [
                'id' => 51,
                'is_minister' => 1,
                'name' => 'Theophilus Sunday',
                'email' => bin2hex(random_bytes(10)),
                'password' => 'intermittent',
                'avatar' => "$appURL/storage/images/ministers/small/theophilus-sunday.jpg",

            ],
            [
                'id' => 52,
                'is_minister' => 1,
                'name' => 'Arome Osayi',
                'email' => bin2hex(random_bytes(10)),
                'password' => 'intermittent',
                'avatar' => "$appURL/storage/images/ministers/small/arome-osayi.jpeg",

            ],
            [
                'id' => 53,
                'is_minister' => 1,
                'name' => 'Joshua Selman',
                'email' => bin2hex(random_bytes(10)),
                'password' => 'intermittent',
                'avatar' => "$appURL/storage/images/ministers/small/joshua-selman.jpeg",

            ],
            [
                'id' => 54,
                'is_minister' => 1,
                'name' => 'Ephraim Sanni',
                'email' => bin2hex(random_bytes(10)),
                'password' => 'intermittent',
                'avatar' => "$appURL/storage/images/ministers/small/ephraim-sanni.jpeg",

            ],
            [
                'id' => 55,
                'is_minister' => 1,
                'name' => 'Michael Orokpo',
                'email' => bin2hex(random_bytes(10)),
                'password' => 'intermittent',
                'avatar' => "$appURL/storage/images/ministers/small/michael-orokpo.jpeg",

            ],
            [
                'id' => 56,
                'is_minister' => 1,
                'name' => 'Charles Spurgeon',
                'email' => bin2hex(random_bytes(10)),
                'password' => 'intermittent',
                'avatar' => "$appURL/storage/images/ministers/small/charles-spurgeon.jpeg",

            ],



        ];

        // \App\Models\User::factory(50)->create();
        User::insert($datas);
        // foreach ($datas as $key => $value) {
        //     User::create($value);
        // }
    }
}
