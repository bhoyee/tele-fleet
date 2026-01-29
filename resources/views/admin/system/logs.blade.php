<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">System Logs</h1>
            <p class="text-muted mb-0">Review recent application logs.</p>
        </div>
        @if ($selected)
            <a class="btn btn-outline-primary" href="{{ route('system.logs.download', $selected) }}" data-loading>Download Log</a>
        @endif
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label" for="file">Log File</label>
                    <select class="form-select" id="file" name="file">
                        @foreach ($files as $file)
                            <option value="{{ $file['name'] }}" @selected($selected === $file['name'])>
                                {{ $file['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" type="submit">Load</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="border rounded-3 bg-light p-3" style="max-height: 520px; overflow:auto; font-family: Consolas, 'Courier New', monospace; font-size: 0.85rem; white-space: pre-wrap;">
                @forelse ($entries as $entry)
                    <div>{{ $entry }}</div>
                @empty
                    <div class="text-muted">No log entries found.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
