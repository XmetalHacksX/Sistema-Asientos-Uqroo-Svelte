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

// ==========================================
// RUTA RAÍZ DINÁMICA (Cartelera Pública)
// ==========================================
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        // Traemos todos los eventos con la información de su sala asociada,
        // ordenados por fecha de inicio (del más próximo al más lejano).
        'events' => Event::with('space')->orderBy('start_time', 'asc')->get()
    ]);
})->name('home');

// ==========================================
// ZONA DE ADMINISTRACIÓN (Protegida)
// ==========================================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

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
        Route::resource('spaces', SpaceController::class)->except(['show']);
    });
});

// ==========================================
// ZONA PÚBLICA (Compra de boletos dinámica)
// ==========================================
Route::get('/espacios/{space}/eventos/{event}/reservar', [ReservationController::class, 'show'])->name('espacios.reservar.show');
Route::post('/espacios/reservar', [ReservationController::class, 'reservar'])->name('espacios.reservar');

require __DIR__ . '/settings.php';
