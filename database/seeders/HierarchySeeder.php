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
            'rank' => '1',
            'name'=>'General Overseer',
            // 'person_name'=>' John David',
            'user_id'=>1
        ],[
            'rank' => '2',
            'name'=>'Assistant Overseer',
            // 'person_name'=>'Owen Kings',
            'user_id'=>3
        ],[
            'rank' => '3',
            'name'=>'Praise Leader',
            // 'person_name'=>' Michael Jordan',
            'user_id'=>5
        ]];
        Hierarchy::insert($heirarchies);

    }
}
