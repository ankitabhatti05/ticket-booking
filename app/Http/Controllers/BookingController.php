<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\TicketBookingRequest;
use Illuminate\Http\Request;
use App\Models\Seat;
use App\Models\User;
use InvalidArgumentException;

class BookingController extends Controller
{
    public function index(){
        $columns = ['A','B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S','T'];
        return view('welcome', ['columns' => $columns]);
    }

function bookSeats(TicketBookingRequest $request)
{
    // Validate the input seat
    $requestedSeat = $request->seat;
    $numTickets = $request->number_of_seat;
    $row = (int)substr($requestedSeat, 1);
    $column = substr($requestedSeat, 0, 1);
    if (!preg_match('/^[A-T][1-9][0]?/', $requestedSeat) || $row > 10) {
        throw new InvalidArgumentException('Invalid seat input');
    }

    // Check if requested seat is available
    $seats = Seat::whereNull('user_id')->get();
    $seat = $seats->where('name', $requestedSeat)->first();
    if (!$seat) {
        return $this->suggestAlternateSeats($request->number_of_seat, $seats);
    }

    // Check if adjacent seats are available
    $seatIds = [$seat->id];

    $seatsLeft = $this->getAdjacentSeats($column, $row, $numTickets, 'left', $seats);
    $seatsRight = $this->getAdjacentSeats($column, $row, $numTickets, 'right', $seats);
    if(!empty($seatsLeft) && (count($seatsLeft) == $numTickets)){
        $seatIds = array_merge($seatsLeft, $seatIds);
    } elseif(!empty($seatsRight) && (count($seatsRight) == $numTickets)){
        $seatIds = array_merge($seatIds, $seatsRight);
    }else{
        return $this->suggestAlternateSeats($numTickets, $seats);
    }

    // Book the seats
    $user = User::first();
    Seat::whereIn('id', $seatIds)->update(['user_id' => $user->id]);

    // Return the confirmation message
    $bookedSeats = Seat::whereIn('id', $seatIds)->pluck('name')->toArray();
    return $this->responseType(trans('message.seat_booked'), $bookedSeats, 200);
}

function getAdjacentSeats($column, $row, $numSeats, $direction, $seats): array
{
    $seatIds = [];
 
    $start = $direction == 'left' ? ($row - $numSeats) + 1 : $row;
    $end = $direction == 'left' ? $row : ($row + $numSeats) - 1;
   // dump($start, $end, $seats->pluck('name'));
    for ($i = $start; $i <= $end; $i++) {
        $seat = $seats->where('name', $column.$i)->first();

        if (!$seat) {
            break;
        }
        $seatIds[] = $seat->id;
    }
    return $seatIds;
}

function suggestAlternateSeats(int $numSeats, $seats)
{        // Find available sets of adjacent seats
        $availableSets = [];
        foreach ($seats as $seat) {
            $column = substr($seat->name, 0, 1);
            $row = substr($seat->name, 1);
            $leftSeats = $this->getAdjacentSeats($column, $row, $numSeats, 'left', $seats);
            $rightSeats = $this->getAdjacentSeats($column, $row, $numSeats, 'right', $seats);
            if (count($leftSeats) == $numSeats || count($rightSeats) == $numSeats) {
                $availableSets[] = $seat;
            }
        }

        // Sort available sets by distance from center
        $center = ceil(count($seats) / 2);
        usort($availableSets, function ($a, $b) use ($center) {
            $distanceA = abs($a->id - $center);
            $distanceB = abs($b->id - $center);
            return $distanceA - $distanceB;
        });
        
        // If no adjacent sets are available, return failure message
        if (count($availableSets) == 0) {
            return $this->responseType(trans('message.seat_booked_failed'), null, 500);
        }
        
        // Return the suggested alternate seats
        $alternateSeats = [];
        $count = 0;
        foreach ($availableSets as $set) {
            $column = substr($set->name, 0, 1);
            $row = substr($set->name, 1);
            $leftSeats = $this->getAdjacentSeats($column, $row, $numSeats, 'left', $seats);
            $rightSeats = $this->getAdjacentSeats($column, $row, $numSeats, 'right', $seats);
            if (count($leftSeats) == $numSeats) {
                $alternateSeats[] = $seats->whereIn('id', $leftSeats)->pluck('name')->toArray();
                $count++;
            }
            if (count($rightSeats) == $numSeats && $count < 3) {
                $alternateSeats[] = $seats->whereIn('id', $rightSeats)->pluck('name')->toArray();
                $count++;
            }
            if ($count == 3) {
                break;
            }
        }
        return $this->responseType(trans('message.seat_not_available'), $alternateSeats, 500);
    }
}

