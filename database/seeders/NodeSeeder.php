<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\Space;
use Illuminate\Database\Seeder;

class NodeSeeder extends Seeder
{
    /**
     * Rellena la tabla nodes con el plano de ejemplo del teatro (filas A–Q).
     */
    public function run(?Space $space = null): void
    {
        $space ??= Space::query()->firstOrCreate(
            ['name' => 'Teatro UQROO'],
            ['viewport' => ['zoom' => 1]]
        );

        $space->nodes()->delete();

        $startX = 50;
        $startY = 50;
        $seatWidth = 40;
        $seatHeight = 40;
        $seatGap = 8;
        $rowGap = 20;
        $aisleGap = 60;

        $layout = [
            'A' => ['seats' => 20, 'aisles_after' => [10]],
            'B' => ['seats' => 32, 'aisles_after' => [16], 'wheelchairs_at' => [1, 32]],
            'C' => ['seats' => 32, 'aisles_after' => [16]],
            'D' => ['seats' => 34, 'aisles_after' => [17]],
            'E' => ['seats' => 36, 'aisles_after' => [18], 'wheelchairs_at' => [1, 36]],
            'F' => ['seats' => 34, 'aisles_after' => [17]],
            'G' => ['seats' => 34, 'aisles_after' => [17]],
            'H' => ['seats' => 36, 'aisles_after' => [18]],
            'I' => ['seats' => 36, 'aisles_after' => [18]],
            'J' => ['seats' => 38, 'aisles_after' => [19]],
            'K' => ['seats' => 40, 'aisles_after' => [20]],
            'L' => ['seats' => 40, 'aisles_after' => [20]],
            'M' => ['seats' => 42, 'aisles_after' => [21]],
            'N' => ['seats' => 46, 'aisles_after' => [23]],
            'O' => ['seats' => 54, 'aisles_after' => [27]],
            'P' => ['seats' => 56, 'aisles_after' => [28]],
            'Q' => ['seats' => 56, 'aisles_after' => [28]],
        ];

        $currentY = $startY;

        foreach ($layout as $rowLetter => $config) {
            $currentX = $startX;

            for ($seatNumber = 1; $seatNumber <= $config['seats']; $seatNumber++) {
                $isWheelchair = isset($config['wheelchairs_at']) && in_array($seatNumber, $config['wheelchairs_at']);
                $identifier = $rowLetter.'-'.$seatNumber;

                if ($isWheelchair) {
                    $identifier .= ' ♿';
                }

                Node::create([
                    'space_id' => $space->id,
                    'identifier' => $identifier,
                    'pos_x' => $currentX,
                    'pos_y' => $currentY,
                    'status' => 'active',
                    'is_occupied' => false,
                ]);

                $currentX += $seatWidth + $seatGap;

                if (isset($config['aisles_after']) && in_array($seatNumber, $config['aisles_after'])) {
                    $currentX += $aisleGap;
                }
            }

            $currentY += $seatHeight + $rowGap;
        }
    }
}
