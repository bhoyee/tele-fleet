<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as ViewFacade;

class NotificationController extends Controller
{
    private function excludedTypes(): array
    {
        return [\App\Notifications\ChatMessageNotification::class];
    }

    private function dropdownPayload($user): array
    {
        $excludedTypes = $this->excludedTypes();

        $unreadCount = $user
            ->unreadNotifications()
            ->whereNotIn('type', $excludedTypes)
            ->count();

        $latestNotifications = $user
            ->unreadNotifications()
            ->whereNotIn('type', $excludedTypes)
            ->latest()
            ->take(5)
            ->get();

        $html = ViewFacade::make('notifications._dropdown_list', compact('latestNotifications'))->render();

        return [
            'count' => $unreadCount,
            'html' => $html,
        ];
    }

    public function index(Request $request): View
    {
        $excludedTypes = $this->excludedTypes();
        $notifications = $request->user()
            ->notifications()
            ->whereNotIn('type', $excludedTypes)
            ->latest()
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()
                ->json($this->dropdownPayload($request->user()));
        }

        return redirect()
            ->back()
            ->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $excludedTypes = $this->excludedTypes();
        $request->user()
            ->unreadNotifications()
            ->whereNotIn('type', $excludedTypes)
            ->get()
            ->markAsRead();

        if ($request->expectsJson()) {
            return response()
                ->json($this->dropdownPayload($request->user()));
        }

        return redirect()
            ->back()
            ->with('success', 'All notifications marked as read.');
    }

    public function cleanupDuplicates(Request $request): RedirectResponse
    {
        $user = $request->user();
        $rows = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->orderBy('created_at')
            ->get(['id', 'type', 'data', 'created_at']);

        $seen = [];
        $duplicateIds = [];

        foreach ($rows as $row) {
            $key = implode('|', [
                $row->type,
                (string) $row->data,
                $row->created_at,
            ]);

            if (isset($seen[$key])) {
                $duplicateIds[] = $row->id;
                continue;
            }

            $seen[$key] = true;
        }

        if (! empty($duplicateIds)) {
            DB::table('notifications')->whereIn('id', $duplicateIds)->delete();
        }

        return redirect()
            ->back()
            ->with('success', 'Removed ' . count($duplicateIds) . ' duplicate notifications.');
    }

    public function count(Request $request)
    {
        $excludedTypes = $this->excludedTypes();
        return response()->json([
            'count' => $request->user()
                ->unreadNotifications()
                ->whereNotIn('type', $excludedTypes)
                ->count(),
        ]);
    }

    public function latest(Request $request)
    {
        return response()->json($this->dropdownPayload($request->user()));
    }
}
