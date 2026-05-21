<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Space;
use App\Models\Node;
use App\Models\EventSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // <-- IMPORTANTE: Añadimos Storage
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Events/Index', [
            'events' => Event::query()
                ->with('space')
                ->orderByDesc('start_time')
                ->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Events/Create', [
            'spaces' => Space::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'space_id' => ['required', 'integer', 'exists:spaces,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'poster' => ['nullable', 'image', 'max:2048'], // <-- Validamos que sea imagen y máximo 2MB
            'start_time' => ['required', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
        ]);

        // Guardamos la imagen si el usuario subió una
        if ($request->hasFile('poster')) {
            $path = $request->file('poster')->store('posters', 'public');
            $data['poster_url'] = '/storage/' . $path;
        }

        $event = Event::create($data);

        // Clonación de la plantilla de asientos del espacio seleccionado hacia el evento
        $nodes = Node::where('space_id', $event->space_id)->get();
        $seatsToInsert = [];
        $now = now();
        
        foreach ($nodes as $node) {
            // Solo clonamos aquellos asientos que estén activos en el layout original, o todos según regla genérica.
            // La instrucción dice: "Esta tabla debe tener EXACTAMENTE las mismas columnas... clonar esos asientos originales"
            $seatsToInsert[] = [
                'event_id' => $event->id,
                'user_id' => null,
                'identifier' => $node->identifier,
                'pos_x' => $node->pos_x,
                'pos_y' => $node->pos_y,
                'status' => 'disponible',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($seatsToInsert)) {
            EventSeat::insert($seatsToInsert);
        }

        return redirect()->route('admin.events.index');
    }

    public function edit(Event $event)
    {
        $event->load('space');

        return Inertia::render('Admin/Events/Edit', [
            'event' => $event,
            'spaces' => Space::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $data = $request->validate([
            'space_id' => ['required', 'integer', 'exists:spaces,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'poster' => ['nullable', 'image', 'max:2048'], // <-- Validamos imagen
            'start_time' => ['required', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
        ]);

        // Si el usuario sube un nuevo póster
        if ($request->hasFile('poster')) {
            // Borramos el viejo si existía
            if ($event->poster_url) {
                $oldPath = str_replace('/storage/', '', $event->poster_url);
                Storage::disk('public')->delete($oldPath);
            }
            // Guardamos el nuevo
            $path = $request->file('poster')->store('posters', 'public');
            $data['poster_url'] = '/storage/' . $path;
        }

        $event->update($data);

        return redirect()->route('admin.events.index');
    }

    public function destroy(Event $event)
    {
        // Borramos la imagen del disco duro antes de eliminar el evento
        if ($event->poster_url) {
            $oldPath = str_replace('/storage/', '', $event->poster_url);
            Storage::disk('public')->delete($oldPath);
        }

        $event->delete();

        return redirect()->route('admin.events.index');
    }
}
