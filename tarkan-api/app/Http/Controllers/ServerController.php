<?php

namespace App\Http\Controllers;

use App\Tarkan\traccarConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class ServerController extends Controller{

    public function get(Request $request){
        $traccar = new traccarConnector($request);
        $devices = $traccar->getDevices();

        $devicesFiltered = array_filter($devices->json(),function($a){
            $uniqueId = explode("-",$a['uniqueId']);


            return $uniqueId[0]!=='deleted';
        });

        $server = $traccar->getServer();

        $svJSON = $server->json();

        $deviceLimit = (IsSet($svJSON['attributes']['tarkan.deviceLimit'])? ($svJSON['attributes']['tarkan.deviceLimit'] - count($devicesFiltered)):false);

        return response($server->body(),$server->status())->header('licensemode','TarkanPlus')->header('deviceLimit',$deviceLimit);
    }

    public function put(Request $request){
        $traccar = new traccarConnector($request);

        $me = $traccar->getSession(['h'=>['Cookie'=>$request->headers->get('cookie')]]);
        if($me->status()===200) {
            $server = $traccar->putServer($request->input());

            $devices = $traccar->getDevices();
            $svJSON = $server->json();
            $deviceLimit = (isset($svJSON['attributes']['tarkan.deviceLimit']) ? ($svJSON['attributes']['tarkan.deviceLimit'] - count($devices->json())) : false);

            return response($server->body(), $server->status())->header('licensemode', 'TarkanPlus')->header('deviceLimit', $deviceLimit);
        }else{
            return response('User not authed', 503);
        }
    }


    public function restartServer(Request $request){

        $traccar = new traccarConnector($request);
        $me = $traccar->getSession(['h'=>['Cookie'=>$request->headers->get('cookie')]]);
        if($me->status()===200) {


            shell_exec('sleep 5 && /sbin/reboot > /dev/null 2>&1 &');

            return response()->json([]);


        }else{
            return response('User not authed', 503);
        }
    }


}

