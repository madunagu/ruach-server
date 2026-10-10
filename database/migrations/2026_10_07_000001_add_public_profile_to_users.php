<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks rows that are display-only team credits rather than real logins.
     *
     * Public profiles are created from the hierarchy (team) sheet for people
     * without an account. They carry an unusable random email/password so
     * they can never authenticate, and are safe to show anywhere a user
     * can be shown.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_public_profile')->default(false)->after('is_editor');
            $table->foreignId('created_by')->nullable()->after('is_public_profile')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('is_public_profile');
        });
    }
};
