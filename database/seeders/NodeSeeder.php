<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\Space;
use App\Models\LayoutObject;
use Illuminate\Database\Seeder;

class NodeSeeder extends Seeder
{
    /**
     * Rellena la tabla nodes con el plano de ejemplo del teatro (filas A–Q, 708 butacas).
     */
    public function run(?Space $space = null): void
    {
        $space ??= Space::query()->firstOrCreate(
            ['name' => 'Teatro UQROO'],
            [
                'viewport' => ['zoom' => 1],
                'is_template' => true,
            ]
        );

        // Aseguramos que la plantilla tenga el flag is_template = true
        $space->update(['is_template' => true]);

        // Limpiar nodos existentes y objetos
        $space->nodes()->delete();
        $space->layoutObjects()->delete();

        // Configuración de dimensiones y espaciados
        $centerX = 800; // Eje de simetría horizontal
        $seatWidth = 32;
        $seatHeight = 32;
        $seatGap = 10;
        $aisleGap = 70; // Pasillo central
        $curveFactor = 0.00012; // Curvatura cuadrática premium

        // Definición exacta de las 18 filas del Teatro del CCU UAEQROO
        // Total asientos = 708 (Reconstrucción exacta del plano original)
        $layout = [
            'Q' => ['row_y' => 80,  'seats' => 56, 'aisles_after' => [28]],
            'P' => ['row_y' => 135, 'seats' => 56, 'aisles_after' => [28]],
            'O' => ['row_y' => 190, 'seats' => 54, 'aisles_after' => [27]],
            'Ñ' => ['row_y' => 245, 'seats' => 42, 'aisles_after' => [21]],
            'N' => ['row_y' => 300, 'seats' => 46, 'aisles_after' => [23]],
            'M' => ['row_y' => 355, 'seats' => 42, 'aisles_after' => [21]],
            'L' => ['row_y' => 410, 'seats' => 40, 'aisles_after' => [20]],
            'K' => ['row_y' => 465, 'seats' => 40, 'aisles_after' => [20]],
            'J' => ['row_y' => 520, 'seats' => 38, 'aisles_after' => [19]],
            'I' => ['row_y' => 575, 'seats' => 36, 'aisles_after' => [18]],
            'H' => ['row_y' => 630, 'seats' => 36, 'aisles_after' => [18]],
            'G' => ['row_y' => 685, 'seats' => 34, 'aisles_after' => [17]],
            'F' => ['row_y' => 740, 'seats' => 34, 'aisles_after' => [17]],
            // Pasillo transversal de 90 píxeles entre fila F y fila E
            'E' => ['row_y' => 860, 'seats' => 36, 'aisles_after' => [18], 'wheelchairs_at' => [1, 36]],
            'D' => ['row_y' => 915, 'seats' => 34, 'aisles_after' => [17]],
            'C' => ['row_y' => 970, 'seats' => 32, 'aisles_after' => [16]],
            'B' => ['row_y' => 1025, 'seats' => 32, 'aisles_after' => [16], 'wheelchairs_at' => [1, 32]],
            'A' => ['row_y' => 1080, 'seats' => 20, 'aisles_after' => [10]],
        ];

        // Crear asientos
        foreach ($layout as $rowLetter => $config) {
            $seatsCount = $config['seats'];
            $halfSeats = $seatsCount / 2;
            $rowY = $config['row_y'];

            for ($seatNumber = 1; $seatNumber <= $seatsCount; $seatNumber++) {
                $isWheelchair = isset($config['wheelchairs_at']) && in_array($seatNumber, $config['wheelchairs_at']);
                $identifier = $rowLetter.'-'.$seatNumber;

                if ($isWheelchair) {
                    $identifier .= ' ♿';
                }

                // Cálculo lineal simétrico de X alrededor de centerX
                if ($seatNumber <= $halfSeats) {
                    // Lado izquierdo: va hacia la izquierda desde el centro
                    $k = $halfSeats - $seatNumber; // 0 para el asiento más cercano al centro, 1, 2...
                    $posX = $centerX - ($aisleGap / 2) - ($k * ($seatWidth + $seatGap)) - $seatWidth;
                } else {
                    // Lado derecho: va hacia la derecha desde el centro
                    $k = $seatNumber - $halfSeats - 1; // 0 para el asiento más cercano al centro, 1, 2...
                    $posX = $centerX + ($aisleGap / 2) + ($k * ($seatWidth + $seatGap));
                }

                // Cálculo de Y aplicando curvatura cuadrática simétrica
                $dx = $posX + ($seatWidth / 2) - $centerX;
                $posY = $rowY - ($curveFactor * ($dx * $dx));

                Node::create([
                    'space_id' => $space->id,
                    'identifier' => $identifier,
                    'pos_x' => (int) round($posX),
                    'pos_y' => (int) round($posY),
                    'status' => 'active',
                    'is_occupied' => false,
                ]);
            }
        }

        // Crear objetos del escenario e infraestructura física del teatro
        // 1. ESCENARIO (al fondo de las butacas)
        LayoutObject::create([
            'space_id' => $space->id,
            'type' => 'stage',
            'properties' => [
                'pos_x' => 500,
                'pos_y' => 1140,
                'width' => 600,
                'height' => 90,
                'label' => 'ESCENARIO',
                'color' => '#1e293b',
            ],
        ]);

        // 2. CABINA DE SONIDO (en la parte superior, detrás de la fila Q)
        LayoutObject::create([
            'space_id' => $space->id,
            'type' => 'sound_cabin',
            'properties' => [
                'pos_x' => 650,
                'pos_y' => 10,
                'width' => 300,
                'height' => 45,
                'label' => 'CABINA DE SONIDO',
                'color' => '#0f172a',
            ],
        ]);
    }
}
