<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
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
    public function getTokenAndRefreshToken($email, $password) {
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
    public function login(Request $request)
    {
       $data=[
            'email'=> Request("email"),
            'password'=>Request("password"),
            'deleted'=>false,
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
       /* $log="The user '".$user->id."' logged in using manual auth.";
        $this->log('info',$log,'web',$user);*/
        return $this->response('false',Response::HTTP_OK,'200 OK',$data);
    }

    /**
     * Return new token with refresh token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
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
}
