<?php

namespace Database\Seeders;

use App\Models\Seat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::first();
        $columns = ['A','B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S','T'];
        foreach($columns as $column){
            for($rows = 1; $rows<=10; $rows++){
                $resultArray[] = [
                    'user_id' => null,
                    'name' => $column.$rows,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }
        Seat::insert($resultArray);
    }
}
