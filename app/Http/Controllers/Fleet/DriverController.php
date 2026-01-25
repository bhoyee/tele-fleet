<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverRequest;
use App\Http\Requests\Fleet\UpdateDriverRequest;
use App\Models\Branch;
use App\Models\Driver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DriverController extends Controller
{
    public function index(): View
    {
        $drivers = Driver::with('branch')->orderBy('full_name')->get();

        return view('drivers.index', compact('drivers'));
    }

    public function create(): View
    {
        $branches = Branch::orderBy('name')->get();

        return view('drivers.create', compact('branches'));
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
        $branches = Branch::orderBy('name')->get();

        return view('drivers.edit', compact('driver', 'branches'));
    }

    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $driver->update($request->validated());

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
}
