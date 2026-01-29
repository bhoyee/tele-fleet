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
        $users = User::with('branch')->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
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
        $user->forceDelete();
        $auditLog->log('user.force_deleted', $user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function roleOptions(): array
    {
        return [
            User::ROLE_SUPER_ADMIN => 'Super Admin',
            User::ROLE_FLEET_MANAGER => 'Fleet Manager',
            User::ROLE_BRANCH_HEAD => 'Branch Head',
            User::ROLE_BRANCH_ADMIN => 'Branch Admin',
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
