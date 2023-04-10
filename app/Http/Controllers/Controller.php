<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    /*
        @param $message string
        @param $param array
        @param $statusCode int
        return Response 
    */
    public function responseType($message, $param = null, $statusCode){
        $array = [];
        if(!empty($message)){
            $array['message'] = $message;
        }
        if(!empty($param)){
            $array['seats'] = $param;
        }
        return response($array, $statusCode);
    }
}
