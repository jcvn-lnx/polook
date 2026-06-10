<?php


namespace App\Http\Controllers;

use App\Models\UserLog;
use App\Tarkan\traccarConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class DeviceController extends Controller{



    public function autoLink(Request $request){

        $clientCookie = $request->cookie('JSESSIONID');

        $data = $request->all();

        $traccar = new traccarConnector($request);

        $me = $traccar->getSession(['h'=>['Cookie'=>$request->headers->get('cookie')]]);
        if($me->status()===200) {


            $userData = $me->json();

            $device = $traccar->getDeviceByImei($data['imei']);
            if($device->status()===200 && count($device->json())>0){
                $devices = $device->json();
                $device = $devices[0];

                $device['name'] = $userData['name'].' - '.$data['placa'];
                $device['model'] = $data['modelo'];
                $device['category'] = $data['categoria'];
                $device['attributes']['placa'] = $data['placa'];


                $traccar->saveDevice($device['id'],$device);

                $traccar->linkObjects(['userId'=>$userData['id'],'deviceId'=>$device['id']]);


                return response('OK',200);

            }else{
                return response('Invalid device', 503);
            }



        }else{
            return response('User not authed', 503);
        }


    }

    public function post(Request $request){
        $clientCookie = $request->cookie('JSESSIONID');

        $data = $request->json();


        $traccar = new traccarConnector($request);

        $server = $traccar->getServer();

        $svJSON = $server->json();
        $devices = $traccar->getDevices();

        $deviceLimit = (IsSet($svJSON['attributes']['tarkan.deviceLimit'])? ($svJSON['attributes']['tarkan.deviceLimit'] - count($devices->json())):false);

        if($deviceLimit!==false && ($deviceLimit-1)<=0){
            return response('Server Device Limit Exceeded', 503);
        }else {
            $me = $traccar->getSession(['h' => ['Cookie' => $request->headers->get('cookie')]]);
            if ($me->status() === 200) {

                $deviceId = $request->input('deviceId', $request->input('id', 0));

                if ($deviceId > 0) {
                    $old = $traccar->getDevice($deviceId, ['h' => ['Cookie' => $request->headers->get('cookie')]]);
                    $response = $traccar->saveDevice($deviceId, $request->all(), ['h' => ['Cookie' => $request->headers->get('cookie')]]);

                    $logOld = $old->json();
                } else {
                    $response = $traccar->createDevice($request->all(), ['h' => ['Cookie' => $request->headers->get('cookie')]]);

                    $logOld = null;
                }


                UserLog::create([
                    'sesId' => $clientCookie,
                    'serverIp' => $request->ip(),
                    'serverHost' => $request->header('tarkan-domain'),
                    'username' => $me['email'],
                    'userIp' => $request->header('x-real-ip'),
                    'userAgent' => $request->userAgent(),
                    'log' => [
                        'code' => 301,
                        'object' => ['deviceId' => intval($deviceId)],
                        'status' => $response->status(),
                        "old" => $logOld,
                        "data" => $data
                    ]
                ]);


                return response($response->body(), $response->status());
            } else {
                return response('User not authed', 503);
            }
        }
    }


    public function put(Request $request){
        $clientCookie = $request->cookie('JSESSIONID');

        $data = $request->json();

        $traccar = new traccarConnector($request);

        $me = $traccar->getSession(['h'=>['Cookie'=>$request->headers->get('cookie')]]);
        if($me->status()===200) {

            $old = $traccar->getDevice($request->deviceId, ['h' => ['Cookie' => $request->headers->get('cookie')]]);

            $response = $traccar->saveDevice($request->deviceId, $request->all(), ['h' => ['Cookie' => $request->headers->get('cookie')]]);


            UserLog::create([
                'sesId' => $clientCookie,
                'serverIp' => $request->ip(),
                'serverHost' => $request->header('tarkan-domain'),
                'username' => $me['email'],
                'userIp' => $request->header('x-real-ip'),
                'userAgent' => $request->userAgent(),
                'log' => [
                    'code' => 301,
                    'object' => ['deviceId' => (IsSet($request->deviceId)) ? intval($request->deviceId) : 0],
                    'status' => $response->status(),
                    "old"=> $old->json(),
                    "data" => $data
                ]
            ]);


            return response($response->body(),$response->status());
        }else{
            return response('User not authed', 503);
        }
    }

    public function delete(Request $request){
        $clientCookie = $request->cookie('JSESSIONID');

        $data = $request->json();

        $traccar = new traccarConnector($request);

        $me = $traccar->getSession(['h'=>['Cookie'=>$request->headers->get('cookie')]]);
        if($me->status()===200) {

            $server = $traccar->getServer()->json();

            $old = $traccar->getDevice($request->deviceId, ['h' => ['Cookie' => $request->headers->get('cookie')]]);


            if($server['attributes'] && IsSet($server['attributes']['tarkan.enableLazyDeletion']) && $server['attributes']['tarkan.enableLazyDeletion']===true){



                $tmp = $old->json();
                $tmp = $tmp[0];

                if (IsSet($tmp['attributes']) && is_array($tmp['attributes']) && count($tmp['attributes'])==0) {
                    $tmp['attributes'] = (object)null;
                }


                $tmp['uniqueId'] = 'deleted-'.$tmp['uniqueId'].'-'.time();
                $response = $traccar->saveDevice($request->deviceId, $tmp);
            }else {
                $response = $traccar->deleteDevice($request->deviceId, ['h' => ['Cookie' => $request->headers->get('cookie')]]);
            }


            UserLog::create([
                'sesId' => $clientCookie,
                'serverIp' => $request->ip(),
                'serverHost' => $request->header('tarkan-domain'),
                'username' => $me['email'],
                'userIp' => $request->header('x-real-ip'),
                'userAgent' => $request->userAgent(),
                'log' => [
                    'code' => 302,
                    'object' => ['deviceId' => (IsSet($request->deviceId)) ? intval($request->deviceId) : 0],
                    'status' => $response->status(),
                    "old"=> $old->json()
                ]
            ]);


            return response($response->body(),$response->status());
        }else{
            return response('User not authed', 503);
        }
    }

    public function uploadImage(Request $request){

        if(!Storage::exists('assets/'.$request->ip().'/assets/images/')){
            Storage::makeDirectory('assets/'.$request->ip().'/assets/images/');
        }


        Image::make($request->file('image'))->fit('170', '140')->save(storage_path(). '/app/assets/'.$request->ip().'/assets/images/' . $request->deviceId .'.jpg')->encode('jpg', 80);

        return response()->json(['img'=>true]);
    }


    public static function stopEngine(Request $request,$deviceId){

        $traccar = new traccarConnector($request);

        $changeNative = false;

        $availableCommands = $traccar->getAvailableCommands($deviceId);
        if($availableCommands->status()===200){
            $commands = $availableCommands->json();

            foreach($commands as $command){
                if($command['attributes']['tarkan.changeNative']==='engineStop'){
                    $changeNative = $command;
                    break;
                }
            }
        }

        if($changeNative){
            $command['deviceId'] = $deviceId;
            $send = $traccar->sendCommand($command);
        }else {
            $send = $traccar->sendStopEngine($deviceId);
        }

        return ['status'=>$send->status(),'body'=>$send->body(),'native'=>$changeNative];
    }

    public static function resumeEngine(Request $request,$deviceId){

        $traccar = new traccarConnector($request);

        $changeNative = false;

        $availableCommands = $traccar->getAvailableCommands($deviceId);
        if($availableCommands->status()===200){
            $commands = $availableCommands->json();

            foreach($commands as $command){
                if($command['attributes']['tarkan.changeNative']==='engineResume'){
                    $changeNative = $command;
                    break;
                }
            }
        }

        if($changeNative){
            $command['deviceId'] = $deviceId;
            $send = $traccar->sendCommand($command);
        }else {
            $send = $traccar->sendResumeEngine($deviceId);
        }

        return ['status'=>$send->status(),'body'=>$send->body(),'native'=>$changeNative];
    }
}
