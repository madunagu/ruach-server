<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Conversations, so one messaging surface serves both private and group chat.
 *
 * A private conversation is between two users; a group one hangs off an event
 * and its participants are that event's attendees. Both are the same row and
 * the same message endpoints, so the client has one code path rather than two.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->reshapeMessages();

        if (! Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();

                // 'private' between two users, 'event' for an event's group.
                $table->string('type')->default('private');

                // Set for group conversations; the source of the membership.
                $table->unsignedBigInteger('event_id')->nullable()->index();

                // Group conversations are named after their event; private ones
                // derive a title from the other participant instead.
                $table->string('title')->nullable();

                // Denormalised so the conversation list can sort without joining
                // through to the newest message of every thread.
                $table->timestamp('last_message_at')->nullable()->index();

                $table->timestamps();

                // One group per event. Private rows have a null event_id, and
                // MySQL treats those as distinct so they do not collide here.
                $table->unique(['type', 'event_id']);
            });
        }

        if (! Schema::hasTable('conversation_user')) {
            Schema::create('conversation_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->unsignedBigInteger('user_id')->index();

                // The id of the newest message this participant has opened.
                //
                // An id rather than a read timestamp: messages written in the
                // same second as the read would compare equal and never count
                // as unread, and ids cannot go backwards.
                $table->unsignedBigInteger('last_read_message_id')
                    ->nullable()
                    ->default(0);
                $table->timestamps();

                $table->unique(['conversation_id', 'user_id']);
            });
        }
    }

    /**
     * Converts the legacy one-to-one message table into thread messages.
     *
     * The old shape was `sender_id`/`reciever_id`, which cannot express a group.
     * The table is expected to be empty; refusing loudly beats dropping real
     * history on the floor if that ever stops being true.
     */
    private function reshapeMessages(): void
    {
        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });

            return;
        }

        if (Schema::hasColumn('messages', 'conversation_id')) {
            return;
        }

        if (DB::table('messages')->count() > 0) {
            throw new RuntimeException(
                'The legacy messages table holds rows and cannot be converted to '
                . 'conversations automatically. Migrate them by hand first.'
            );
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['reciever_id', 'sender_id', 'message']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('body')->nullable();
            $table->timestamp('read_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversations');
    }
};