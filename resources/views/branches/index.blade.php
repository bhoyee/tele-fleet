<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Branches</h1>
            <p class="text-muted mb-0">Maintain branch locations and ownership.</p>
        </div>
        <a href="{{ route('branches.create') }}" class="btn btn-primary">New Branch</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Location</th>
                            <th>Manager</th>
                            <th>Head Office</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $branch)
                            <tr>
                                <td>{{ $branch->name }}</td>
                                <td>{{ $branch->code }}</td>
                                <td>{{ trim($branch->city . ', ' . $branch->state, ', ') ?: '—' }}</td>
                                <td>{{ $branch->manager?->name ?? '—' }}</td>
                                <td>
                                    @if ($branch->is_head_office)
                                        <span class="badge bg-primary">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('branches.destroy', $branch) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this branch?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No branches found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($branches->hasPages())
            <div class="card-footer bg-white">
                {{ $branches->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
