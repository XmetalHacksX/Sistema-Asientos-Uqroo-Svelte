<?php

use App\Models\User;
use App\Models\Event;
use App\Models\EventSeat;
use App\Models\Space;
use App\Models\Node;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest can reserve a seat by providing contact details', function () {
    // 1. Setup Space, Event, Node, EventSeat
    $space = Space::create([
        'name' => 'Aula Magna',
        'capacity' => 100,
    ]);
    
    $event = Event::create([
        'space_id' => $space->id,
        'name' => 'Conferencia de Inteligencia Artificial',
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

    // 2. Perform the guest booking request
    $response = $this->post(route('espacios.reservar'), [
        'seat_id' => $seat->id,
        'event_id' => $event->id,
        'guest_name' => 'Invitado Especial',
        'guest_email' => 'invitado@correo.com',
        'guest_phone' => '9831234567',
    ]);

    // 3. Assertions
    $response->assertRedirect();
    
    // Seat status should be updated
    $seat->refresh();
    expect($seat->status)->toBe('reservado');
    expect($seat->user_id)->toBeNull();

    // Reservation should be created with guest info
    $reservation = Reservation::first();
    expect($reservation)->not->toBeNull();
    expect($reservation->event_id)->toBe($event->id);
    expect($reservation->user_id)->toBeNull();
    expect($reservation->guest_info)->toBe([
        'name' => 'Invitado Especial',
        'email' => 'invitado@correo.com',
        'phone' => '9831234567',
    ]);
});

test('a guest cannot reserve a seat without contact details', function () {
    $space = Space::create(['name' => 'Aula Magna', 'capacity' => 100]);
    $event = Event::create([
        'space_id' => $space->id,
        'name' => 'Conferencia',
        'start_time' => now()->addDays(2),
        'end_time' => now()->addDays(2)->addHours(2),
        'status' => 'active',
    ]);
    $seat = EventSeat::create([
        'event_id' => $event->id,
        'identifier' => 'A-1',
        'pos_x' => 10,
        'pos_y' => 20,
        'status' => 'disponible',
    ]);

    $response = $this->post(route('espacios.reservar'), [
        'seat_id' => $seat->id,
        'event_id' => $event->id,
    ]);

    $response->assertSessionHasErrors(['guest_name', 'guest_email', 'guest_phone']);
    expect(Reservation::count())->toBe(0);
});

test('an authenticated user can reserve a seat without guest details', function () {
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

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('espacios.reservar'), [
        'seat_id' => $seat->id,
        'event_id' => $event->id,
    ]);

    $response->assertRedirect();
    
    $seat->refresh();
    expect($seat->status)->toBe('reservado');
    expect($seat->user_id)->toBe($user->id);

    $reservation = Reservation::first();
    expect($reservation)->not->toBeNull();
    expect($reservation->user_id)->toBe($user->id);
    expect($reservation->guest_info)->toBeNull();
});
