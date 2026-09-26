<?php

namespace Database\Seeders;

use App\Models\Comment;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Clear orphaned demo comments. FaithContentSeeder attaches comments to
     * the records after those records have been created.
     */
    public function run(): void
    {
        Comment::truncate();
    }
}
