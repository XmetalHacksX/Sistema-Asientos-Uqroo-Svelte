<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\EventSeat;
use App\Models\Node;
use Illuminate\Support\Facades\DB;

class ClearExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:clear-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Libera los asientos de las reservaciones pendientes que han expirado (10 minutos)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::transaction(function () {
            // Buscamos reservaciones pendientes que hayan superado su tiempo límite
            $expiredReservations = Reservation::where('status', 'pending')
                ->where('expires_at', '<', now())
                ->get();

            $count = 0;
            foreach ($expiredReservations as $reservation) {
                if ($reservation->node_id) {
                    $node = Node::find($reservation->node_id);
                    if ($node) {
                        // Buscamos el asiento correspondiente para el evento
                        $seat = EventSeat::where('event_id', $reservation->event_id)
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
                
                // Eliminamos el registro de la reservación expirada
                $reservation->delete();
                $count++;
            }

            if ($count > 0) {
                $this->info("Se han liberado {$count} reservaciones expiradas.");
            }
        });
    }
}
