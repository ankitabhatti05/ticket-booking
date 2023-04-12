<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\TicketBookingRequest;
use Illuminate\Http\Request;
use App\Models\Seat;
use App\Models\User;
use InvalidArgumentException;
class BookingController extends Controller
{
    public function bookSeats(TicketBookingRequest $request)
    {
        // Validate the input seat
        $requestedSeat = $request->seat;
        $numTickets = $request->number_of_seat;
        $row = (int)substr($requestedSeat, 1);
        $column = substr($requestedSeat, 0, 1);
        //if the requested funciton name is not exists in datbase throw exception
        if (!preg_match('/^[A-T][1-9][0]?/', $requestedSeat) || $row > 10) {
            throw new InvalidArgumentException('Invalid seat input');
        }

        // Check if requested seat is available
        $seats = Seat::whereNull('user_id')->get();
        $seat = $seats->where('name', $requestedSeat)->first();
        if (!$seat) {
            return suggestAlternateSeats($request->number_of_seat, $seats);
        }

        // Check if adjacent seats are available
        $seatIds = [$seat->id];

        $seatsLeft = getAdjacentSeats($column, $row, $numTickets, 'left', $seats);
        $seatsRight = getAdjacentSeats($column, $row, $numTickets, 'right', $seats);
        if(!empty($seatsLeft) && (count($seatsLeft) == $numTickets)){
            $seatIds = array_merge($seatsLeft, $seatIds);
        } elseif(!empty($seatsRight) && (count($seatsRight) == $numTickets)){
            $seatIds = array_merge($seatIds, $seatsRight);
        }else{
            return suggestAlternateSeats($numTickets, $seats);
        }

        // Book the seats
        $user = User::first();
        Seat::whereIn('id', $seatIds)->update(['user_id' => $user->id]);

        // Return the confirmation message
        $bookedSeats = $seats->whereIn('id', $seatIds)->pluck('name')->toArray();
        return responseType(trans('message.seat_booked'), $bookedSeats, 200);
    }
}

