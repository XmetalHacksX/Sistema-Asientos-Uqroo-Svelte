<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Space;
use App\Models\Event; // <-- IMPORTANTE: Importar el modelo Event
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TheaterLayoutSeeder extends Seeder
{
    public function run(): void
    {
        $building = Building::firstOrCreate(
            ['name' => 'Campus Central UQROO'],
            [
                'pos_x' => 0,
                'pos_y' => 0,
                'width' => 1200,
                'height' => 900,
            ]
        );

        $space = Space::firstOrCreate(
            ['name' => 'Teatro UQROO'],
            [
                'building_id' => $building->id,
                'viewport' => ['zoom' => 1],
            ]
        );

        if (! $space->building_id) {
            $space->update(['building_id' => $building->id]);
        }

        // Llamamos al seeder de los asientos
        $this->call(NodeSeeder::class, false, ['space' => $space]);

        // ==========================================
        // NUEVO: CREAR UN EVENTO DE PRUEBA
        // ==========================================
        $event = Event::updateOrCreate(
            ['name' => 'Gran Estreno MVP'], // Buscamos por nombre
            [
                'space_id' => $space->id, // Lo enlazamos a la sala que acabamos de crear
                'description' => 'Primer evento de prueba para validar el sistema de reservas y asientos interactivos de la UQROO.',
                'poster_url' => 'https://via.placeholder.com/150x220/1e293b/ffffff?text=POSTER', // Imagen de relleno
                'start_time' => Carbon::now()->addDays(5)->setTime(18, 0), // Empieza en 5 días a las 6:00 PM
                'end_time' => Carbon::now()->addDays(5)->setTime(20, 30),  // Termina a las 8:30 PM
            ]
        );

        // Limpiamos y re-clonamos los asientos del evento desde los nuevos nodos del espacio
        \App\Models\EventSeat::where('event_id', $event->id)->delete();
        
        $nodes = \App\Models\Node::where('space_id', $space->id)->get();
        $seatsToInsert = [];
        $now = now();
        
        foreach ($nodes as $node) {
            $seatsToInsert[] = [
                'event_id' => $event->id,
                'user_id' => null,
                'identifier' => $node->identifier,
                'pos_x' => $node->pos_x,
                'pos_y' => $node->pos_y,
                'status' => 'disponible',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($seatsToInsert)) {
            \App\Models\EventSeat::insert($seatsToInsert);
        }

        $this->command?->info('Edificio, espacio, nodos y evento del teatro listos.');
    }
}
