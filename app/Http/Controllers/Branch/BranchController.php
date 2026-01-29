<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BranchController extends Controller
{
    public function index(): View
    {
        $branches = Branch::with('manager')->orderByDesc('is_head_office')->orderBy('name')->get();

        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_head_office'] = (bool) ($data['is_head_office'] ?? false);

        if ($data['is_head_office']) {
            Branch::where('is_head_office', true)->update(['is_head_office' => false]);
        }

        Branch::create($data);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch): View
    {
        return view('branches.edit', compact('branch'));
    }

    public function show(Branch $branch): View
    {
        $branch->load('manager');
        $branchAdmins = User::where('branch_id', $branch->id)
            ->where('role', User::ROLE_BRANCH_ADMIN)
            ->orderBy('name')
            ->get();
        $branchHeads = User::where('branch_id', $branch->id)
            ->where('role', User::ROLE_BRANCH_HEAD)
            ->orderBy('name')
            ->get();

        return view('branches.show', compact('branch', 'branchAdmins', 'branchHeads'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();
        $data['is_head_office'] = (bool) ($data['is_head_office'] ?? false);

        if ($data['is_head_office']) {
            Branch::where('is_head_office', true)->where('id', '!=', $branch->id)->update(['is_head_office' => false]);
        }

        $branch->update($data);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->delete();

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch removed successfully.');
    }
}
