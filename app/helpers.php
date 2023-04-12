<?php

    /**
     * This function will be use if the request seat is already booked then will returnother alternate seats
     * @var $column string
     * @var $row int
     * @var $numSeats int
     * @var $direction string
     * @var $seats database collection
     */
    function suggestAlternateSeats(int $numSeats, $seats)
    {   // Find available sets of adjacent seats
        $availableSets = [];
        $count = 0;
        foreach ($seats as $seat) {
            $column = substr($seat->name, 0, 1);
            $row = substr($seat->name, 1);
            $leftSeats = getAdjacentSeats($column, $row, $numSeats, 'left', $seats);
            $rightSeats = getAdjacentSeats($column, $row, $numSeats, 'right', $seats);
                
            if (count($leftSeats) == $numSeats && $count < 3) {
                if(!in_array($seatName = $seats->whereIn('id', $leftSeats)->pluck('name')->toArray(), $availableSets)){
                    $availableSets[] = $seatName;
                    $count++;
                }  
            }
            if (count($rightSeats) == $numSeats && $count < 3) {
                if(!in_array($seatName = $seats->whereIn('id', $rightSeats)->pluck('name')->toArray(), $availableSets)){
                    $availableSets[] = $seats->whereIn('id', $rightSeats)->pluck('name')->toArray();
                    $count++;
                }
            }
            if ($count == 3) {
                break;
            }
        }
        // If no adjacent sets are available, return failure message
        if (count($availableSets) == 0) {
            return responseType(trans('message.seat_booked_failed'), null, 500);
        }
        return responseType(trans('message.seat_not_available'), $availableSets, 500);
    }
    
    /**
     * This function will be use to get starting and ending point or booking
     * @var $column string
     * @var $row int
     * @var $numSeats int
     * @var $direction string
     * @var $seats database collection
     */
    function getAdjacentSeats($column, $row, $numSeats, $direction, $seats): array
    {
        $seatIds = [];
        //fetch starting and ending point for booking list
        $start = $direction == 'left' ? ($row - $numSeats) + 1 : $row;
        $end = $direction == 'left' ? $row : ($row + $numSeats) - 1;
       
        for ($i = $start; $i <= $end; $i++) {
            $seat = $seats->where('name', $column.$i)->first();
            if (!$seat) {
                break;
            }
            $seatIds[] = $seat->id;
        }
        return $seatIds;
    }
    /*
        @param $message string
        @param $param array
        @param $statusCode int
        return Response 
    */
    function responseType($message, $param = null, $statusCode){
        $array = [];
        if(!empty($message)){
            $array['message'] = $message;
        }
        if(!empty($param)){
            $array['seats'] = $param;
        }
        return response($array, $statusCode);
    }


