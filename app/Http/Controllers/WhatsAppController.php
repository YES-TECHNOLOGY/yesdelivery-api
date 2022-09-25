<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Log;
use App\Models\Messages;
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

    private function managementMessages($message=0,Conversation $conversation){
        if($message){
            switch ($message['type']){
                case 'text':
                    $message=[
                        'whatsapp_id'=>$message['id'],
                        'message'=>$message['text']['body'],
                        'send_user'=>1,
                        'conversation_id'=>$conversation->id,
                        'type'=>'text'
                    ];
                    Messages::create($message);
                    break;
                case 'location':
                    $message=[
                        'whatsapp_id'=>$message['id'],
                        'message'=>json_encode($message['location']),
                        'send_user'=>1,
                        'conversation_id'=>$conversation->id,
                        'type'=>'text'
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
            return null;
        }


        if(isset($value['statuses'])&&$statuses=$value['statuses'][0]){
            $id=$statuses['id'];
            $message=Messages::where('whatsapp_id','=',$id)->first();
            if(isset($message)&&$message){
                $status=$statuses['status'];
                $timestamp=Carbon::createFromTimestamp($statuses['timestamp']);
                $mess['status']=$status;
                if($status=='delivered')
                    $mess['timestamp_delivered']=$timestamp;
                if($status=='read')
                    $mess['timestamp_read']=$timestamp;

                $message->update($mess);
            }
            return  $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
        }

        $metadata=$value['metadata'];
        $contacts= $value['contacts'][0];
        $phone_number_id=$metadata['phone_number_id'];
        $display_phone_number=$metadata['display_phone_number'];
        $remittent=$contacts['wa_id'];
        $conversation=Conversation::where('recipient_phone_number','=',$remittent)
           ->where('display_phone_number','=',$display_phone_number)
           ->where('status','!=','terminated')
           ->where('deleted','!=',true)
           ->first();
        if(!$conversation){
           $data=[
                'recipient_phone_number'=>$remittent,
                'display_phone_number'=>$display_phone_number,
                'status'=>'initializer',
                'send_user'=>0,
            ];
            $conversation= Conversation::create($data);
        }

        $this->managementMessages($value['messages'][0],$conversation);

        switch ($conversation['status']){
            case 'initializer':
                $data=$this->sendMessageSimpleTemplate($remittent,'wp_initializer',$phone_number_id);
                $dat=$data->json();
                if($data->ok()){
                    $message=[
                        'whatsapp_id'=>$dat['messages'][0]['id'],
                        'message'=>'send template wp_initializer',
                        'conversation_id'=>$conversation->id,
                        'type'=>'template',
                        'send_user'=>0,
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
                if($value['messages'][0]['type']=='text'){
                    $sms=$value['messages'][0]['text']['body'];
                    if($sms=='Taxi' || $sms == 'Delivery'){
                        $data = $this->sendMessageSimpleTemplate($remittent, 'wp_location', $phone_number_id);
                        $dat = $data->json();
                        if ($data->ok()) {
                            $message = [
                                'whatsapp_id' => $dat['messages'][0]['id'],
                                'message' => 'send template wp_location',
                                'send_user'=>0,
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

                $data = $this->sendMessageSimpleTemplate($remittent, 'wp_not_found_message_order', $phone_number_id);
                $dat = $data->json();
                if ($data->ok()) {
                    $message = [
                        'whatsapp_id' => $dat['messages'][0]['id'],
                        'message' => 'send template wp_not_found_message_order',
                        'send_user'=>0,
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
                            'send_user'=>0,
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
                        'send_user'=>0,
                        'conversation_id' => $conversation->id,
                        'type'=>'template'
                    ];
                    Messages::create($message);
                    $this->sendMessageText($remittent,'Gracias!!',$phone_number_id);
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
                                'send_user'=>0,
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
                        'send_user'=>0,
                        'conversation_id' => $conversation->id,
                        'type'=>'template'
                    ];
                    Messages::create($message);
                    $this->log('info', json_encode($data), 'web');
                    return $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                }
                break;
            case 'assigning':
                break;
        }
        $this->log('critical',json_encode($data),'web');
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
}
