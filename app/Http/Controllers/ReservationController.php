<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\Space;
use App\Services\ReservationManager;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Exception;

class ReservationController extends Controller
{
    protected ReservationManager $reservationManager;
    protected StripePaymentService $stripePaymentService;

    public function __construct(
        ReservationManager $reservationManager,
        StripePaymentService $stripePaymentService
    ) {
        $this->reservationManager = $reservationManager;
        $this->stripePaymentService = $stripePaymentService;
    }

    public function show(Space $space, Event $event)
    {
        $space->load(['layoutObjects']);

        $eventSeats = \App\Models\EventSeat::where('event_id', $event->id)->get();

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

        $rules = [
            'seat_id'  => 'required|exists:event_seats,id',
            'event_id' => 'required|exists:events,id',
        ];

        if ($isGuest) {
            $rules['guest_name']  = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
            $rules['guest_phone'] = 'required|string|max:20';
        }

        $request->validate($rules);

        $guestInfo = null;
        if ($isGuest) {
            $guestInfo = [
                'name'  => $request->guest_name,
                'email' => $request->guest_email,
                'phone' => $request->guest_phone,
            ];
        }

        try {
            $this->reservationManager->reserveDirectly(
                $request->seat_id,
                $request->event_id,
                $guestInfo
            );
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function apartar(Request $request)
    {
        $request->validate([
            'seat_id'  => 'required|exists:event_seats,id',
            'event_id' => 'required|exists:events,id',
        ]);

        $expiresAt = now()->addMinutes(10);

        try {
            $reservation = $this->reservationManager->holdSeat(
                $request->seat_id,
                $request->event_id,
                $expiresAt
            );
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'        => true,
            'reservation_id' => $reservation->id,
            'expires_at'     => $expiresAt->toIso8601String(),
        ]);
    }

    public function liberar(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
        ]);

        try {
            $this->reservationManager->releaseReservation($request->reservation_id);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function confirmar(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
        ]);

        $isGuest = !Auth::check();
        $rules = [];
        if ($isGuest) {
            $rules['guest_name']  = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
            $rules['guest_phone'] = 'required|string|max:20';
        }

        $request->validate($rules);

        $guestInfo = null;
        if ($isGuest) {
            $guestInfo = [
                'name'  => $request->guest_name,
                'email' => $request->guest_email,
                'phone' => $request->guest_phone,
            ];
        }

        try {
            $this->reservationManager->confirmReservation($request->reservation_id, $guestInfo);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return redirect()->back();
    }

    public function pagar(Request $request)
    {
        $isGuest = !Auth::check();

        $rules = [
            'reservation_id'    => 'required|exists:reservations,id',
            'payment_method_id' => 'required|string|starts_with:pm_',
        ];

        if ($isGuest) {
            $rules['guest_name']  = 'required|string|max:255';
            $rules['guest_email'] = 'required|email|max:255';
            $rules['guest_phone'] = 'required|string|max:20';
        }

        $request->validate($rules);

        $reservation = Reservation::findOrFail($request->reservation_id);

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

        $priceMxn       = config('tickets.ticket_price_mxn');
        $amountCents    = $priceMxn * 100;
        $amountDecimal  = (float) $priceMxn;

        try {
            $paymentIntent = $this->stripePaymentService->charge(
                $amountCents,
                $request->payment_method_id,
                "Boleto: Asiento para el evento #{$reservation->event_id}"
            );
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($paymentIntent->status !== 'succeeded') {
            return response()->json([
                'message' => 'El pago no fue completado. Estado: ' . $paymentIntent->status
            ], 422);
        }

        $guestInfo = null;
        if ($isGuest) {
            $guestInfo = [
                'name'  => $request->guest_name,
                'email' => $request->guest_email,
                'phone' => $request->guest_phone,
            ];
        }

        try {
            $payment = $this->reservationManager->completePaymentAndConfirm(
                $reservation,
                $paymentIntent->id,
                $amountDecimal,
                $guestInfo
            );
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al registrar la confirmación del pago en la base de datos.'], 500);
        }

        return response()->json([
            'success'      => true,
            'payment_id'   => $payment->id,
            'ticket_token' => $reservation->ticket_token,
            'message'      => '¡Pago exitoso! Tu boleto ha sido confirmado.',
        ]);
    }
}
