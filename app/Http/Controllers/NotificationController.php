<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    /**
     * The recipient's notifications, newest first.
     *
     * Optional filters mirror what the client can show as tabs: `type` for a
     * single kind and `unread` for the unread-only view.
     */
    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => [
                'nullable',
                'string',
                Rule::in([
                    Notification::LIKE,
                    Notification::COMMENT,
                    Notification::FOLLOW,
                    Notification::ATTEND,
                    Notification::MESSAGE,
                ]),
            ],
            'unread' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $userId = Auth::id();
        $perPage = (int) ($request['perPage'] ?? 30);

        $query = Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', 'user')
            ->with(['actor:id,name,avatar'])
            ->ofType($request['type']);

        if ($request->has('unread') && $request->boolean('unread')) {
            $query->unread();
        }

        return response()->json(
            $query->orderByDesc('id')->paginate($perPage)
        );
    }

    /** Unread totals for the badges on the client. */
    public function counts()
    {
        $userId = Auth::id();

        $unread = Notification::where('notifiable_id', $userId)
            ->where('notifiable_type', 'user')
            ->unread();

        $byType = (clone $unread)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        return response()->json([
            'data' => [
                'unread_count' => (clone $unread)->count(),
                'by_type' => array_map('intval', $byType),
            ],
        ]);
    }

    /** A single notification, marked read as a side effect of reading it. */
    public function get(Request $request)
    {
        $notification = $this->findForUser((int) $request->route('id'));

        if ($notification === null) {
            return response()->json(['data' => false], 404);
        }

        $notification->markAsRead();

        return response()->json(['data' => $notification->load('actor:id,name,avatar')]);
    }

    /** Marks one notification read. */
    public function read(Request $request)
    {
        $notification = $this->findForUser((int) $request->route('id'));

        if ($notification === null) {
            return response()->json(['data' => false], 404);
        }

        return response()->json(['data' => $notification->markAsRead()]);
    }

    /** Marks every unread notification read. */
    public function readAll()
    {
        $count = Notification::where('notifiable_id', Auth::id())
            ->where('notifiable_type', 'user')
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['data' => (bool) $count, 'count' => $count]);
    }

    public function delete(Request $request)
    {
        $notification = $this->findForUser((int) $request->route('id'));

        if ($notification === null) {
            return response()->json(['data' => false], 404);
        }

        $notification->delete();

        return response()->json(['data' => true]);
    }

    /**
     * Scopes a lookup to the authenticated user.
     *
     * Without this an id could read or mutate another user's notifications.
     */
    private function findForUser(int $id): ?Notification
    {
        return Notification::where('id', $id)
            ->where('notifiable_id', Auth::id())
            ->where('notifiable_type', 'user')
            ->first();
    }
}