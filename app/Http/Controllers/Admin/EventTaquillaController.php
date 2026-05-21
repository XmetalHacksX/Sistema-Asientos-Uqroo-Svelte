<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSeat;
use App\Models\User;
use App\Models\Reservation;
use App\Models\Node;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EventTaquillaController extends Controller
{
    public function show(Event $event)
    {
        // Load the space and layout objects for the map rendering
        $event->load(['space.layoutObjects']);
        
        // Load all seats for this event, including assigned user relation if any.
        $eventSeats = EventSeat::where('event_id', $event->id)->with('user:id,name,email')->get();
        
        // Load all users to allow admin to assign them
        $users = User::select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Events/Taquilla', [
            'event' => $event,
            'space' => $event->space,
            'seats' => $eventSeats,
            'users' => $users,
        ]);
    }

    public function updateSeat(Request $request, Event $event, EventSeat $seat)
    {
        $request->validate([
            'status' => 'required|string|in:disponible,bloqueado,reservado',
            'user_id' => 'nullable|exists:users,id',
        ]);

        if ($seat->event_id !== $event->id) {
            abort(404, 'Asiento no pertenece al evento especificado.');
        }

        \DB::transaction(function () use ($request, $event, $seat) {
            $seat->update([
                'status' => $request->status,
                'user_id' => $request->user_id,
            ]);

            // Sincronizar con la tabla de reservaciones transaccionales
            $node = Node::where('space_id', $event->space_id)
                ->where('identifier', $seat->identifier)
                ->first();

            if ($node) {
                if ($request->status === 'reservado') {
                    Reservation::updateOrCreate(
                        ['event_id' => $event->id, 'node_id' => $node->id],
                        [
                            'user_id' => $request->user_id ?? Auth::id() ?? 1,
                            'reserved_by_user_id' => Auth::id(),
                            'status' => 'confirmed',
                        ]
                    );
                } else {
                    Reservation::where('event_id', $event->id)
                        ->where('node_id', $node->id)
                        ->delete();
                }
            }
        });

        return redirect()->back();
    }
}
