<x-admin-layout>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Maintenance</h1>
            <p class="text-muted mb-0">Update the maintenance schedule or status.</p>
        </div>
        <a href="{{ route('maintenances.show', $maintenance) }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('maintenances.update', $maintenance) }}">
                @csrf
                @method('PATCH')
                @include('maintenances._form', ['maintenance' => $maintenance])
                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-primary" type="submit">Update Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
