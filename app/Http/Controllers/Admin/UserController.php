<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\LoginHistory;
use App\Models\TripRequest;
use App\Models\User;
use App\Notifications\UserWelcomeCredentials;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class UserController extends Controller
{
    public function index(): View
    {
        $showArchived = request()->boolean('archived');

        $usersQuery = User::with('branch')
            ->where('is_system', false)
            ->orderBy('name');
        if ($showArchived) {
            $usersQuery->onlyTrashed();
        }

        $users = $usersQuery->get();

        $userStats = [
            'total' => $users->count(),
            'active' => $users->where('status', User::STATUS_ACTIVE)->count(),
            'inactive' => $users->where('status', User::STATUS_INACTIVE)->count(),
            'roles' => [
                User::ROLE_SUPER_ADMIN => $users->where('role', User::ROLE_SUPER_ADMIN)->count(),
                User::ROLE_FLEET_MANAGER => $users->where('role', User::ROLE_FLEET_MANAGER)->count(),
                User::ROLE_BRANCH_HEAD => $users->where('role', User::ROLE_BRANCH_HEAD)->count(),
                User::ROLE_BRANCH_ADMIN => $users->where('role', User::ROLE_BRANCH_ADMIN)->count(),
            ],
        ];

        return view('admin.users.index', compact('users', 'showArchived', 'userStats'));
    }

    public function create(): View
    {
        $branches = Branch::orderBy('name')->get();
        $roles = $this->roleOptions();
        $statuses = $this->statusOptions();

        return view('admin.users.create', compact('branches', 'roles', 'statuses'));
    }

    public function store(StoreUserRequest $request, AuditLogService $auditLog): RedirectResponse
    {
        $data = $request->validated();
        $passwordProvided = $request->filled('password');
        $plainPassword = $passwordProvided ? $data['password'] : Str::random(12);
        $data['password'] = Hash::make($plainPassword);

        $newUser = User::create($data);
        $auditLog->log('user.created', $newUser, [], $newUser->toArray());
        try {
            $newUser->notify(new UserWelcomeCredentials($plainPassword));
        } catch (Throwable $exception) {
            Log::warning('User welcome notification failed.', [
                'user_id' => $newUser->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.')
            ->with('generated_password', $passwordProvided ? null : $plainPassword);
    }

    public function edit(User $user): View
    {
        $branches = Branch::orderBy('name')->get();
        $roles = $this->roleOptions();
        $statuses = $this->statusOptions();

        return view('admin.users.edit', compact('user', 'branches', 'roles', 'statuses'));
    }

    public function show(User $user): View
    {
        $user->load('branch');
        $activities = AuditLog::where('user_id', $user->id)
            ->latest()
            ->take(200)
            ->get();
        $loginHistory = LoginHistory::where('user_id', $user->id)
            ->orderByDesc('logged_in_at')
            ->take(50)
            ->get();

        $tripRequestNumbers = $activities
            ->where('model_type', TripRequest::class)
            ->pluck('model_id')
            ->unique()
            ->filter()
            ->values();

        $tripRequestMap = $tripRequestNumbers->isEmpty()
            ? collect()
            : TripRequest::whereIn('id', $tripRequestNumbers)
                ->pluck('request_number', 'id');

        return view('admin.users.show', compact('user', 'activities', 'tripRequestMap', 'loginHistory'));
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogService $auditLog): RedirectResponse
    {
        $data = $request->validated();
        $oldValues = $user->getOriginal();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        $auditLog->log('user.updated', $user, $oldValues, $user->getChanges());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user, AuditLogService $auditLog): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        if ($user->is_system) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'This user is a protected system account.');
        }

        $user->delete();
        $auditLog->log('user.deleted', $user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User archived successfully.');
    }

    public function restore(User $user, AuditLogService $auditLog): RedirectResponse
    {
        if (! $user->trashed()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'User is already active.');
        }

        $user->restore();
        $auditLog->log('user.restored', $user);

        return redirect()
            ->route('admin.users.index', ['archived' => 1])
            ->with('success', 'User restored successfully.');
    }

    public function forceDelete(User $user, AuditLogService $auditLog): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->route('admin.users.index', ['archived' => 1])
                ->with('error', 'You cannot delete your own account.');
        }

        if ($user->is_system) {
            return redirect()
                ->route('admin.users.index', ['archived' => 1])
                ->with('error', 'This user is a protected system account.');
        }

        $user->forceDelete();
        $auditLog->log('user.force_deleted', $user);

        return redirect()
            ->route('admin.users.index', ['archived' => 1])
            ->with('success', 'User deleted permanently.');
    }

    private function roleOptions(): array
    {
        return [
            User::ROLE_SUPER_ADMIN => 'Head Fleet',
            User::ROLE_FLEET_MANAGER => 'Fleet Manager',
            User::ROLE_BRANCH_HEAD => 'Office Head',
            User::ROLE_BRANCH_ADMIN => 'Office Admin',
        ];
    }

    private function statusOptions(): array
    {
        return [
            User::STATUS_ACTIVE => 'Active',
            User::STATUS_INACTIVE => 'Inactive',
        ];
    }
}
