<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\QrCode;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    /**
     * Vista pública del boleto digital.
     * Cualquier persona con el link /boletos/{token} puede verlo.
     */
    public function show(string $token)
    {
        $reservation = Reservation::where('ticket_token', $token)
            ->whereIn('status', ['confirmed', 'ingresado'])
            ->with(['event.space', 'node', 'payment'])
            ->firstOrFail();

        // Generar el SVG del QR apuntando a la URL de validación directa
        // La URL se genera con route() de Laravel: sin hardcodear nada.
        $validationUrl = route('admin.boletos.validar-directo', $token);
        $qrSvg = QrCode::size(250)->generate($validationUrl);

        return Inertia::render('Tickets/Show', [
            'reservation' => $reservation,
            'qrSvg'       => $qrSvg,
            'ticketPrice' => config('tickets.ticket_price_mxn'),
        ]);
    }

    /**
     * Panel móvil del validador con escáner de cámara continuo.
     * Requiere autenticación y permiso validate-tickets.
     */
    public function escanear()
    {
        Gate::authorize('validate-tickets');

        return Inertia::render('Admin/Tickets/Scanner');
    }

    /**
     * Endpoint AJAX llamado por el escáner continuo (html5-qrcode).
     * Retorna JSON con el resultado de la validación.
     */
    public function validarToken(Request $request)
    {
        Gate::authorize('validate-tickets');

        $request->validate(['token' => 'required|string']);

        $reservation = Reservation::where('ticket_token', $request->token)
            ->with(['event', 'node'])
            ->first();

        if (! $reservation || ! in_array($reservation->status, ['confirmed', 'ingresado'])) {
            return response()->json(['result' => 'invalido']);
        }

        if ($reservation->status === 'ingresado') {
            return response()->json([
                'result'       => 'ya_validado',
                'validated_at' => $reservation->validated_at?->format('d/m/Y H:i:s'),
                'seat'         => $reservation->node?->identifier,
                'name'         => $this->resolveGuestName($reservation),
            ]);
        }

        // Marcar como ingresado
        $reservation->update([
            'status'       => 'ingresado',
            'validated_at' => now(),
        ]);

        return response()->json([
            'result' => 'success',
            'seat'   => $reservation->node?->identifier,
            'event'  => $reservation->event?->name,
            'name'   => $this->resolveGuestName($reservation),
        ]);
    }

    /**
     * Validación directa por URL (el QR apunta aquí).
     * Útil para validar con la cámara nativa del celular sin app especial.
     */
    public function validarDirecto(string $token)
    {
        Gate::authorize('validate-tickets');

        $reservation = Reservation::where('ticket_token', $token)
            ->with(['event', 'node'])
            ->first();

        if (! $reservation || ! in_array($reservation->status, ['confirmed', 'ingresado'])) {
            return Inertia::render('Admin/Tickets/ValidationResult', [
                'result' => 'invalido',
            ]);
        }

        if ($reservation->status === 'ingresado') {
            return Inertia::render('Admin/Tickets/ValidationResult', [
                'result'       => 'ya_validado',
                'seat'         => $reservation->node?->identifier,
                'event'        => $reservation->event?->name,
                'name'         => $this->resolveGuestName($reservation),
                'validated_at' => $reservation->validated_at?->format('d/m/Y H:i:s'),
            ]);
        }

        $reservation->update([
            'status'       => 'ingresado',
            'validated_at' => now(),
        ]);

        return Inertia::render('Admin/Tickets/ValidationResult', [
            'result' => 'success',
            'seat'   => $reservation->node?->identifier,
            'event'  => $reservation->event?->name,
            'name'   => $this->resolveGuestName($reservation),
        ]);
    }

    /**
     * Obtiene el nombre del asistente (usuario registrado o invitado).
     */
    private function resolveGuestName(Reservation $reservation): string
    {
        if ($reservation->user) {
            return $reservation->user->name;
        }
        return $reservation->guest_info['name'] ?? 'Invitado';
    }
}
