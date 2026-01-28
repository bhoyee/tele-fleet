<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverRequest;
use App\Http\Requests\Fleet\UpdateDriverRequest;
use App\Models\Driver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DriverController extends Controller
{
    public function index(): View
    {
        $drivers = Driver::orderBy('full_name')->get();

        return view('drivers.index', compact('drivers'));
    }

    public function create(): View
    {
        return view('drivers.create');
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        Driver::create($request->validated());

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver created successfully.');
    }

    public function edit(Driver $driver): View
    {
        return view('drivers.edit', compact('driver'));
    }

    public function show(Driver $driver): View
    {
        $driver->load('branch');

        return view('drivers.show', compact('driver'));
    }

    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['license_expiry']) && $driver->license_expiry?->format('Y-m-d') !== $data['license_expiry']) {
            $data['license_expiry_notified_at'] = null;
        }
        $driver->update($data);

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        $driver->delete();

        return redirect()
            ->route('drivers.index')
            ->with('success', 'Driver archived successfully.');
    }

    public function indexData(): JsonResponse
    {
        $drivers = Driver::orderBy('full_name')->get();

        return response()->json([
            'data' => $drivers->map(function (Driver $driver): array {
                return [
                    'id' => $driver->id,
                    'full_name' => $driver->full_name,
                    'license_number' => $driver->license_number,
                    'license_expiry' => $driver->license_expiry?->format('M d, Y') ?? 'N/A',
                    'phone' => $driver->phone,
                    'status' => $driver->status,
                ];
            }),
        ]);
    }
}
