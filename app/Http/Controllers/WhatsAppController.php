<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Log;
use App\Models\Messages;
use App\Models\WhatsappNumber;
use Carbon\Carbon;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Faker\Core\File;
use Illuminate\Support\Facades\Storage;

class WhatsAppController extends Controller
{

    /**
     * Upload file to whatsapp
     * @param $path_file
     * @param $phone_number_id
     * @return PromiseInterface|Response
     */
    public static function uploadMedia($path_file,$phone_number_id)
    {

        $token=env('WHATSAPP_TOKEN');

        $wp_version=env('WHATSAPP_VERSION');
        $media = '../storage/app/' . $path_file;
        $contents = fopen($media, 'r');
        $data=[
            "messaging_product" => "whatsapp",
        ];

        $headers=[
            'Authorization' => 'Bearer '.$token
        ];

        return Http::withHeaders($headers)
            ->attach('file',$contents)
            ->post("https://graph.facebook.com/$wp_version/$phone_number_id/media",$data);
    }

    /**
     * Send message to whatsapp
     *
     * @param $remittent
     * @param $object_id
     * @param $phone_number_id
     * @param $type
     * @return PromiseInterface|Response
     */
    public static function sendMessageMediaObject($remittent,$object_id,$phone_number_id,$type): PromiseInterface|Response
    {
        $token=env('WHATSAPP_TOKEN');
        $wp_version=env('WHATSAPP_VERSION');
        $data=[
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to"=> $remittent,
            "type"=> $type,
            "$type"=> [
                "id"=> "$object_id"
            ]
        ];
        return Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization'=>"Bearer $token"
        ])->post("https://graph.facebook.com/$wp_version/$phone_number_id/messages",$data);
    }

    /**
     * Send Messages type txt with Whatsapp
     *
     * @param $remittent
     * @param $message
     * @param $phone_number_id
     * @return PromiseInterface|Response
     */
    public static function sendMessageText($remittent,$message,$phone_number_id): PromiseInterface|Response
    {
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

    /**
     * Send Messages type template with Whatsapp
     * @param $remittent
     * @param $template
     * @param $parameters
     * @param $phone_number_id
     * @return PromiseInterface|Response
     */
    public static function sendMessageParamsTemplate($remittent, $template, $parameters, $phone_number_id,$image_header_url=false): PromiseInterface|Response
    {
        $token=env('WHATSAPP_TOKEN');
        $wp_version=env('WHATSAPP_VERSION');

        if($image_header_url){
            $components[]=array(
                "type"=> "header",
                "parameters"=> array(
                    array(
                        "type"=> "image",
                        "image"=> [
                            "link"=> $image_header_url
                        ]
                    )
                )
            );
        }
        $components[]=array(
            "type"=> "body",
            "parameters"=> $parameters
        );
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
                "components"=> $components
            ]
        ];
        return Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization'=>"Bearer $token"
        ])->post("https://graph.facebook.com/$wp_version/$phone_number_id/messages",$data);
    }

    private static function retriveMediaUrl($media_id){
        $token=env('WHATSAPP_TOKEN');
        $wp_version=env('WHATSAPP_VERSION');

        return Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization'=>"Bearer $token"
        ])->get("https://graph.facebook.com/$wp_version/$media_id");

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
                case 'audio':
                    $token=env('WHATSAPP_TOKEN');
                    $audio= $message['audio'];
                    $url=WhatsAppController::retriveMediaUrl($audio['id']);
                    if($url->ok()){
                        $user= $conversation->user;
                        $path="conversations/demo/$conversation->id";
                        $content= Http::withHeaders([
                            'Content-Type'=>'application/json',
                            'Authorization'=>"Bearer $token"
                        ])->get($url['url'])->body();

                        $file= FileController::saveFileByContent($content,$user,'audio',$url['mime_type']);
                        $message=[
                            'message'=>$file->name,
                            'type'=>'audio',
                            'whatsapp_id'=>$message['id'],
                            'send_user'=>1,
                            'conversation_id'=>$conversation->id
                        ];
                        Messages::create($message);
                    }
                    break;
            }
        }
    }

    public function receiveMessages(Request $request){
        $message_wp=$request->all();

        $value= $message_wp['entry'][0]['changes'][0]['value'] ?? '';
        if(!$value){
            $this->log('critical',json_encode($message_wp), 'facebook');
            return null;
        }


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
            $this->log('info',json_encode($message_wp), 'facebook');
            return  $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
        }

        if(env('APP_TEST')&&$value['contacts'][0]['wa_id']!='593980150689')
            return '';

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
               'phone_number_id'=>$phone_number_id,
               'status'=>'name',
               'cod_user'=>1,
               'send_user'=>1,
         ];

        $conv=Conversation::create($dat_conv);
        if(!$conv){
            $log=[
                'status'=>'error',
                'message'=>'Error to create conversation',
                'data'=>$dat_conv
            ];
            $this->log('critical',$log, 'web');
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
                'type'=>'text',
                'send_user'=>1,
            ];
            Messages::create($message);

            $conver=[
                'status'=>'name'
            ];

            $conv->update($conver);
            $this->log('info',json_encode($message_wp), 'facebook');
            return  $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
           }

       }

           $conversation=$wp_number->conversations
           ->where('display_phone_number','=',$display_phone_number)
           ->where('status','!=','terminated')
           ->where('deleted','!=',true)
           ->first();

       if(!$conversation){
           $dat_conv=[
               'recipient_phone_number'=>$wp_number->id,
               'display_phone_number'=>$display_phone_number,
               'status'=>'initializer',
               'phone_number_id'=>$phone_number_id,
               'cod_user'=>1,
               'send_user'=>1,
           ];

           $conversation=Conversation::create($dat_conv);
           if(!$conversation){
                $log=[
                     'status'=>'error',
                     'message'=>'Error to create conversation',
                     'data'=>$dat_conv
                ];
               $this->log('critical',$log, 'web');
               $this->log('info',json_encode($message_wp), 'facebook');
               return  $this->response('true', \Illuminate\Http\Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
           }
       }

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
                        $this->log('info',json_encode($message_wp), 'facebook');
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
                    $this->log('info',json_encode($message_wp), 'facebook');
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
                            $this->log('info',json_encode($message_wp), 'facebook');
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
                    $this->log('info',json_encode($message_wp), 'facebook');
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
                        $this->log('info',json_encode($message_wp), 'facebook');
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
                    $this->log('info',json_encode($message_wp), 'facebook');
                    return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                }

                break;
            case 'reference':
                if($value['messages'][0]['type']=='text'){
                    $smsr=$value['messages'][0]['text']['body'];
                    $location_client = [
                        'lat'=>$conversation->latitude,
                        'lng'=>$conversation->longitude
                    ];

                    $city=GeoLocationController::getCityLocatedClient($location_client,$conversation->type_order);

                    $sms=(!$city)?"Lo sentimos, te encuentras fuera de nuestra área de servicio.":
                        "En unos minutos te asignaremos una unidad.";
                        $data = $this->sendMessageText($remittent,$sms,$phone_number_id);
                        $dat = $data->json();
                        if ($data->ok()) {
                            $message = [
                                'whatsapp_id' => $dat['messages'][0]['id'],
                                'message' => $sms,
                                'send_user'=>1,
                                'conversation_id' => $conversation->id,
                                'type'=>'text'
                            ];
                            Messages::create($message);
                            $conver=[
                                'reference'=>$smsr,
                                'status'=>($city)?'assigning':'terminated',
                                'operate_city_id'=>($city)?$city->id:null
                            ];
                            $conversation->update($conver);
                            $this->log('info',json_encode($message_wp), 'facebook');
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
                    $this->log('info',json_encode($message_wp), 'facebook');
                    return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                }
                break;
            case 'assigned':
                return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                break;
        }
        $this->log('info',json_encode($message_wp), 'facebook');
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
