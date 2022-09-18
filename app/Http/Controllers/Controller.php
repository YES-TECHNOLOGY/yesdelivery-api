<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Return a response formatted in JSON
     * @param $error
     * @param $status
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    public static function response($error=true, $code=Response::HTTP_NOT_FOUND, $status='404 Not Found', $data =array()){
        return response()->json([
            'error' => $error,
            'code' => $code,
            'status'=> $status,
            'data' => $data
        ], $code);
    }

    /**
     * Save a log in the database
     *
     * @param $type
     * @param $logt
     * @param $origin
     * @param User|null $user
     */
    public function log($type,$logt,$origin,User $user=null){
        $log=[];
        if(isset($_SERVER['HTTP_USER_AGENT'])){
            $log['user_agent']=$_SERVER['HTTP_USER_AGENT'];
            $log['ip']=$_SERVER['REMOTE_ADDR'];
        }
        $log['type']=$type;
        $log['log']=$logt;
        $log['origin']=$origin;

        if($user!=null){
            $log['id_user']=$user->id;
        }
        Log::create($log);
    }

}
