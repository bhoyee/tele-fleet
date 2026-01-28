<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Schedule Maintenance</h1>
            <p class="text-muted mb-0">Plan maintenance and track service history.</p>
        </div>
        <a href="{{ route('maintenances.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('maintenances.store') }}">
                @csrf
                @include('maintenances._form', ['maintenance' => null])
                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary" type="submit">Save Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
