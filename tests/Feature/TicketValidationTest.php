<?php

use App\Models\User;
use App\Models\Event;
use App\Models\Space;
use App\Models\Node;
use App\Models\EventSeat;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

// Helper para crear el escenario de validación de boletos
function createTicketScenario(): array
{
    // Crear roles/permisos necesarios
    Permission::findOrCreate('validate-tickets');
    $validadorRole = Role::findOrCreate('validador');
    $validadorRole->givePermissionTo('validate-tickets');

    $space = Space::create(['name' => 'Aula Magna', 'capacity' => 100]);
    $event = Event::create([
        'space_id'   => $space->id,
        'name'       => 'Gran Concierto de Gala',
        'start_time' => now()->addDays(3),
        'end_time'   => now()->addDays(3)->addHours(2),
        'status'     => 'active',
    ]);
    $node = Node::create([
        'space_id'   => $space->id,
        'identifier' => 'B-5',
        'type'       => 'seat',
        'pos_x'      => 15,
        'pos_y'      => 25,
    ]);
    $seat = EventSeat::create([
        'event_id'   => $event->id,
        'identifier' => 'B-5',
        'pos_x'      => 15,
        'pos_y'      => 25,
        'status'     => 'disponible',
    ]);

    // Crear un boleto confirmado (con token)
    $reservation = Reservation::create([
        'event_id'            => $event->id,
        'node_id'             => $node->id,
        'status'              => 'confirmed',
        'expires_at'          => null,
        'guest_info'          => ['name' => 'Ana Smith', 'email' => 'ana@example.com', 'phone' => '1234567890'],
    ]);

    return compact('space', 'event', 'node', 'seat', 'reservation');
}

test('boleto confirmado autogenera token unico', function () {
    ['reservation' => $reservation] = createTicketScenario();

    expect($reservation->ticket_token)->not->toBeNull();
    expect(strlen($reservation->ticket_token))->toBeGreaterThan(10);
});

test('vista publica del boleto digital es accesible sin autenticacion', function () {
    ['reservation' => $reservation] = createTicketScenario();

    $response = $this->get(route('boletos.show', $reservation->ticket_token));

    $response->assertStatus(200);
    // Debe incluir la reservación y el SVG en las props de Inertia
    $response->assertInertia(fn ($page) => $page
        ->component('Tickets/Show')
        ->has('reservation')
        ->has('qrSvg')
    );
});

test('vista publica del boleto retorna 404 para token inexistente', function () {
    $response = $this->get('/boletos/token-falso-123');
    $response->assertStatus(404);
});

test('validar token requiere autenticacion y permiso de validador', function () {
    ['reservation' => $reservation] = createTicketScenario();

    // 1. Sin autenticar -> Redirige a login
    $response = $this->postJson(route('admin.boletos.validar-token'), [
        'token' => $reservation->ticket_token,
    ]);
    $response->assertStatus(401);

    // 2. Autenticado pero sin permiso/rol -> 403 Forbidden
    $user = User::factory()->create();
    $response = $this->actingAs($user)->postJson(route('admin.boletos.validar-token'), [
        'token' => $reservation->ticket_token,
    ]);
    $response->assertStatus(403);
});

test('validador con permisos puede validar un boleto confirmado', function () {
    ['reservation' => $reservation] = createTicketScenario();
    
    $validador = User::factory()->create();
    $validador->assignRole('validador');

    $response = $this->actingAs($validador)->postJson(route('admin.boletos.validar-token'), [
        'token' => $reservation->ticket_token,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'result' => 'success',
        'seat'   => 'B-5',
        'name'   => 'Ana Smith',
    ]);

    // El estado en la base de datos debe actualizarse a ingresado
    $reservation->refresh();
    expect($reservation->status)->toBe('ingresado');
    expect($reservation->validated_at)->not->toBeNull();
});

test('segundo intento de validacion del mismo boleto retorna ya_validado', function () {
    ['reservation' => $reservation] = createTicketScenario();
    
    $validador = User::factory()->create();
    $validador->assignRole('validador');

    // Primera validación exitosa
    $this->actingAs($validador)->postJson(route('admin.boletos.validar-token'), [
        'token' => $reservation->ticket_token,
    ]);

    // Segunda validación
    $response = $this->actingAs($validador)->postJson(route('admin.boletos.validar-token'), [
        'token' => $reservation->ticket_token,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'result' => 'ya_validado',
        'seat'   => 'B-5',
        'name'   => 'Ana Smith',
    ]);
    expect($response->json('validated_at'))->not->toBeNull();
});

test('validacion directa por url redirige a vista de resultado', function () {
    ['reservation' => $reservation] = createTicketScenario();
    
    $validador = User::factory()->create();
    $validador->assignRole('validador');

    $response = $this->actingAs($validador)->get(route('admin.boletos.validar-directo', $reservation->ticket_token));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Tickets/ValidationResult')
        ->where('result', 'success')
        ->where('seat', 'B-5')
        ->where('name', 'Ana Smith')
    );
});
