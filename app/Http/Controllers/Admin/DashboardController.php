<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Building;
use App\Models\Space;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Payment;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Core KPIs
        $totalCampuses = Campus::count();
        $totalBuildings = Building::count();
        $totalSpaces = Space::count();
        $totalEvents = Event::count();
        $totalReservations = Reservation::count();
        $confirmedReservations = Reservation::where('status', 'confirmed')->count();
        $validatedTickets = Reservation::whereNotNull('validated_at')->count();
        $totalRevenue = Payment::sum('amount');

        // 2. Reservations by Event (Popularity Chart - Last 6 events)
        $reservationsByEvent = Event::withCount(['reservations' => function ($query) {
                $query->where('status', 'confirmed');
            }])
            ->orderBy('start_time', 'desc')
            ->take(6)
            ->get()
            ->reverse() // Chronological order for the chart
            ->map(function ($event) {
                return [
                    'name' => strlen($event->name) > 15 ? substr($event->name, 0, 15) . '...' : $event->name,
                    'full_name' => $event->name,
                    'reservations' => $event->reservations_count,
                ];
            })
            ->values();

        // 3. Revenue Over Time (Aggregated by month, padded to always show last 6 months)
        $revenueMonths = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $revenueMonths->put($key, [
                'month_key' => $key,
                'label' => $date->isoFormat('MMM YY'),
                'total' => 0.0,
            ]);
        }

        $payments = Payment::orderBy('created_at', 'asc')->get();
        $payments->groupBy(function ($payment) {
                return $payment->created_at ? $payment->created_at->format('Y-m') : Carbon::now()->format('Y-m');
            })
            ->each(function ($group, $month) use ($revenueMonths) {
                if ($revenueMonths->has($month)) {
                    $item = $revenueMonths->get($month);
                    $item['total'] = round((float)$group->sum('amount'), 2);
                    $revenueMonths->put($month, $item);
                }
            });

        $revenueOverTime = $revenueMonths->values();

        // 4. Events per Week (density mapping, padded to always show last 8 weeks)
        $weekCollection = collect();
        for ($i = 7; $i >= 0; $i--) {
            $date = Carbon::now()->subWeeks($i);
            $key = $date->format('o-W');
            $weekCollection->put($key, [
                'week_key' => $key,
                'label' => 'Sem. ' . $date->format('W') . ' (' . $date->startOfWeek()->format('d/m') . ')',
                'count' => 0,
            ]);
        }

        $events = Event::orderBy('start_time', 'asc')->get();
        $events->groupBy(function ($event) {
                return $event->start_time ? $event->start_time->format('o-W') : Carbon::now()->format('o-W');
            })
            ->each(function ($group, $weekYear) use ($weekCollection) {
                if ($weekCollection->has($weekYear)) {
                    $item = $weekCollection->get($weekYear);
                    $item['count'] = $group->count();
                    $weekCollection->put($weekYear, $item);
                }
            });

        $eventsByWeek = $weekCollection->values();

        // 5. Reservation Status Distribution (Donut Chart representation)
        $scannedCount = Reservation::whereNotNull('validated_at')->count();
        $activeConfirmedCount = Reservation::where('status', 'confirmed')->whereNull('validated_at')->count();
        $pendingOrExpiredCount = Reservation::whereIn('status', ['pending', 'expired', 'cancelled'])->count();

        $statusDistribution = [
            [
                'status' => 'Confirmados (Por asistir)',
                'value' => $activeConfirmedCount,
                'color' => '#10b981', // green-500
            ],
            [
                'status' => 'Asistieron (Validados)',
                'value' => $scannedCount,
                'color' => '#3b82f6', // blue-500
            ],
            [
                'status' => 'Pendientes/Otros',
                'value' => $pendingOrExpiredCount,
                'color' => '#f59e0b', // amber-500
            ],
        ];

        return Inertia::render('Dashboard', [
            'stats' => [
                'campuses' => $totalCampuses,
                'buildings' => $totalBuildings,
                'spaces' => $totalSpaces,
                'events' => $totalEvents,
                'reservations' => $totalReservations,
                'confirmed' => $confirmedReservations,
                'validated' => $validatedTickets,
                'revenue' => round((float)$totalRevenue, 2),
            ],
            'reservationsByEvent' => $reservationsByEvent,
            'revenueOverTime' => $revenueOverTime,
            'eventsByWeek' => $eventsByWeek,
            'statusDistribution' => $statusDistribution,
        ]);
    }
}
