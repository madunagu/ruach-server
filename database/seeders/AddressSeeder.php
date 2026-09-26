<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * Clear the legacy address sample. FaithContentSeeder creates the Nigerian
     * locations used by the demo pastors, churches, and events.
     */
    public function run(): void
    {
        Address::truncate();
    }
}
