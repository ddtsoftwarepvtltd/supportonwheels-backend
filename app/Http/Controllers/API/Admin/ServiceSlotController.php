<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceSlot;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ServiceSlotController extends Controller
{
    use ApiResponse;

    // GET /admin/services/{service_id}/slots?date=2026-06-22
    public function index(Request $request, $serviceId): JsonResponse
    {
        $request->validate([
            'date'      => 'sometimes|date',
            'date_from' => 'sometimes|date',
            'date_to'   => 'sometimes|date',
        ]);

        $query = ServiceSlot::where('service_id', $serviceId)
            ->orderBy('date')
            ->orderBy('time_start');

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        return $this->success($query->get());
    }

    // POST /admin/services/{service_id}/slots
    // Single slot banao
    public function store(Request $request, $serviceId): JsonResponse
    {
        $request->validate([
            'date'         => 'required|date',
            'time_start'   => 'required|date_format:H:i',
            'time_end'     => 'required|date_format:H:i|after:time_start',
            'max_bookings' => 'sometimes|integer|min:1',
            'is_available' => 'sometimes|boolean',
        ]);

        $slot = ServiceSlot::create([
            'service_id'   => $serviceId,
            'date'         => $request->date,
            'time_start'   => $request->time_start,
            'time_end'     => $request->time_end,
            'max_bookings' => $request->max_bookings ?? 5,
            'booked_count' => 0,
            'is_available' => $request->is_available ?? true,
        ]);

        return $this->success($slot, 'Slot create ho gaya', 201);
    }

    // POST /admin/services/{service_id}/slots/bulk-generate
    // Ek hafte ya mahine ke slots ek baar mein banao
    public function bulkGenerate(Request $request, $serviceId): JsonResponse
    {
        $request->validate([
            'date_from'    => 'required|date|after_or_equal:today',
            'date_to'      => 'required|date|after:date_from',
            'time_start'   => 'required|date_format:H:i',
            'time_end'     => 'required|date_format:H:i|after:time_start',
            'max_bookings' => 'sometimes|integer|min:1',
            'skip_days'    => 'sometimes|array',            // [0,6] = Sunday, Saturday skip
            'skip_days.*'  => 'integer|between:0,6',
        ]);

        $skipDays    = $request->skip_days ?? [];           // 0=Sun, 1=Mon...6=Sat
        $maxBookings = $request->max_bookings ?? 5;
        $created     = 0;
        $skipped     = 0;

        $current = Carbon::parse($request->date_from);
        $end     = Carbon::parse($request->date_to);

        while ($current->lte($end)) {
            // Skip days check (e.g. Sunday=0, Saturday=6)
            if (!in_array($current->dayOfWeek, $skipDays)) {
                // Duplicate check — same service+date+time_start
                $exists = ServiceSlot::where('service_id', $serviceId)
                    ->where('date', $current->toDateString())
                    ->where('time_start', $request->time_start)
                    ->exists();

                if (!$exists) {
                    ServiceSlot::create([
                        'service_id'   => $serviceId,
                        'date'         => $current->toDateString(),
                        'time_start'   => $request->time_start,
                        'time_end'     => $request->time_end,
                        'max_bookings' => $maxBookings,
                        'booked_count' => 0,
                        'is_available' => true,
                    ]);
                    $created++;
                } else {
                    $skipped++;
                }
            }

            $current->addDay();
        }

        return $this->success([
            'created' => $created,
            'skipped' => $skipped,
            'date_from' => $request->date_from,
            'date_to'   => $request->date_to,
        ], "{$created} slots create ho gaye, {$skipped} already exist the");
    }

    // PATCH /admin/services/{service_id}/slots/{slot_id}
    public function update(Request $request, $serviceId, $slotId): JsonResponse
    {
        $slot = ServiceSlot::where('id', $slotId)
            ->where('service_id', $serviceId)
            ->first();

        if (!$slot) return $this->error('Slot nahi mila', 404);

        $request->validate([
            'max_bookings' => 'sometimes|integer|min:1',
            'is_available' => 'sometimes|boolean',
            'time_start'   => 'sometimes|date_format:H:i',
            'time_end'     => 'sometimes|date_format:H:i',
        ]);

        $slot->update($request->only('max_bookings', 'is_available', 'time_start', 'time_end'));

        return $this->success($slot, 'Slot update ho gaya');
    }

    // DELETE /admin/services/{service_id}/slots/{slot_id}
    public function destroy($serviceId, $slotId): JsonResponse
    {
        $slot = ServiceSlot::where('id', $slotId)
            ->where('service_id', $serviceId)
            ->first();

        if (!$slot) return $this->error('Slot nahi mila', 404);

        if ($slot->booked_count > 0) {
            return $this->error('Is slot mein booking hai, delete nahi kar sakte', 422);
        }

        $slot->delete();
        return $this->success(null, 'Slot delete ho gaya');
    }

    // DELETE /admin/services/{service_id}/slots/bulk-delete
    // Date range ke saare slots delete karo
    public function bulkDelete(Request $request, $serviceId): JsonResponse
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);

        $deleted = ServiceSlot::where('service_id', $serviceId)
            ->whereBetween('date', [$request->date_from, $request->date_to])
            ->where('booked_count', 0)   // booked slots safe rakho
            ->delete();

        return $this->success(['deleted' => $deleted], "{$deleted} slots delete ho gaye");
    }
}
