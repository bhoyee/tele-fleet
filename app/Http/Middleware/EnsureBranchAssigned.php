<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAssigned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $role = (string) ($user->role ?? '');
        $requiresBranch = in_array($role, [User::ROLE_BRANCH_ADMIN, User::ROLE_BRANCH_HEAD], true);
        if (! $requiresBranch) {
            return $next($request);
        }

        if (! empty($user->branch_id)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $allowed = [
            'dashboard',
            'dashboard.metrics',
            'dashboard.calendar',
            'dashboard.trip-status',
            'dashboard.upcoming-trips',
            'helpdesk.index',
            'helpdesk.stats',
            'helpdesk.create',
            'helpdesk.store',
            'helpdesk.show',
            'helpdesk.update',
            'helpdesk.messages.store',
            'helpdesk.messages.latest',
            'helpdesk.attachments.download',
            'profile.edit',
            'profile.update',
            'profile.destroy',
            'profile.password.check',
            'logout',
            'notifications.index',
            'notifications.read',
            'notifications.read_all',
            'notifications.count',
            'notifications.latest',
        ];

        if (is_string($routeName) && in_array($routeName, $allowed, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your account is missing a branch assignment. Contact support to update your profile.',
            ], 403);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Your account is missing a branch assignment. Contact support to update your profile.')
            ->with('tele_branch_required', true);
    }
}

