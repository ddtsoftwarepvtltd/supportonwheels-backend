<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(): JsonResponse
    {
        $bookings = Booking::where('user_id', auth('api')->id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['success' => true, 'data' => $bookings]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'service_name' => 'required|string|max:100',
            'date'         => 'required|date|after_or_equal:today',
            'time'         => 'required|string',
            'address'      => 'required|string|max:500',
            'amount'       => 'required|numeric|min:0',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $booking = Booking::create([
            'user_id'      => auth('api')->id(),
            'service_id'   => $request->service_id,
            'service_name' => $request->service_name,
            'date'         => $request->date,
            'time'         => $request->time,
            'address'      => $request->address,
            'notes'        => $request->notes,
            'amount'       => $request->amount,
            'status'       => 'pending',
        ]);

        return response()->json(['success' => true, 'data' => $booking], 201);
    }

    public function cancel(Booking $booking): JsonResponse
    {
        if ($booking->user_id !== auth('api')->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ye booking cancel nahi ho sakti',
            ], 422);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'data' => $booking]);
    }
}
