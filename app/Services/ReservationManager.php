<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Node;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReservationManager
{
    /**
     * Reservar un asiento directamente de forma transaccional.
     */
    public function reserveDirectly(int $seatId, int $eventId, ?array $guestInfo = null)
    {
        $reservation = DB::transaction(function () use ($seatId, $eventId, $guestInfo) {
            $userId = Auth::id(); // null para invitados
            
            $seat = \App\Models\EventSeat::findOrFail($seatId);
            $seat->update([
                'user_id' => $userId,
                'status' => 'reservado'
            ]);

            $event = Event::findOrFail($eventId);
            $node = Node::where('space_id', $event->space_id)
                ->where('identifier', $seat->identifier)
                ->first();

            return Reservation::create([
                'event_id' => $event->id,
                'node_id' => $node ? $node->id : null,
                'user_id' => $userId,
                'reserved_by_user_id' => Auth::id(),
                'status' => 'confirmed',
                'guest_info' => $guestInfo,
                'expires_at' => null,
            ]);
        });

        if ($reservation) {
            $seat = \App\Models\EventSeat::findOrFail($seatId);
            event(new \App\Events\SeatStatusUpdated($eventId, $seat->identifier, 'reservado'));
            event(new \App\Events\DashboardUpdated());
        }

        return $reservation;
    }

    /**
     * Apartar temporalmente un asiento.
     */
    public function holdSeat(int $seatId, int $eventId, $expiresAt)
    {
        $reservation = DB::transaction(function () use ($seatId, $eventId, $expiresAt) {
            $seat = \App\Models\EventSeat::findOrFail($seatId);
            $event = Event::findOrFail($eventId);
            $node = Node::where('space_id', $event->space_id)
                ->where('identifier', $seat->identifier)
                ->first();

            // Lógica de auto-limpieza en caliente (Self-healing)
            if ($seat->status !== 'disponible') {
                $expiredReservation = null;
                if ($node) {
                    $expiredReservation = Reservation::where('event_id', $event->id)
                        ->where('node_id', $node->id)
                        ->where('status', 'pending')
                        ->where('expires_at', '<', now())
                        ->first();
                }

                if ($expiredReservation) {
                    $expiredReservation->delete();
                    $seat->update([
                        'status' => 'disponible',
                        'user_id' => null,
                    ]);
                } else {
                    throw new \Exception('¡Lástima! Alguien acaba de ganar este asiento o está bloqueado.');
                }
            }

            $userId = Auth::id();
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
                'guest_info' => null,
                'expires_at' => $expiresAt,
            ]);
        });

        if ($reservation) {
            $seat = \App\Models\EventSeat::findOrFail($seatId);
            event(new \App\Events\SeatStatusUpdated($eventId, $seat->identifier, 'reservado'));
        }

        return $reservation;
    }

    /**
     * Liberar un asiento apartado.
     */
    public function releaseReservation(int $reservationId)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $eventId = $reservation->event_id;
        $seatIdentifier = null;

        if ($reservation->node_id) {
            $node = Node::find($reservation->node_id);
            if ($node) {
                $seatIdentifier = $node->identifier;
            }
        }

        DB::transaction(function () use ($reservation) {
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

        if ($seatIdentifier) {
            event(new \App\Events\SeatStatusUpdated($eventId, $seatIdentifier, 'disponible'));
            event(new \App\Events\DashboardUpdated());
        }
    }

    /**
     * Confirmar una reservación pendiente de forma manual.
     */
    public function confirmReservation(int $reservationId, ?array $guestInfo = null)
    {
        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->status !== 'pending' || ($reservation->expires_at && $reservation->expires_at->isPast())) {
            throw new \Exception('Esta reservación ha expirado o ya no está disponible.');
        }

        $seatIdentifier = null;
        if ($reservation->node_id) {
            $node = Node::find($reservation->node_id);
            if ($node) {
                $seatIdentifier = $node->identifier;
            }
        }

        DB::transaction(function () use ($reservation, $guestInfo) {
            $userId = Auth::id();

            $reservation->update([
                'user_id' => $userId,
                'reserved_by_user_id' => Auth::id(),
                'status' => 'confirmed',
                'guest_info' => $guestInfo,
                'expires_at' => null,
            ]);

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

        if ($seatIdentifier) {
            event(new \App\Events\SeatStatusUpdated($reservation->event_id, $seatIdentifier, 'reservado'));
            event(new \App\Events\DashboardUpdated());
        }
    }

    /**
     * Registrar el pago completado y confirmar la reservación de forma transaccional.
     */
    public function completePaymentAndConfirm(Reservation $reservation, string $stripePaymentIntentId, float $amountDecimal, ?array $guestInfo = null)
    {
        $payment = DB::transaction(function () use ($reservation, $stripePaymentIntentId, $amountDecimal, $guestInfo) {
            $userId = Auth::id();

            // 1. Crear registro del pago
            $payment = Payment::create([
                'reservation_id'          => $reservation->id,
                'amount'                  => $amountDecimal,
                'status'                  => 'completed',
                'stripe_payment_intent_id' => $stripePaymentIntentId,
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

        if ($payment && $reservation->node_id) {
            $node = Node::find($reservation->node_id);
            if ($node) {
                event(new \App\Events\SeatStatusUpdated($reservation->event_id, $node->identifier, 'reservado'));
            }
            event(new \App\Events\DashboardUpdated());
        }

        return $payment;
    }
}
