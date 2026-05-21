<?php

use App\Models\User;
use App\Models\Event;
use App\Models\EventSeat;
use App\Models\Space;
use App\Models\Node;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

// Helper para crear la estructura de espacio, evento, nodo y asiento
function createSeatScenario(): array
{
    $space = Space::create(['name' => 'Aula Magna', 'capacity' => 100]);
    $event = Event::create([
        'space_id'   => $space->id,
        'name'       => 'Gran Concierto de Prueba',
        'start_time' => now()->addDays(3),
        'end_time'   => now()->addDays(3)->addHours(2),
        'status'     => 'active',
    ]);
    $node = Node::create([
        'space_id'   => $space->id,
        'identifier' => 'A-1',
        'type'       => 'seat',
        'pos_x'      => 10,
        'pos_y'      => 20,
    ]);
    $seat = EventSeat::create([
        'event_id'   => $event->id,
        'identifier' => 'A-1',
        'pos_x'      => 10,
        'pos_y'      => 20,
        'status'     => 'disponible',
    ]);

    return compact('space', 'event', 'node', 'seat');
}

test('pagar endpoint rechaza payment_method_id inválido', function () {
    ['event' => $event, 'seat' => $seat, 'node' => $node] = createSeatScenario();

    // Creamos hold pendiente manualmente
    $reservation = Reservation::create([
        'event_id'  => $event->id,
        'node_id'   => $node->id,
        'status'    => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson(route('espacios.reservar.pagar'), [
        'reservation_id'    => $reservation->id,
        'payment_method_id' => 'tok_invalido', // no comienza con pm_
        'guest_name'        => 'Juan Test',
        'guest_email'       => 'juan@test.com',
        'guest_phone'       => '9831234567',
    ]);

    $response->assertStatus(422);
    expect(Payment::count())->toBe(0);
});

test('pagar endpoint rechaza un hold ya expirado', function () {
    ['event' => $event, 'seat' => $seat, 'node' => $node] = createSeatScenario();

    // Hold que ya venció
    $reservation = Reservation::create([
        'event_id'   => $event->id,
        'node_id'    => $node->id,
        'status'     => 'pending',
        'expires_at' => now()->subMinutes(5),
    ]);

    $response = $this->postJson(route('espacios.reservar.pagar'), [
        'reservation_id'    => $reservation->id,
        'payment_method_id' => 'pm_card_visa', // formato válido pero hold expirado
        'guest_name'        => 'Juan Test',
        'guest_email'       => 'juan@test.com',
        'guest_phone'       => '9831234567',
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment(['message' => 'Tu tiempo de apartado ha expirado. Por favor selecciona el asiento de nuevo.']);
    expect(Payment::count())->toBe(0);
});

test('pagar requiere datos de invitado si no está autenticado', function () {
    ['event' => $event, 'node' => $node] = createSeatScenario();

    $reservation = Reservation::create([
        'event_id'   => $event->id,
        'node_id'    => $node->id,
        'status'     => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    // Intento sin datos de invitado (sin estar autenticado)
    $response = $this->postJson(route('espacios.reservar.pagar'), [
        'reservation_id'    => $reservation->id,
        'payment_method_id' => 'pm_card_visa',
        // guest_name, guest_email, guest_phone ausentes
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['guest_name', 'guest_email', 'guest_phone']);
    expect(Payment::count())->toBe(0);
});

test('pagar no requiere datos de invitado si el usuario está autenticado', function () {
    ['event' => $event, 'node' => $node] = createSeatScenario();
    $user = User::factory()->create();

    $reservation = Reservation::create([
        'event_id'   => $event->id,
        'node_id'    => $node->id,
        'status'     => 'pending',
        'expires_at' => now()->addMinutes(10),
    ]);

    // Usuario autenticado no necesita guest_name/email/phone
    // Nota: El endpoint llamaría a Stripe real con pm_card_visa, por eso solo verificamos
    // que la validación del servidor no rechace la petición por falta de datos de invitado.
    // En tests unitarios de integración real se usaría Mockery o un Stripe mock server.
    $response = $this->actingAs($user)->postJson(route('espacios.reservar.pagar'), [
        'reservation_id'    => $reservation->id,
        'payment_method_id' => 'pm_card_visa',
        // Sin guest_name/email/phone — no debe fallar por eso
    ]);

    // Solo verificamos que el error NO sea de validación de invitado
    // (puede fallar con 422/500 de Stripe, pero no por datos de invitado ausentes)
    $response->assertJsonMissingValidationErrors(['guest_name', 'guest_email', 'guest_phone']);
});

test('pagar rechaza una reservación que ya fue confirmada', function () {
    ['event' => $event, 'node' => $node] = createSeatScenario();

    // Reservación ya confirmada (no pending)
    $reservation = Reservation::create([
        'event_id'   => $event->id,
        'node_id'    => $node->id,
        'status'     => 'confirmed',
        'expires_at' => null,
    ]);

    $response = $this->postJson(route('espacios.reservar.pagar'), [
        'reservation_id'    => $reservation->id,
        'payment_method_id' => 'pm_card_visa',
        'guest_name'        => 'Juan Test',
        'guest_email'       => 'juan@test.com',
        'guest_phone'       => '9831234567',
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment(['message' => 'Esta reservación ya fue confirmada o no existe.']);
    expect(Payment::count())->toBe(0);
});
