<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Node;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Stripe\StripeClient;

class ReservationController extends Controller
{
    public function show(Space $space, Event $event)
    {
        // Cargamos las relaciones para la sala que viene en la URL (solo objetos de diseño)
        $space->load(['layoutObjects']);

        // Obtenemos la matriz clonada de asientos independiente del plano maestro
        $eventSeats = \App\Models\EventSeat::where('event_id', $event->id)->get();

        // Enviamos el teatro, el evento, los asientos y la configuración al frontend
        return Inertia::render('Reservations/Index', [
            'space'       => $space,
            'event'       => $event,
            'seats'       => $eventSeats,
            'stripeKey'   => config('cashier.key'),
            'ticketPrice' => config('tickets.ticket_price_mxn'),
        ]);
    }

    public function reservar(Request $request)
    {
        $isGuest = !Auth::check();

        // 1. Validamos que nos manden datos requeridos
        $rules = [
            'seat_id' => 'required|exists:event_seats,id',
            'event_id' => 'required|exists:events,id',
        ];

        if ($isGuest) {
            $rules['guest_name'] = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
            $rules['guest_phone'] = 'required|string|max:20';
        }

        $request->validate($rules);

        // 2. Buscamos el asiento en la matriz clonada
        $seat = \App\Models\EventSeat::findOrFail($request->seat_id);

        if ($seat->status !== 'disponible') {
            return back()->withErrors(['message' => '¡Lástima! Alguien acaba de ganar este asiento o está bloqueado.']);
        }

        // 3. Reservamos el asiento directamente y creamos el registro de reservación transaccional
        \DB::transaction(function () use ($request, $seat, $isGuest) {
            $userId = Auth::id(); // Será null para invitados

            $seat->update([
                'user_id' => $userId,
                'status' => 'reservado'
            ]);

            // Buscar el nodo físico correspondiente en el espacio del evento
            $event = Event::findOrFail($request->event_id);
            $node = Node::where('space_id', $event->space_id)
                ->where('identifier', $seat->identifier)
                ->first();

            $guestInfo = null;
            if ($isGuest) {
                $guestInfo = [
                    'name' => $request->guest_name,
                    'email' => $request->guest_email,
                    'phone' => $request->guest_phone,
                ];
            }

            Reservation::create([
                'event_id' => $event->id,
                'node_id' => $node ? $node->id : null,
                'user_id' => $userId,
                'reserved_by_user_id' => Auth::id(),
                'status' => 'confirmed',
                'guest_info' => $guestInfo,
                'expires_at' => null,
            ]);
        });

        // 4. Recargamos la página
        return redirect()->back();
    }

    public function apartar(Request $request)
    {
        $request->validate([
            'seat_id' => 'required|exists:event_seats,id',
            'event_id' => 'required|exists:events,id',
        ]);

        $seat = \App\Models\EventSeat::findOrFail($request->seat_id);
        $event = Event::findOrFail($request->event_id);
        $node = Node::where('space_id', $event->space_id)
            ->where('identifier', $seat->identifier)
            ->first();

        if ($seat->status !== 'disponible') {
            // Lógica de auto-limpieza en caliente (Self-healing)
            $expiredReservation = null;
            if ($node) {
                $expiredReservation = Reservation::where('event_id', $event->id)
                    ->where('node_id', $node->id)
                    ->where('status', 'pending')
                    ->where('expires_at', '<', now())
                    ->first();
            }

            if ($expiredReservation) {
                \DB::transaction(function () use ($expiredReservation, $seat) {
                    $expiredReservation->delete();
                    $seat->update([
                        'status' => 'disponible',
                        'user_id' => null,
                    ]);
                });
            } else {
                return response()->json([
                    'message' => '¡Lástima! Alguien acaba de ganar este asiento o está bloqueado.'
                ], 422);
            }
        }

        // Crear el hold pendiente (10 minutos)
        $expiresAt = now()->addMinutes(10);
        $reservation = \DB::transaction(function () use ($seat, $event, $node, $expiresAt) {
            $userId = Auth::id(); // null para invitados
            
            $seat->update([
                'status' => 'reservado',
                'user_id' => $userId,
            ]);

            return Reservation::create([
                'event_id' => $event->id,
                'node_id' => $node ? $node->id : null,
                'user_id' => $userId,
                'reserved_by_user_id' => Auth::id(),
                'status' => 'pending',
                'guest_info' => null, // se rellenará en la confirmación
                'expires_at' => $expiresAt,
            ]);
        });

        return response()->json([
            'success' => true,
            'reservation_id' => $reservation->id,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function liberar(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
        ]);

        $reservation = Reservation::findOrFail($request->reservation_id);

        \DB::transaction(function () use ($reservation) {
            if ($reservation->node_id) {
                $node = Node::find($reservation->node_id);
                if ($node) {
                    $seat = \App\Models\EventSeat::where('event_id', $reservation->event_id)
                        ->where('identifier', $node->identifier)
                        ->first();
                    if ($seat) {
                        $seat->update([
                            'status' => 'disponible',
                            'user_id' => null,
                        ]);
                    }
                }
            }
            $reservation->delete();
        });

        return response()->json(['success' => true]);
    }

    public function confirmar(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
        ]);

        $reservation = Reservation::findOrFail($request->reservation_id);

