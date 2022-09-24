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

    public function receiveMessages(Request $request){
        $data=$request->all();
        $this->log('info',json_encode($data),'web');
        $value=$data['entry'][0]['changes'][0]['value'];
        if(isset($value['statuses'])&&$statuses=$value['statuses'][0]){
            $id=$statuses['id'];
            $message=Messages::where('whatsapp_id','=',$id)->first();
            if($message){
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
        $messages=$value['messages'][0];
        $message=$value['messages'][0]['text']['body'];
        $phone_number_id=$metadata['phone_number_id'];
        $remittent=$contacts['wa_id'];
        $conversation=Conversation::where('recipient_phone_number','=',$remittent)
           ->where('status','!=','terminated')
           ->where('deleted','!=',true)
           ->first();
        if(!$conversation){
           $data=[
                'recipient_phone_number'=>$remittent,
                'status'=>'initializer',
                'cod_user'=>1
            ];
            $conversation= Conversation::create($data);
        }

        switch ($conversation['status']){
            case 'initializer':
                $data=$this->sendMessageSimpleTemplate($remittent,'initializer',$phone_number_id);
                $dat=$data->json();
                if($data->ok()){
                    $message=[
                        'whatsapp_id'=>$dat['messages'][0]['id'],
                        'message'=>'send template initializer',
                        'send_user'=>1,
                        'conversation_id'=>$conversation->id
                    ];
                    Messages::create($message);
                    $conver=[
                        'status'=>'order'
                    ];
                    $conversation->update($conver);
                return  $this->response('false', \Illuminate\Http\Response::HTTP_OK, '200 OK');
                }
                break;
            case 'location':
                break;
            case 'reference':
                break;
            case 'assigning':
                break;
        }
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
