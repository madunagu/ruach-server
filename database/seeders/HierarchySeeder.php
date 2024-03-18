<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hierarchy;

class HierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Hierarchy::truncate();
        $heirarchies = [[
            'id'=>1,
            'rank' => '1',
            'name'=>'Minister',
            'user_id'=>51
        ],[

            'id'=>2,
            'rank' => '1',
            'name'=>'Minister',
            'user_id'=>52
        ],[

            'id'=>3,
            'rank' => '1',
            'name'=>'Minister',
            'user_id'=>53
        ]];
        Hierarchy::insert($heirarchies);

    }
}