        if ($reservation->status !== 'pending' || ($reservation->expires_at && $reservation->expires_at->isPast())) {
            return back()->withErrors(['message' => 'Esta reservación ha expirado o ya no está disponible.']);
        }

        $isGuest = !Auth::check();
        $rules = [];
        if ($isGuest) {
            $rules['guest_name'] = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
            $rules['guest_phone'] = 'required|string|max:20';
        }

        $request->validate($rules);

        \DB::transaction(function () use ($request, $reservation, $isGuest) {
            $userId = Auth::id(); // null para invitados
            $guestInfo = null;

            if ($isGuest) {
                $guestInfo = [
                    'name' => $request->guest_name,
                    'email' => $request->guest_email,
                    'phone' => $request->guest_phone,
                ];
            }

            $reservation->update([
                'user_id' => $userId,
                'reserved_by_user_id' => Auth::id(),
                'status' => 'confirmed',
                'guest_info' => $guestInfo,
                'expires_at' => null,
            ]);

            // Aseguramos consistencia del asiento
            if ($reservation->node_id) {
                $node = Node::find($reservation->node_id);
                if ($node) {
                    $seat = \App\Models\EventSeat::where('event_id', $reservation->event_id)
                        ->where('identifier', $node->identifier)
                        ->first();
                    if ($seat) {
                        $seat->update([
                            'status' => 'reservado',
                            'user_id' => $userId,
                        ]);
                    }
                }
            }
        });

        return redirect()->back();
    }

    public function pagar(Request $request)
    {
        $isGuest = !Auth::check();

        // Validaciones base
        $rules = [
            'reservation_id'   => 'required|exists:reservations,id',
            'payment_method_id' => 'required|string|starts_with:pm_',
        ];

        // Datos de contacto del invitado
        if ($isGuest) {
            $rules['guest_name']  = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
            $rules['guest_phone'] = 'required|string|max:20';
        }

        $request->validate($rules);

        $reservation = Reservation::findOrFail($request->reservation_id);

        // Verificar que el hold siga activo y en estado pendiente
        if ($reservation->status !== 'pending') {
            return response()->json([
                'message' => 'Esta reservación ya fue confirmada o no existe.'
            ], 422);
        }

        if ($reservation->expires_at && $reservation->expires_at->isPast()) {
            return response()->json([
                'message' => 'Tu tiempo de apartado ha expirado. Por favor selecciona el asiento de nuevo.'
            ], 422);
        }

        // Precio configurado en config/tickets.php (env: TICKET_PRICE_MXN)
        $priceMxn    = config('tickets.ticket_price_mxn'); // ej. 150
        $amountCents = $priceMxn * 100;                    // Stripe requiere centavos
        $amountDecimal = (float) $priceMxn;

        // Procesar cargo con Stripe (clave secreta desde config/cashier.php)
        $stripe = new StripeClient(config('cashier.secret'));

        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount'               => $amountCents,
                'currency'             => 'mxn',
                'payment_method'       => $request->payment_method_id,
                'confirm'              => true,
                'automatic_payment_methods' => [
                    'enabled'          => true,
                    'allow_redirects'  => 'never',
                ],
                'description'          => "Boleto: Asiento para el evento #{$reservation->event_id}",
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            return response()->json([
                'message' => 'Tu tarjeta fue declinada: ' . $e->getError()->message
            ], 422);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'message' => 'Error al procesar el pago con Stripe. Por favor intenta de nuevo.'
            ], 500);
        }

        // Si el PaymentIntent no está en estado 'succeeded', algo falló
        if ($paymentIntent->status !== 'succeeded') {
            return response()->json([
                'message' => 'El pago no fue completado. Estado: ' . $paymentIntent->status
            ], 422);
        }

        // Transacción atómica: registrar pago y confirmar reservación
        $payment = \DB::transaction(function () use ($request, $reservation, $paymentIntent, $amountDecimal, $isGuest) {
            $userId = Auth::id();

            $guestInfo = null;
            if ($isGuest) {
                $guestInfo = [
                    'name'  => $request->guest_name,
                    'email' => $request->guest_email,
                    'phone' => $request->guest_phone,
                ];
            }

            // 1. Crear registro del pago
            $payment = Payment::create([
                'reservation_id'          => $reservation->id,
                'amount'                  => $amountDecimal,
                'status'                  => 'completed',
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            // 2. Confirmar la reservación permanentemente
            $reservation->update([
                'user_id'             => $userId,
                'reserved_by_user_id' => $userId,
                'status'              => 'confirmed',
                'guest_info'          => $guestInfo,
                'expires_at'          => null,
            ]);

            // 3. Asegurar consistencia del asiento en event_seats
            if ($reservation->node_id) {
                $node = Node::find($reservation->node_id);
                if ($node) {
                    $seat = \App\Models\EventSeat::where('event_id', $reservation->event_id)
                        ->where('identifier', $node->identifier)
                        ->first();
                    if ($seat) {
                        $seat->update([
                            'status'  => 'reservado',
                            'user_id' => $userId,
                        ]);
                    }
                }
            }

            return $payment;
        });

        return response()->json([
            'success'      => true,
            'payment_id'   => $payment->id,
            'ticket_token' => $reservation->ticket_token,
            'message'      => '¡Pago exitoso! Tu boleto ha sido confirmado.',
        ]);
    }
}
