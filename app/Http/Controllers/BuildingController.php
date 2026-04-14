<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Campus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BuildingController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Buildings/Index', [
            'buildings' => Building::query()
                ->with('campus')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Buildings/Create', [
            'campuses' => Campus::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'pos_x' => ['nullable', 'integer'],
            'pos_y' => ['nullable', 'integer'],
            'width' => ['nullable', 'integer'],
            'height' => ['nullable', 'integer'],
        ]);

        $data = array_merge([
            'pos_x' => 0,
            'pos_y' => 0,
            'width' => 0,
            'height' => 0,
        ], $data);

        Building::create($data);

        return redirect()->route('admin.buildings.index');
    }

    public function edit(Building $building)
    {
        $building->load('campus');

        return Inertia::render('Admin/Buildings/Edit', [
            'building' => $building,
            'campuses' => Campus::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Building $building)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'pos_x' => ['nullable', 'integer'],
            'pos_y' => ['nullable', 'integer'],
            'width' => ['nullable', 'integer'],
            'height' => ['nullable', 'integer'],
        ]);

        $building->update($data);

        return redirect()->route('admin.buildings.index');
    }

    public function destroy(Building $building)
    {
        $building->delete();

        return redirect()->route('admin.buildings.index');
    }
}

