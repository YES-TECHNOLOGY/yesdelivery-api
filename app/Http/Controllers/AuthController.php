<?php

namespace App\Http\Controllers;


use App\Mail\SendTokenResetPassword;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Passport\Client;


class AuthController extends Controller
{

    /**
     * Return Refresh token and Access token.
     *
     * @param $email
     * @param $password
     * @return array|mixed
     */
    public function getTokenAndRefreshToken($email, $password): mixed
    {
        $client = Client::where('password_client', 1)->first();
        $response = Http::withoutVerifying()->asForm()->post(env('APP_URL').'/oauth/token', [
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => $email,
                'password' => $password,
                'scope' => '*'
        ]);
        return $response->json();
    }

    /**
     * Login User
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request):JsonResponse
    {
       $data=[
            'email'=> Request("email"),
            'password'=>Request("password"),
            'active'=>true
        ];

        if (!Auth::attempt($data)) {
            return $this->response('true',Response::HTTP_UNAUTHORIZED,'401 Unauthorized');
        }
        $tokenResult= $this->getTokenAndRefreshToken($data['email'], $data['password']);

        $tokenParts = explode(".", $tokenResult['access_token']);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);
        $jwtexp=   \DateTimeImmutable::createFromFormat(
            'U.u',
            $jwtPayload->exp,
        )->setTimezone(new \DateTimeZone('America/Guayaquil'))->format('Y-m-d H:i:s');

        $jwtexp= date(DATE_ISO8601, strtotime($jwtexp));
        $user = User::where('email','=',$data['email'])->first();
        $data=[
            'user'=>$user,
            'access_token'=>[
                "token"=>$tokenResult['access_token'],
                "type"=>$tokenResult['token_type'],
                'expires_at'=> $jwtexp
            ],
            'refresh_token'=>[
                "token"=>$tokenResult['refresh_token']
            ]
        ];
        $log="The user '".$user->id."' logged in using manual auth.";
        $this->log('info',$log,'web',$user);
        return $this->response('false',Response::HTTP_OK,'200 OK',$data);
    }

    /**
     * Return new token with refresh token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $client = Client::where('password_client', 1)->first();
        $response = Http::withoutVerifying()->asForm()->post(env('APP_URL').'/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scope' => '*'
        ]);
        if($response->clientError())
           return $this->response('true',Response::HTTP_BAD_REQUEST,'400 Bad Request');
        $response= $response->json();

        $tokenParts = explode(".", $response['access_token']);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);
        $jwtexp=   \DateTimeImmutable::createFromFormat(
            'U.u',
            $jwtPayload->exp,
        )->setTimezone(new \DateTimeZone('America/Guayaquil'))->format('Y-m-d H:i:s');

        $jwtexp= date(DATE_ISO8601, strtotime($jwtexp));

        $data=[
            'access_token'=>[
                "token"=>$response['access_token'],
                "type"=>$response['token_type'],
                'expires_at'=> $jwtexp
            ],
            'refresh_token'=>[
                "token"=>$response['refresh_token']
            ]
        ];
        /* $log="The user '".$user->id."' logged in using manual auth.";
 $this->log('info',$log,'web',$user);*/
        return $this->response('false',Response::HTTP_OK,'200 OK',$data);

    }

    /**
     * Send token for recover password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recoverPassword(Request $request): JsonResponse
    {

        $verifyCaptcha= GoogleController::verifyRecaptcha($request->recaptcha_token);

        if(!$verifyCaptcha){
            $errors[]="Captcha Incorrecto";
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $errors);
        }

        $email=$request->email;
        $user=User::where('email','=',$email)
            ->first();
        if(!isset($user)){
            $errors['user_not_found']="Usuario no encontrado";
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $errors);
        }

        $remember_token = str::random(60);
        $remember_token_valid_time=now()->addMinute(15);
        $data['remember_token']=$remember_token;
        $data['remember_token_valid_time']=$remember_token_valid_time;
        $dat_email=[
            'name' => "$user->name $user->lastname",
            'email' => $user->email,
            'user' => Crypt::encryptString($user->id),
            'token'=>$remember_token
        ];

        $for = [
            ['name' => "$user->name $user->lastname",
                'email' => $user->email]
        ];
        $user->update($data);
        Mail::to($for)->send(new SendTokenResetPassword($dat_email));
        $log="The user '".$user->id."' requested to recover their password.";
        $this->log('info',$log,'web',$user);
        return $this->response('false', Response::HTTP_OK, '200 OK');

    }

    /**
     * Store new password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveNewPassword(Request $request): JsonResponse
    {
        $token=$request->token;
        $errors=[];
        $data=[];

        $edit_permission=[
            'user',
            'confirm_password',
            'new_password',
            'token'
        ];

        foreach ($edit_permission as $d){
            if(isset($request->$d)){
                $data[$d]=$request->$d;
            }
        }

        $validate=\Validator::make($data,[
            'user'=>'required',
            'token'=>'required',
            'confirm_password'=>'required',
            'new_password'=>'string|same:confirm_password|required'
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        try{
            $user_id =Crypt::decryptString($request->user);
            $user=User::find($user_id);
            $remember_token=$user->remember_token;
            $remember_toke_valid_time=$user->remember_toke_valid_time;
            if(now()>$remember_toke_valid_time) {
                $errors['incorrect_token']="El token es incorrecto";
            }
            if($remember_token==$token) {
                $data['password']=bcrypt($data['new_password']);
                $data['remember_token']=null;
                $data['remember_token_valid_time']=null;
                if($user->update($data)){
                    $msj['change_success']='Se actualizo el password correctamente';
                    $log="The user '".$user->id."' requested updated their password.";
                    $this->log('info',$log,'web',$user);

                    return $this->response('false', Response::HTTP_OK, '200 OK', $msj);
                }
                $errors['internal_error']='Ocurrio un error interno';
                return $this->response('true', Response::HTTP_INTERNAL_SERVER_ERROR, '500 Internal Error',  $errors);
            }
        }catch(DecryptException $de){
            $errors['incorrect_token']="El token es incorrecto";
        }
        return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $errors);
    }
}
