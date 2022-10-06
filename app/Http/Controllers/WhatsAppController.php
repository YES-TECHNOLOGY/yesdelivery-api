<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Log;
use App\Models\Messages;
use App\Models\WhatsappNumber;
use Carbon\Carbon;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Faker\Core\File;

class WhatsAppController extends Controller
{

    /**
     * Send Messages type txt with Whatsapp
     *
     * @param $remittent
     * @param $message
     * @param $phone_number_id
     * @return PromiseInterface|Response
     */
    private function sendMessageText($remittent,$message,$phone_number_id){
        $token=env('WHATSAPP_TOKEN');
        $wp_version=env('WHATSAPP_VERSION');
        $data=[
            "messaging_product"=> "whatsapp",
            "recipient_type"=> "individual",
            "to"=> $remittent,
            "type"=> "text",
            "text"=> [
                "preview_url"=> false,
                "body"=> "$message"
            ]
        ];

        return Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization'=>"Bearer $token"
        ])->post("https://graph.facebook.com/$wp_version/$phone_number_id/messages",$data);
    }

    /**
     * Send Messages type simple template with Whatsapp
     *
     * @param $remittent
     * @param $template
     * @param $phone_number_id
     * @return PromiseInterface|Response
     */
    private function sendMessageSimpleTemplate($remittent,$template,$phone_number_id): PromiseInterface|Response
    {
        $token=env('WHATSAPP_TOKEN');
        $wp_version=env('WHATSAPP_VERSION');

       $data= [
            "messaging_product"=> "whatsapp",
            "recipient_type"=> "individual",
            "to"=> $remittent,
            "type"=> "template",
            "template"=> [
                "name"=> $template,
                "language"=> [
                        "code"=> "es_MX"
                ]
            ]
        ];
        return Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization'=>"Bearer $token"
        ])->post("https://graph.facebook.com/$wp_version/$phone_number_id/messages",$data);
    }

    private function sendMessageParamsTemplate($remittent,$template,$parameters,$phone_number_id)
    {
        $token=env('WHATSAPP_TOKEN');
        $wp_version=env('WHATSAPP_VERSION');

        $data= [
            "messaging_product"=> "whatsapp",
            "recipient_type"=> "individual",
            "to"=> $remittent,
            "type"=> "template",
            "template"=> [
                "name"=> $template,
                "language"=> [
                    "code"=> "es_MX"
                ],
                "components"=> array(
                    array(
                         "type"=> "body",
                         "parameters"=> $parameters
                    )
                )
            ]
        ];
        return Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization'=>"Bearer $token"
        ])->post("https://graph.facebook.com/$wp_version/$phone_number_id/messages",$data);
    }

    private function managementMessages($message=0,Conversation $conversation){
        if($message){
            switch ($message['type']){
                case 'text':
                    $message=[
                        'whatsapp_id'=>$message['id'],
                        'message'=>$message['text']['body'],
                        'send_user'=>0,
                        'conversation_id'=>$conversation->id,
                        'type'=>'text'
                    ];
                    Messages::create($message);
                    break;
                case 'location':
                    $message=[
                        'whatsapp_id'=>$message['id'],
                        'message'=>json_encode($message['location']),
                        'send_user'=>0,
                        'conversation_id'=>$conversation->id,
                        'type'=>'location'
                    ];
                    Messages::create($message);
                    break;
            }
        }
    }

    public function receiveMessages(Request $request){
        $data=$request->all();
        $value= $data['entry'][0]['changes'][0]['value'] ?? '';
        if(!$value){
            $this->log('critical',json_encode($data), 'web');
            return null;
        }

        if($value['contacts'][0]['wa_id']!='593980150689')
            return '';

        if(isset($value['statuses'])&&$statuses=$value['statuses'][0]){
            $id=$statuses['id'];
            $message=Messages::where('whatsapp_id','=',$id)->first();
            if(isset($message)&&$message){
                $status=$statuses['status'];
                $timestamp=Carbon::createFromTimestamp($statuses['timestamp']);

                if($status=='delivered'){
                    $mess['timestamp_delivered']=$timestamp;
                }
                if($status=='read'){
                    $mess['timestamp_read']=$timestamp;
                }

                if($message->status!='read' && $status=='delivered') {
                    $mess['status']=$status;
                }

                if($message->status!='read'&&$status=='delivered'){
                    $mess['status']=$status;
                }

                if($message->status!='delivered'&&$status=='read') {
                    $mess['status']=$status;
                }

                $message->update($mess);
            }
            $this->log('info',$data, 'web');
            return  $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
        }

        $metadata=$value['metadata'];
        $contacts= $value['contacts'][0];
        $phone_number_id=$metadata['phone_number_id'];
        $display_phone_number=$metadata['display_phone_number'];
        $remittent=$contacts['wa_id'];

        $wp_number=WhatsappNumber::where('number','=',$remittent)->first();

       if(!$wp_number){

         $dat=[
            'number'=>$remittent
         ];
         $new_number= WhatsappNumber::create($dat);

         $dat_conv=[
               'recipient_phone_number'=>$new_number->id,
               'display_phone_number'=>$display_phone_number,
               'status'=>'name',
               'cod_user'=>1,
               'send_user'=>1,
         ];

        $conv=Conversation::create($dat_conv);
        if(!$conv){
            $this->log('critical',$dat_conv, 'web');
            return  $this->response('true', \Illuminate\Http\Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
        }

        $this->managementMessages($value['messages'][0],$conv);

        $message='Para poder brindarte un mejor servicio, por favor ingresa tu nombre.';
        $data=$this->sendMessageText($remittent,$message,$phone_number_id);
        $d=$data->json();

        if($data->ok()){
            $message=[
                'whatsapp_id'=>$d['messages'][0]['id'],
                'message'=>$message,
                'conversation_id'=>$conv->id,
                'type'=>'template',
                'send_user'=>1,
            ];
            Messages::create($message);

            $conver=[
                'status'=>'name'
            ];

            $conv->update($conver);
            $this->log('info',$data,'web');
            return  $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
           }

       }

       $conversation=$wp_number->conversations->where('display_phone_number','=',$display_phone_number)
           ->where('status','!=','terminated')
           ->where('deleted','!=',true)
           ->first();

        $this->managementMessages($value['messages'][0],$conversation);

        switch ($conversation['status']){
            case 'name':
                if($value['messages'][0]['type']!='text'){
                    $message='Disculpa no logré entenderte, por favor ingresa tu nombre.';
                    $data = $this->sendMessageText($remittent, $message, $phone_number_id);
                    $dat = $data->json();
                    if ($data->ok()) {
                        $message = [
                            'whatsapp_id' => $dat['messages'][0]['id'],
                            'message' => $message,
                            'send_user'=>1,
                            'conversation_id' => $conversation->id,
                            'type'=>'text'
                        ];
                        Messages::create($message);
                        $this->log('info', json_encode($data), 'web');
                        return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                    }
                }

                $name=$value['messages'][0]['text']['body'];
                $name=[
                    'name'=>$name
                ];
                $wp_number->update($name);

            case 'initializer':
                $parameters=array(
                    array(
                        "type"=> "text",
                        "text"=> $wp_number->name,
                    )
                );
                $data=$this->sendMessageParamsTemplate($remittent,'wp_initializer_pro',$parameters,$phone_number_id);
                $dat=$data->json();
                if($data->ok()){
                    $message=[
                        'whatsapp_id'=>$dat['messages'][0]['id'],
                        'message'=>'send template wp_initializer',
                        'conversation_id'=>$conversation->id,
                        'type'=>'template',
                        'send_user'=>1,
                    ];
                    Messages::create($message);
                    $conver=[
                        'status'=>'order'
                    ];
                    $conversation->update($conver);
                    $this->log('info',$data,'web');
                    return  $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                }

                break;
            case 'order':
                if($value['messages'][0]['type']=='text'||$value['messages'][0]['type']=='button'){
                    $sms=$value['messages'][0]['type']=='text'?$value['messages'][0]['text']['body']:$value['messages'][0]['button']['text'];
                    $sms=ucfirst(strtolower($sms));
                    if($sms=='Taxi' || $sms == 'Pedido'){
                        if($sms=='Pedido')
                            $sms='Delivery';

                        $data = $this->sendMessageSimpleTemplate($remittent, 'wp_location', $phone_number_id);
                        $dat = $data->json();
                        if ($data->ok()) {
                            $message = [
                                'whatsapp_id' => $dat['messages'][0]['id'],
                                'message' => 'send template wp_location',
                                'send_user'=>1,
                                'conversation_id' => $conversation->id,
                                'type'=>'template'
                            ];
                            Messages::create($message);
                            $conver=[
                                'status'=>'location',
                                'type_order'=>$sms
                            ];
                            $conversation->update($conver);
                            $this->log('info',$data, 'web');
                            return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                        }
                    }
                }

                $data = $this->sendMessageSimpleTemplate($remittent, 'wp_error_message_order', $phone_number_id);
                $dat = $data->json();
                if ($data->ok()) {
                    $message = [
                        'whatsapp_id' => $dat['messages'][0]['id'],
                        'message' => 'send template wp_not_found_message_order',
                        'send_user'=>1,
                        'conversation_id' => $conversation->id,
                        'type'=>'template'
                    ];
                    Messages::create($message);
                    $this->log('info', json_encode($data), 'web');
                    return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                }
                break;
            case 'location':
                if($value['messages'][0]['type']=='location'){
                    $data = $this->sendMessageSimpleTemplate($remittent, 'wp_reference', $phone_number_id);
                    $dat = $data->json();
                    if ($data->ok()) {
                        $message = [
                            'whatsapp_id' => $dat['messages'][0]['id'],
                            'message' => 'send template wp_location',
                            'send_user'=>1,
                            'conversation_id' => $conversation->id,
                            'type'=>'template'
                        ];
                        Messages::create($message);
                        $conver=[
                            'status'=>'reference',
                            'latitude'=>$value['messages'][0]['location']['latitude'],
                            'longitude'=>$value['messages'][0]['location']['longitude']
                        ];
                        $conversation->update($conver);
                        $this->log('info',$data, 'web');
                        return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                    }
                }
                $data = $this->sendMessageSimpleTemplate($remittent, 'wp_error_location', $phone_number_id);
                $dat = $data->json();
                if ($data->ok()) {
                    $message = [
                        'whatsapp_id' => $dat['messages'][0]['id'],
                        'message' => 'send template wp_error_location',
                        'send_user'=>1,
                        'conversation_id' => $conversation->id,
                        'type'=>'template'
                    ];
                    Messages::create($message);
                    $this->log('info', $data, 'web');
                    return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                }

                break;
            case 'reference':
                if($value['messages'][0]['type']=='text'){
                    $sms=$value['messages'][0]['text']['body'];
                        $data = $this->sendMessageText($remittent,'En unos minutos te asignaremos una unidad.',$phone_number_id);
                        $dat = $data->json();
                        if ($data->ok()) {
                            $message = [
                                'whatsapp_id' => $dat['messages'][0]['id'],
                                'message' => 'En unos minutos te asignaremos una unidad.',
                                'send_user'=>1,
                                'conversation_id' => $conversation->id,
                                'type'=>'text'
                            ];
                            Messages::create($message);
                            $conver=[
                                'status'=>'assigning',
                            ];
                            $conversation->update($conver);
                            $this->log('info',$data, 'web');
                            return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                        }
                    }

                $data = $this->sendMessageSimpleTemplate($remittent, 'wp_error_reference', $phone_number_id);
                $dat = $data->json();
                if ($data->ok()) {
                    $message = [
                        'whatsapp_id' => $dat['messages'][0]['id'],
                        'message' => 'send template wp_error_reference',
                        'send_user'=>1,
                        'conversation_id' => $conversation->id,
                        'type'=>'template'
                    ];
                    Messages::create($message);
                    $this->log('info', json_encode($data), 'web');
                    return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                }
                break;
            case 'assigning':
                $data = $this->sendMessageText($remittent,'En unos minutos te asignaremos una unidad.',$phone_number_id);
               return $dat = $data->json();
                break;
        }
        $this->log('info',json_encode($data),'web');
        return $this->response('false', \Illuminate\Http\Response::HTTP_BAD_REQUEST, '400 Bad Request');
    }

    public function verificationWhatsapp(Request $request){
        $mode = $request->hub_mode;
        $challenge= $request->hub_challenge;
        $token = $request->hub_verify_token;
        if ($mode && $token) {
            if ($mode === "subscribe" && $token === '123456YES') {
                return $challenge;
            }
        }
    }

    public function isWithin(){
        $org=[-1.590270233704707, -79.00484654680204];
        $dst=[-1.5869181255795615, -78.99525591268554];
       return GeoLocationController::isWithin($org,$dst);

        /*$org=[64.88907365760862, -159.51051578229257];
        $dst=[-4.912403233449302, -60.68140854968768];*/

       /* $lat0 = $org[0];
        $lng0 = $org[1];

        $lat1 = $dst[0];
        $lng1 = $dst[1];

        $rlat0 = deg2rad($lat0);
        $rlng0 = deg2rad($lng0);
        $rlat1 = deg2rad($lat1);
        $rlng1 = deg2rad($lng1);

        $latDelta = $rlat1 - $rlat0;
        $lonDelta = $rlng1 - $rlng0;

        $distance = (6371 *
            acos(
                cos($rlat0) * cos($rlat1) * cos($lonDelta) +
                sin($rlat0) * sin($rlat1)
            )
        );

        echo 'distanct arcosine ' . $distance;

        $distance2 = 6371 * 2 * asin(
                sqrt(
                    cos($rlat0) * cos($rlat1) * pow(sin($lonDelta / 2), 2) +
                    pow(sin($latDelta / 2), 2)
                )
            );

        echo '<br>distance haversine ' . $distance2;*/
    }




}
