<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminTeatroController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CampusController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Admin\SpaceController;
use App\Http\Controllers\ReservationController;
use Laravel\Fortify\Features;
use App\Models\Event; // <-- IMPORTANTE: Importamos el modelo Event
use Inertia\Inertia;  // <-- IMPORTANTE: Importamos Inertia para el renderizado
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\TicketController;

// ==========================================
// RUTA RAÍZ DINÁMICA (Cartelera Pública)
// ==========================================
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        // Traemos todos los eventos con la información de su sala asociada y campus,
        // ordenados por fecha de inicio (del más próximo al más lejano).
        'events' => App\Models\Event::with('space.building.campus')->orderBy('start_time', 'asc')->get()
    ]);
})->name('home');

// ==========================================
// ZONA DE ADMINISTRACIÓN (Protegida)
// ==========================================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin/teatro')->name('admin.teatro.')->group(function () {
        Route::get('spaces/{space}/edit', [AdminTeatroController::class, 'edit'])->name('edit');
        Route::put('spaces/{space}/layout', [AdminTeatroController::class, 'updateLayout'])->name('layout.update');
        Route::patch('spaces/{space}/nodes/{node}/status', [AdminTeatroController::class, 'updateStatus'])->name('nodes.status');
        Route::patch('spaces/{space}/nodes/{node}/identifier', [AdminTeatroController::class, 'updateIdentifier'])->name('nodes.identifier');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('campuses', CampusController::class)->except(['show']);
        Route::resource('buildings', BuildingController::class)->except(['show']);
        Route::resource('events', EventController::class)->except(['show']);
        Route::get('events/{event}/taquilla', [\App\Http\Controllers\Admin\EventTaquillaController::class, 'show'])->name('events.taquilla.show');
        Route::put('events/{event}/taquilla/{seat}', [\App\Http\Controllers\Admin\EventTaquillaController::class, 'updateSeat'])->name('events.taquilla.updateSeat');
        Route::resource('spaces', SpaceController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['create', 'store', 'show']);
        Route::resource('roles', RoleController::class)->except(['show']);
        
        // Rutas de administración y validación de boletos
        Route::get('/boletos/escanear', [TicketController::class, 'escanear'])->name('boletos.escanear');
        Route::post('/boletos/validar-token', [TicketController::class, 'validarToken'])->name('boletos.validar-token');
        Route::get('/boletos/validar/{token}', [TicketController::class, 'validarDirecto'])->name('boletos.validar-directo');
    });
});

// ==========================================
// ZONA PÚBLICA (Compra de boletos dinámica)
// ==========================================
Route::get('/boletos/{token}', [TicketController::class, 'show'])->name('boletos.show');
Route::get('/espacios/{space}/eventos/{event}/reservar', [ReservationController::class, 'show'])->name('espacios.reservar.show');
Route::post('/espacios/reservar', [ReservationController::class, 'reservar'])->name('espacios.reservar');
Route::post('/espacios/reservar/apartar', [ReservationController::class, 'apartar'])->name('espacios.reservar.apartar');
Route::post('/espacios/reservar/liberar', [ReservationController::class, 'liberar'])->name('espacios.reservar.liberar');
Route::post('/espacios/reservar/confirmar', [ReservationController::class, 'confirmar'])->name('espacios.reservar.confirmar');
Route::post('/espacios/reservar/pagar', [ReservationController::class, 'pagar'])->name('espacios.reservar.pagar');

require __DIR__ . '/settings.php';
