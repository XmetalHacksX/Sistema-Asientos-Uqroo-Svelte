<?php

namespace App\Http\Controllers;

use App\Models\Node;
use App\Models\Space;
use App\Models\LayoutObject; // Importamos el nuevo modelo
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminTeatroController extends Controller
{
    public function edit(Space $space)
    {
        // Cargamos los nodos y los objetos del escenario para que Svelte los reciba al abrir la página
        $space->load(['nodes', 'layoutObjects']);

        return Inertia::render('Admin/Teatro/Editor', [
            'space' => $space,
        ]);
    }

    public function updateLayout(Request $request, Space $space)
    {
        // Quitamos la validación estricta de 'exists:nodes,id' para permitir IDs negativos
        $data = $request->validate([
            'nodes' => ['nullable', 'array'],
            'layout_objects' => ['nullable', 'array'],
        ]);

        // ==========================================
        // 1. SINCRONIZAR ASIENTOS (NODES)
        // ==========================================
        $nodesData = $data['nodes'] ?? [];
        $existingNodeIds = collect($nodesData)->pluck('id')->filter(fn($id) => $id > 0)->toArray();

        // Eliminar los asientos que ya no vienen en la petición (los que borraste)
        Node::where('space_id', $space->id)
            ->whereNotIn('id', $existingNodeIds)
            ->delete();

        foreach ($nodesData as $node) {
            if ($node['id'] > 0) {
                // Actualizar existentes
                Node::where('id', $node['id'])->where('space_id', $space->id)->update([
                    'pos_x' => $node['pos_x'],
                    'pos_y' => $node['pos_y'],
                    'identifier' => $node['identifier'],
                    'status' => $node['status'],
                ]);
            } else {
                // Crear nuevos (IDs negativos)
                Node::create([
                    'space_id' => $space->id,
                    'identifier' => $node['identifier'],
                    'pos_x' => $node['pos_x'],
                    'pos_y' => $node['pos_y'],
                    'status' => $node['status'],
                    'is_occupied' => false,
                ]);
            }
        }

        // ==========================================
        // 2. SINCRONIZAR ESCENARIOS (LAYOUT OBJECTS)
        // ==========================================
        $objectsData = $data['layout_objects'] ?? [];
        $existingObjectIds = collect($objectsData)->pluck('id')->filter(fn($id) => $id > 0)->toArray();

        // Eliminar los objetos que borraste
        LayoutObject::where('space_id', $space->id)
            ->whereNotIn('id', $existingObjectIds)
            ->delete();

        foreach ($objectsData as $obj) {
            if ($obj['id'] > 0) {
                // Actualizar existentes
                LayoutObject::where('id', $obj['id'])->where('space_id', $space->id)->update([
                    'type' => $obj['type'],
                    'properties' => $obj['properties'],
                ]);
            } else {
                // Crear nuevos (IDs negativos)
                LayoutObject::create([
                    'space_id' => $space->id,
                    'type' => $obj['type'],
                    'properties' => $obj['properties'],
                ]);
            }
        }

        return redirect()->back();
    }

    public function updateStatus(Request $request, Space $space, Node $node)
    {
        abort_unless($node->space_id === $space->id, 404);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:active,maintenance,blocked'],
        ]);

        $node->update(['status' => $data['status']]);

        return redirect()->back();
    }

    public function updateIdentifier(Request $request, Space $space, Node $node)
    {
        abort_unless($node->space_id === $space->id, 404);

        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $node->update(['identifier' => $data['identifier']]);

        return redirect()->back();
    }
}
