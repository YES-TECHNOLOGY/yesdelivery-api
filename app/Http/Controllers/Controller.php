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
    protected $limit_pagination=15;
    protected $messages = [
        'required'  => 'El campo :attribute es requerido.',
        'unique'    => ':attribute ya existe',
        'exists'=>':attribute no existe',
        'email'=>'El correo es incorrecto',
        'in'=>':attribute es incorrecto',
        'same'=>':attribute no coinciden',
        'mimes'=>'Formato no admitido',
        'regex'=>'El formato es incorrecto',
        'numeric'=>'El campo tiene que ser numérico',
        'string'=>'Solo se aceptan texto',
        'size'=>'El tamaño del archivo es muy grande',
        'max'=>'El tamaño del archivo es muy grande',
    ];

    /**
     * Return a response formatted in JSON
     * @param $error
     * @param $status
     * @param $data
     * @param bool $paginate
     * @return \Illuminate\Http\JsonResponse
     */

    public static function response($error=true, $code=Response::HTTP_NOT_FOUND, $status='404 Not Found', $data='' ,$paginate=false){
        $d=array();
        $d['error']= $error;
        $d['code'] = $code;
        $d['status']= $status;
        if($data!='')
            $d['data']=$data;
        if($paginate){
            $d['data']=$data->items();
            $d['per_page']=$data->perPage();
            $d['last_page']=$data->lastPage();
            $d['current_page']=$data->currentPage();
            $d['total']=$data->total();
        }
        return response()->json($d, $code);
    }
    /**
     * Save a log in the database
     *
     * @param $type
     * @param $logt
     * @param $origin
     * @param User|null $user
     */
    public static function log($type,$logt,$origin,User $user=null){
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

    /**
     * Verify access with id user.
     *
     * @param User $user
     * @param $method
     * @param $endpoint
     */
    public function verifyPermissions(User $user, $method, $endpoint){
        $access= $user->rol->access
            ->where('method','=',$method)
            ->where('endpoint','=',$endpoint)
            ->first();
        if(!$access)
            abort(403,'403 Forbidden');
    }

}
