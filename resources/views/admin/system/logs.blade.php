<x-admin-layout>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1">System Logs</h1>
            <p class="text-muted mb-0">Review recent application logs.</p>
        </div>
        @if ($selected)
            <a class="btn btn-outline-primary" href="{{ route('system.logs.download', $selected) }}" data-loading>Download Log</a>
            <a class="btn btn-outline-secondary" href="{{ route('system.logs.download', $selected) }}?format=csv" data-loading>Export CSV</a>
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
                    <label class="form-label" for="level">Level</label>
                    <select class="form-select" id="level" name="level">
                        <option value="">All</option>
                        @foreach (['emergency','alert','critical','error','warning','notice','info','debug'] as $level)
                            <option value="{{ $level }}" @selected(($filters['level'] ?? '') === $level)>{{ strtoupper($level) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="from">From</label>
                    <input class="form-control" id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="to">To</label>
                    <input class="form-control" id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="q">Search</label>
                    <input class="form-control" id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Search message or context...">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" type="submit">Apply Filters</button>
                </div>
                <div class="col-md-3">
                    <a class="btn btn-outline-secondary w-100" href="{{ route('system.logs', ['file' => $selected]) }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @php
                            $levelPalette = [
                                'emergency' => 'danger',
                                'alert' => 'danger',
                                'critical' => 'danger',
                                'error' => 'danger',
                                'warning' => 'warning',
                                'notice' => 'info',
                                'info' => 'primary',
                                'debug' => 'secondary',
                            ];
                        @endphp
                        @foreach ($summary['counts'] ?? [] as $level => $count)
                            <span class="badge bg-{{ $levelPalette[$level] ?? 'secondary' }}">
                                {{ strtoupper($level) }}: {{ $count }}
                            </span>
                        @endforeach
                        @if (empty($summary['counts']))
                            <span class="text-muted small">No summary available.</span>
                        @endif
                    </div>
                    @if (! empty($summary['top_messages']))
                        <div class="mt-3">
                            <div class="fw-semibold small text-muted">Top Messages</div>
                            <div class="d-flex flex-column gap-1 mt-2">
                                @foreach ($summary['top_messages'] as $message => $count)
                                    <div class="small">
                                        <span class="badge bg-light text-dark me-2">{{ $count }}</span>
                                        {{ $message }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="border rounded-3 bg-light p-3" style="max-height: 520px; overflow:auto; font-family: Consolas, 'Courier New', monospace; font-size: 0.85rem;">
                @forelse ($entries as $entry)
                    @if (is_array($entry))
                        <div class="mb-2">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                @if ($entry['timestamp'])
                                    <span class="badge bg-light text-dark">{{ $entry['timestamp'] }}</span>
                                @endif
                                @if ($entry['env'])
                                    <span class="badge bg-secondary">{{ strtoupper($entry['env']) }}</span>
                                @endif
                                <span class="badge bg-{{ $levelPalette[$entry['level']] ?? 'secondary' }}">
                                    {{ strtoupper($entry['level']) }}
                                </span>
                            </div>
                            <div class="mt-1 fw-semibold">{{ $entry['message'] }}</div>
                            @if (! empty($entry['context']))
                                <pre class="small text-muted mb-0">{{ json_encode($entry['context'], JSON_PRETTY_PRINT) }}</pre>
                            @endif
                        </div>
                    @else
                        <div>{{ $entry }}</div>
                    @endif
                @empty
                    <div class="text-muted">No log entries found.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
