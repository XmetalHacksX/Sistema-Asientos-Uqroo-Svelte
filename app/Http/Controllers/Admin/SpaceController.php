<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Node;
use App\Models\Space;
use App\Models\LayoutObject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpaceController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Spaces/Index', [
            'spaces' => Space::query()
                ->with('building')
                ->withCount('nodes') // Cargar nodes_count dinámicamente
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Spaces/Create', [
            'buildings' => Building::query()->orderBy('name')->get(['id', 'name']),
            'templates' => Space::query()
                ->where('is_template', true)
                ->orderBy('name')
                ->get(['id', 'name']), // Cargar plantillas disponibles
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'template_space_id' => ['nullable', 'integer', 'exists:spaces,id'],
            'rows' => ['required_without:template_space_id', 'nullable', 'integer', 'min:1', 'max:200'],
            'cols' => ['required_without:template_space_id', 'nullable', 'integer', 'min:1', 'max:200'],
            'is_template' => ['nullable', 'boolean'],
        ]);

        $templateSpace = null;
        if (!empty($data['template_space_id'])) {
            $templateSpace = Space::findOrFail($data['template_space_id']);
        }

        $space = Space::create([
            'name' => $data['name'],
            'building_id' => $data['building_id'],
            'viewport' => $templateSpace ? $templateSpace->viewport : null,
            'is_template' => $request->boolean('is_template', false),
        ]);

        $now = now();

        if ($templateSpace) {
            // 1. Clonar nodos de la plantilla
            $nodes = $templateSpace->nodes()->get();
            $payload = [];
            foreach ($nodes as $node) {
                $payload[] = [
                    'space_id' => $space->id,
                    'identifier' => $node->identifier,
                    'pos_x' => $node->pos_x,
                    'pos_y' => $node->pos_y,
                    'status' => $node->status,
                    'is_occupied' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (count($payload) > 0) {
                Node::query()->insert($payload);
            }

            // 2. Clonar layoutObjects de la plantilla
            $objects = $templateSpace->layoutObjects()->get();
            foreach ($objects as $obj) {
                LayoutObject::create([
                    'space_id' => $space->id,
                    'type' => $obj->type,
                    'properties' => $obj->properties,
                ]);
            }
        } else {
            // Generar retícula rectangular desde cero
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
            if (count($payload) > 0) {
                Node::query()->insert($payload);
            }
        }

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
            'is_template' => ['nullable', 'boolean'],
        ]);

        $space->update([
            'name' => $data['name'],
            'building_id' => $data['building_id'],
            'is_template' => $request->boolean('is_template', false),
        ]);

        return redirect()->route('admin.spaces.index');
    }

    public function destroy(Space $space)
    {
        $space->delete();

        return redirect()->route('admin.spaces.index');
    }
}
