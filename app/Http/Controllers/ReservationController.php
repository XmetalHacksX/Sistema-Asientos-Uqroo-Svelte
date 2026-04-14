<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Node;
use App\Models\Reservation;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReservationController extends Controller
{
    // ============================================================
    // INGENIERÍA: Cambiamos 'index' por 'show' y recibimos las variables
    // ============================================================
    public function show(Space $space, Event $event)
    {
        // Cargamos las relaciones para la sala que viene en la URL
        $space->load(['nodes.reservations', 'layoutObjects']);

        // Verificamos ocupación específica para EL evento que viene en la URL
        $space->nodes->transform(function ($node) use ($event) {
            $node->is_occupied = $node->reservations()
                ->where('event_id', $event->id)
                ->where('status', 'confirmed')
                ->exists()
                || ! in_array($node->status, ['active'], true);
            return $node;
        });

        // Enviamos el teatro y el evento a Svelte
        return Inertia::render('Reservations/Index', [
            'space' => $space,
            'event' => $event
        ]);
    }

    // Función para guardar la reserva (Se mantiene intacta)
    public function reservar(Request $request)
    {
        // 1. Validamos que nos manden datos correctos
        $request->validate([
            'node_id' => 'required|exists:nodes,id',
            'event_id' => 'required|exists:events,id',
        ]);

        $node = Node::query()->findOrFail($request->node_id);
        if ($node->status !== 'active') {
            return back()->withErrors(['message' => 'Este asiento no está disponible para reserva.']);
        }

        // 2. REGLA DE ORO: Verificamos si alguien más lo reservó en el último segundo
        $yaExiste = Reservation::where('event_id', $request->event_id)
            ->where('node_id', $request->node_id)
            ->exists();

        if ($yaExiste) {
            return back()->withErrors(['message' => '¡Lástima! Alguien acaba de ganar este asiento.']);
        }

        // 3. Guardamos la reserva en la Base de Datos
        Reservation::create([
            'event_id' => $request->event_id,
            'node_id' => $request->node_id,
            // Si el usuario no está logueado, le asignamos el ID 1 temporalmente para el MVP
            'user_id' => Auth::id() ?? 1,
            'status' => 'confirmed'
        ]);

        // 4. Recargamos la página (Inertia hará que el asiento se pinte de rojo automáticamente)
        return redirect()->back();
    }
}
