<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">New Vehicle</h1>
            <p class="text-muted mb-0">Register a new vehicle in the fleet.</p>
        </div>
        <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('vehicles.store') }}">
                @include('vehicles._form')
                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
