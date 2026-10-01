<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * In-app notifications for social activity.
 *
 * The client already renders a notifications list and reads a count off the
 * user payload; this table is the source for both.
 *
 * `data` holds the type-specific context (the comment body, the liked post's
 * name, the event date) so the list can be rendered without extra requests.
 * `notifiable_type`/`notifiable_id` are kept polymorphic rather than a plain
 * user_id so the same table can later carry notifications for posts or
 * churches without a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Recipient. Polymorphic so other notifiables can be added later.
            $table->unsignedBigInteger('notifiable_id')->index();
            $table->string('notifiable_type', 64);

            // The event that produced this: like, comment, follow, attend.
            $table->string('type', 32)->index();

            // Who performed the action, and the subject it was performed on.
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type', 64)->nullable();

            // Pre-rendered copy plus any extra context the client needs.
            $table->string('body')->nullable();
            $table->json('data')->nullable();

            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            // A user should never be told twice about the same action.
            $table->unique(
                ['notifiable_id', 'notifiable_type', 'type', 'actor_id', 'subject_id', 'subject_type'],
                'notifications_once_per_action'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};