<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleRequest;
use App\Http\Requests\Fleet\UpdateVehicleRequest;
use App\Models\Branch;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class VehicleController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::with('branch')->orderBy('registration_number')->paginate(15);

        return view('vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        $branches = Branch::orderBy('name')->get();

        return view('vehicles.create', compact('branches'));
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        Vehicle::create($data);

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle created successfully.');
    }

    public function edit(Vehicle $vehicle): View
    {
        $branches = Branch::orderBy('name')->get();

        return view('vehicles.edit', compact('vehicle', 'branches'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle archived successfully.');
    }
}
