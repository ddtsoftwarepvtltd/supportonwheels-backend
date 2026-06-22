<?php

namespace App\Http\Controllers\API\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    use ApiResponse;

    // POST /customer/bookings/{id}/review
    public function store(Request $request, $id): JsonResponse
    {
        $request->validate([
            'rating'      => 'required|integer|min:1|max:5',
            'review_text' => 'sometimes|nullable|string|max:2000',
            'photos'      => 'sometimes|array|max:5',
            'photos.*'    => 'image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $booking = Booking::where('id', $id)
            ->where('customer_id', auth('api')->id())
            ->where('status', 'completed')
            ->first();

        if (!$booking) {
            return $this->error('Booking nahi mili ya complete nahi hui', 404);
        }

        // Already review check
        if (Review::where('booking_id', $id)->exists()) {
            return $this->error('Is booking ka review pehle se de chuke ho', 409);
        }

        // Photos upload karo
        $photoUrls = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store("reviews/{$id}", 'public');
                $photoUrls[] = Storage::url($path);
            }
        }

        $review = Review::create([
            'booking_id'  => $booking->id,
            'customer_id' => auth('api')->id(),
            'provider_id' => $booking->provider_id,
            'service_id'  => $booking->service_id,
            'rating'      => $request->rating,
            'review_text' => $request->review_text,
            'photo_urls'  => $photoUrls,
        ]);

        // Provider rating update karo
        if ($booking->provider_id) {
            $avgRating = Review::where('provider_id', $booking->provider_id)->avg('rating');
            $booking->provider->providerProfile()->update(['rating' => round($avgRating, 2)]);
        }

        return $this->success($review, 'Review submit ho gaya. Shukriya!', 201);
    }
}
