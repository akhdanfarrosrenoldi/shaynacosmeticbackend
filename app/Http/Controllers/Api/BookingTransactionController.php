<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingTransactionRequest;
use Illuminate\Http\Request;

class BookingTransactionController extends Controller
{
    //
    public function store(StoreBookingTransactionRequest $request)
    {
        try {
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occured', 'error' => $e->getMessage()], 500);
        }
    }
}
