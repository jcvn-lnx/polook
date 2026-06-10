<?php

namespace App\Http\Controllers\OMC;

use App\Http\Controllers\Controller;
use App\Models\TcUsers;
use Illuminate\Http\Request;

class UserController extends Controller{

    public function get(Request $request){
        return response()->json(TcUsers::fromCode($request->tarkan['code'])->get());
    }

    public function put(Request $request){

    }
}

