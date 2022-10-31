<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\OperateCity;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{

    /**
     * Assign conversation to a user
     * @return bool|void
     */
    public static function assignConversation()
    {
        $possible_users = [];
        $assigned_user=null;
        $conversation = Conversation::where('status', '=', 'assigning')->first();
        if ($conversation) {
            $location_client = [$conversation->latitude, $conversation->longitude];
            $operate_users = OperateCity::find($conversation->operate_city_id)->users;
            foreach ($operate_users as $user) {
                if($user->id==$conversation->cod_user){
                    break;
                }
                if ($conversation->type_order == 'taxi') {
                    $vehicle = $user->vehicles
                        ->where('status', '=', 'connected')
                        ->where('type_orders', '!=', 'delivery')
                        ->first();
                    if(!$vehicle){
                        break;
                    }
                    $last_location = $vehicle->locations->last();
                    $location = [
                        $last_location->latitude,
                        $last_location->longitude
                    ];
                    $is_near = GeoLocationController::isWithin($location_client, $location);
                    if ($is_near) {
                        $user['vehicle'] = $vehicle;
                        $possible_users[] = $user;
                    }
                    unset($user->vehicles);
                }
            }
            if(!$possible_users){
                $conversation->status = 'terminated';
                $conversation->save();
                $sms='Lo sentimos, no hay conductores disponibles en este momento';
                WhatsAppController::sendMessageText($conversation->phoneNumber->number,$sms,$conversation->phone_number_id);
                return false;
            }
            foreach ($possible_users as $key => $user) {
                $location_vehicle=$user->vehicle->locations->last();

                $dat=[
                    $location_vehicle->latitude,
                    $location_vehicle->longitude
                ];

                $distance= GoogleGeoLocationController::distanceMatrix($dat,$location_client);
                if($distance['status']=='OK'){
                    $user['distance']=$distance;
                }
                unset($user->vehicle->locations);

                if($assigned_user==null){
                    $assigned_user=$user;
                }else{
                    if($user['distance']['rows'][0]['elements'][0]['distance']['value']<$assigned_user['distance']['rows'][0]['elements'][0]['distance']['value']){
                        $assigned_user=$user;
                    }
                }
            }

            //enviar notificacion al usuario asignado

            $dat_conv=[
                'recipient_phone_number'=>$conversation->recipient_phone_number,
                'display_phone_number'=>$conversation->display_phone_number,
                'phone_number_id'=>$conversation->phone_number_id,
                'status'=>'assigned',
                'status_user'=>'pending',
                'cod_user'=>$assigned_user->id,
                'latitude'=>$conversation->latitude,
                'longitude'=>$conversation->longitude,
                'type_order'=>$conversation->type_order,
                'reference'=>$conversation->reference,
                'operate_city_id'=>$conversation->operate_city_id,
            ];

            $conv=Conversation::create($dat_conv);
            if(!$conv){
                Controller::log('critical',$dat_conv, 'cli');
                return false;
            }

            $conversation->status = 'assigned';
            $conversation->save();

            $assigned_user->vehicle->status='assigned';
            $assigned_user->vehicle->save();

            return true;
        }
    }

    /**
     * Accept conversation by user
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function acceptConversation(Request $request,$id)
    {
          $conversation= $request->user()
          ->conversations
          ->where('id','=',$id)
          ->where('status_user','=','pending')
          ->first();
        if(!$conversation){
            $error=[
                "message"=>"The conversation was not found",
                "code"=>"CONVERSATION_NOT_FOUND"
            ];
            return $this->response('true',404,$error);
        }

        $time_difference= Carbon::parse(now())->diffInSeconds($conversation->created_at);
        if($time_difference>40000){
            $error=[
                "message"=>"The conversation has expired",
                "code"=>"CONVERSATION_EXPIRED"
            ];
            return $this->response('true',404,$error);
        }

        $name=$conversation->user->name;
        $lastname=$conversation->user->lastname;
        $vehicle=$conversation->user->vehicles->where('status','=','assigned')->first();
        $origin=[
            $vehicle->locations->last()->latitude,
            $vehicle->locations->last()->longitude
        ];
        $destination=[
            $conversation->latitude,
            $conversation->longitude
        ];
        $time= GoogleGeoLocationController::distanceMatrix($origin,$destination)['rows'][0]['elements'][0]['duration']['text'];
        $conversation->status_user = 'accept';
        if(!$conversation->save()){
            $error=[
                "message"=>"The internal error has occurred",
                "code"=>"INTERNAL_ERROR"
            ];
            return $this->response('true',404,$error);
        }
        $parameters=array(
            array(
                "type"=> "text",
                "text"=> "$name"
            ),
            array(
                "type"=> "text",
                "text"=> "$lastname"
            ),
            array(
                "type"=> "text",
                "text"=> "$vehicle->type"
            ),
            array(
                "type"=> "text",
                "text"=> "$vehicle->brand"
            ),
            array(
                "type"=> "text",
                "text"=> "$vehicle->model"
            ),
            array(
                "type"=> "text",
                "text"=> "$vehicle->registration_number"
            ),
            array(
                "type"=> "text",
                "text"=> "$time"
            )
        );

        $trip=[
            'vehicle_id'=>$vehicle->id,
            'conversation_id'=>$conversation->id,
            'status'=>'traveling',
        ];

        $validate=Validator::make($trip,[
            'vehicle_id'=>'required|exists:vehicles,id',
            'conversation_id'=>'required|exists:conversations,id',
            'status'=>'required|in:traveling,finished,canceled,delivery'
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

       $data= Trip::create($trip);

       WhatsAppController::sendMessageParamsTemplate($conversation->phoneNumber->number,'wp_assigned_taxi_pro',$parameters,$conversation->phone_number_id);

       return $this->response('false',Response::HTTP_OK,$data);

    }

    /**
     * Reject conversation by user
     *
     * @return bool
     */
    public static function checkConversations(): bool
    {
        $conversation=Conversation::where('status_user','=','pending')->first();
        $time_difference= Carbon::parse(now())->diffInSeconds($conversation->created_at);
        if($time_difference>40){
            $vehicle= User::find($conversation->cod_user)->vehicles->where('status','=','assigned')->first();
            $vehicle->status='disconnected';
            $vehicle->save();
            $conversation->status='reject';
            $conversation->status='assigning';
            if($conversation->save()){
                return true;
            }
        }
        return false;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Conversation  $conversation
     * @return \Illuminate\Http\Response
     */
    public function show(Conversation $conversation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Conversation  $conversation
     * @return \Illuminate\Http\Response
     */
    public function edit(Conversation $conversation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Conversation  $conversation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Conversation $conversation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Conversation  $conversation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Conversation $conversation)
    {
        //
    }
}
