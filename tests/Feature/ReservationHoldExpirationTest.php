<?php

use App\Models\User;
use App\Models\Event;
use App\Models\EventSeat;
use App\Models\Space;
use App\Models\Node;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('a user can hold a seat temporarily', function () {
    $space = Space::create(['name' => 'Aula Magna', 'capacity' => 100]);
    $event = Event::create([
        'space_id' => $space->id,
        'name' => 'Conferencia',
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(2),
        'status' => 'active',
    ]);
    $node = Node::create([
        'space_id' => $space->id,
        'identifier' => 'A-1',
        'type' => 'seat',
        'pos_x' => 10,
        'pos_y' => 20,
    ]);
    $seat = EventSeat::create([
        'event_id' => $event->id,
        'identifier' => 'A-1',
        'pos_x' => 10,
        'pos_y' => 20,
        'status' => 'disponible',
    ]);

    $response = $this->postJson(route('espacios.reservar.apartar'), [
        'seat_id' => $seat->id,
        'event_id' => $event->id,
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['success', 'reservation_id', 'expires_at']);

    $seat->refresh();
    expect($seat->status)->toBe('reservado');

    $reservation = Reservation::first();
    expect($reservation->status)->toBe('pending');
    expect($reservation->expires_at)->not->toBeNull();
});

test('a user can release a temporary hold', function () {
    $space = Space::create(['name' => 'Aula Magna', 'capacity' => 100]);
    $event = Event::create([
        'space_id' => $space->id,
        'name' => 'Conferencia',
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(2),
        'status' => 'active',
    ]);
    $node = Node::create([
        'space_id' => $space->id,
        'identifier' => 'A-1',
        'type' => 'seat',
        'pos_x' => 10,
        'pos_y' => 20,
    ]);
    $seat = EventSeat::create([
        'event_id' => $event->id,
        'identifier' => 'A-1',
        'pos_x' => 10,
        'pos_y' => 20,
        'status' => 'disponible',
    ]);

    // Apartamos
    $holdResponse = $this->postJson(route('espacios.reservar.apartar'), [
        'seat_id' => $seat->id,
        'event_id' => $event->id,
    ]);
    $reservationId = $holdResponse['reservation_id'];

    // Liberamos
    $releaseResponse = $this->postJson(route('espacios.reservar.liberar'), [
        'reservation_id' => $reservationId,
    ]);
    $releaseResponse->assertOk();

    $seat->refresh();
    expect($seat->status)->toBe('disponible');
    expect(Reservation::count())->toBe(0);
});

test('expired holds are automatically self-healed on a new hold attempt', function () {
    $space = Space::create(['name' => 'Aula Magna', 'capacity' => 100]);
    $event = Event::create([
        'space_id' => $space->id,
        'name' => 'Conferencia',
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(2),
        'status' => 'active',
    ]);
    $node = Node::create([
        'space_id' => $space->id,
        'identifier' => 'A-1',
        'type' => 'seat',
        'pos_x' => 10,
        'pos_y' => 20,
    ]);
    $seat = EventSeat::create([
        'event_id' => $event->id,
        'identifier' => 'A-1',
        'pos_x' => 10,
        'pos_y' => 20,
        'status' => 'reservado', // Marcamos ocupado
    ]);

    // Creamos reserva pendiente que ya expiró hace 5 minutos
    Reservation::create([
        'event_id' => $event->id,
        'node_id' => $node->id,
        'status' => 'pending',
        'expires_at' => now()->subMinutes(5),
    ]);

    // Intentamos apartar el mismo asiento
    $response = $this->postJson(route('espacios.reservar.apartar'), [
        'seat_id' => $seat->id,
        'event_id' => $event->id,
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['success', 'reservation_id', 'expires_at']);

    // La reserva vieja debió eliminarse, dejando solo la nueva
    expect(Reservation::count())->toBe(1);
    
    $newReservation = Reservation::first();
    expect($newReservation->expires_at->isFuture())->toBeTrue();
});

test('expired holds are cleared by the clear expired reservations artisan command', function () {
    $space = Space::create(['name' => 'Aula Magna', 'capacity' => 100]);
    $event = Event::create([
        'space_id' => $space->id,
        'name' => 'Conferencia',
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(2),
        'status' => 'active',
    ]);
    $node1 = Node::create([
        'space_id' => $space->id,
        'identifier' => 'A-1',
        'type' => 'seat',
        'pos_x' => 10,
        'pos_y' => 20,
    ]);
    $node2 = Node::create([
        'space_id' => $space->id,
        'identifier' => 'A-2',
        'type' => 'seat',
        'pos_x' => 30,
        'pos_y' => 20,
    ]);
    
    $seat1 = EventSeat::create([
        'event_id' => $event->id,
        'identifier' => 'A-1',
        'pos_x' => 10,
        'pos_y' => 20,
        'status' => 'reservado',
    ]);
    $seat2 = EventSeat::create([
        'event_id' => $event->id,
        'identifier' => 'A-2',
        'pos_x' => 30,
        'pos_y' => 20,
        'status' => 'reservado',
    ]);

    // Reserva 1: Expirada
    Reservation::create([
        'event_id' => $event->id,
        'node_id' => $node1->id,
        'status' => 'pending',
        'expires_at' => now()->subMinutes(1),
    ]);

    // Reserva 2: Activa (No expirada)
    Reservation::create([
        'event_id' => $event->id,
        'node_id' => $node2->id,
        'status' => 'pending',
        'expires_at' => now()->addMinutes(9),
    ]);

    // Ejecutamos el comando
    Artisan::call('reservations:clear-expired');

    // Asiento 1 debe estar libre, Asiento 2 debe seguir ocupado
    $seat1->refresh();
    $seat2->refresh();
    expect($seat1->status)->toBe('disponible');
    expect($seat2->status)->toBe('reservado');

    // Solo debe quedar 1 reservación en la base de datos (la no expirada)
    expect(Reservation::count())->toBe(1);
    expect(Reservation::first()->node_id)->toBe($node2->id);
});

test('a user can confirm an active pending reservation', function () {
    $space = Space::create(['name' => 'Aula Magna', 'capacity' => 100]);
    $event = Event::create([
        'space_id' => $space->id,
        'name' => 'Conferencia',
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(2),
        'status' => 'active',
    ]);
    $node = Node::create([
        'space_id' => $space->id,
        'identifier' => 'A-1',
        'type' => 'seat',
        'pos_x' => 10,
        'pos_y' => 20,
    ]);
    $seat = EventSeat::create([
        'event_id' => $event->id,
        'identifier' => 'A-1',
        'pos_x' => 10,
        'pos_y' => 20,
        'status' => 'disponible',
    ]);

    // Apartamos
    $holdResponse = $this->postJson(route('espacios.reservar.apartar'), [
        'seat_id' => $seat->id,
        'event_id' => $event->id,
    ]);
    $reservationId = $holdResponse['reservation_id'];

    // Confirmamos como invitado
    $confirmResponse = $this->post(route('espacios.reservar.confirmar'), [
        'reservation_id' => $reservationId,
        'guest_name' => 'John Doe',
        'guest_email' => 'john@doe.com',
        'guest_phone' => '1234567890',
    ]);

    $confirmResponse->assertRedirect();

    $seat->refresh();
    expect($seat->status)->toBe('reservado');
    expect($seat->user_id)->toBeNull();

    $reservation = Reservation::first();
    expect($reservation->status)->toBe('confirmed');
    expect($reservation->expires_at)->toBeNull();
    expect($reservation->guest_info)->toBe([
        'name' => 'John Doe',
        'email' => 'john@doe.com',
        'phone' => '1234567890',
    ]);
});
