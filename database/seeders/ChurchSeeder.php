<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Universal\Constants;
use Illuminate\Support\Facades\DB;
use App\Models\Church;

class ChurchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Church::truncate();
        Church::factory(10)->create();

        for ($i = 0; $i < 10; $i++) {
            $query[] =
                [
                    'church_id' => $i,
                    'churchable_id' => 10 - $i,
                    'churchable_type' => Constants::$_[$i % count(Constants::$_)],
                ];
        }
        DB::table('churchables')->insert($query);
    }
}
