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
                <table class="table align-middle datatable">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Location</th>
                            <th>Head Office</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                <td>{{ \App\Support\TextNormalizer::titleText($branch->name) }}</td>
                                <td>{{ \App\Support\TextNormalizer::branchCode($branch->code) }}</td>
                                <td>
                                    {{
                                        trim(
                                            (\App\Support\TextNormalizer::titlePreserveAcronyms($branch->city, 3) ?? '')
                                            . ', '
                                            . (\App\Support\TextNormalizer::titlePreserveAcronyms($branch->state, 3) ?? ''),
                                            ', '
                                        ) ?: 'N/A'
                                    }}
                                </td>
                                <td>
                                    @if ($branch->is_head_office)
                                        <span class="badge bg-primary">Yes</span>
                                    @else
                                        <span class="badge bg-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('branches.show', $branch) }}" class="btn btn-sm btn-outline-primary" data-tele-tooltip title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-secondary" data-tele-tooltip title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteBranchModal"
                                            data-action="{{ route('branches.destroy', $branch) }}"
                                            data-name="{{ \App\Support\TextNormalizer::titleText($branch->name) }}"
                                            data-tele-tooltip
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteBranchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete <strong id="deleteBranchName"></strong>? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteBranchForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Branch</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const deleteBranchModal = document.getElementById('deleteBranchModal');
            if (deleteBranchModal) {
                deleteBranchModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const action = button.getAttribute('data-action');
                    const name = button.getAttribute('data-name');
                    document.getElementById('deleteBranchForm').setAttribute('action', action);
                    document.getElementById('deleteBranchName').textContent = name;
                });
            }
        </script>
    @endpush
</x-admin-layout>
