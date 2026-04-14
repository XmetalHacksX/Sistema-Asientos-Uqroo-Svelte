<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Node;
use App\Models\Space;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpaceController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Spaces/Index', [
            'spaces' => Space::query()
                ->with('building')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Spaces/Create', [
            'buildings' => Building::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'rows' => ['required', 'integer', 'min:1', 'max:200'],
            'cols' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        $space = Space::create([
            'name' => $data['name'],
            'building_id' => $data['building_id'],
            'viewport' => null,
        ]);

        $now = now();
        $rows = (int) $data['rows'];
        $cols = (int) $data['cols'];

        $payload = [];
        for ($r = 0; $r < $rows; $r++) {
            $letter = chr(65 + $r);
            for ($c = 0; $c < $cols; $c++) {
                $payload[] = [
                    'space_id' => $space->id,
                    'identifier' => $letter . '-' . ($c + 1),
                    'pos_x' => $c * 40,
                    'pos_y' => $r * 40,
                    'status' => 'active',
                    'is_occupied' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Node::query()->insert($payload);

        return redirect()->route('admin.spaces.index');
    }

    public function edit(Space $space)
    {
        return Inertia::render('Admin/Spaces/Edit', [
            'space' => $space,
            'buildings' => Building::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Space $space)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
        ]);

        $space->update($data);

        return redirect()->route('admin.spaces.index');
    }

    public function destroy(Space $space)
    {
        $space->delete();

        return redirect()->route('admin.spaces.index');
    }
}
