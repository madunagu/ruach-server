<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HierarchyGroup;

class HierarchyGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        HierarchyGroup::truncate();
        HierarchyGroup::create(['id'=>1,
          'name'=>'Church Hierarchy',
          'description'=>'default hierarchy group for small churches',
          'user_id' => '1',
        ]);
    }
}
