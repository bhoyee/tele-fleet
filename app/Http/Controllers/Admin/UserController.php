<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Branch;
use App\Models\User;
use App\Notifications\UserWelcomeCredentials;
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

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $passwordProvided = $request->filled('password');
        $plainPassword = $passwordProvided ? $data['password'] : Str::random(12);
        $data['password'] = Hash::make($plainPassword);

        $newUser = User::create($data);
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

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->forceDelete();

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
