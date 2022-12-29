<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Messages;
use App\Models\OperateCity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class MessagesController extends Controller
{
    /**
     * Display a listing of the conversations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id,Request $request)
    {
        $conversations= $request->user()
           ->conversations()
           ->where('id','=',$id)
           ->where('status_user','=','accept')
           ->first();
        if(!$conversations){
            $error=[
                "message"=>"Conversation not found",
                "code"=>"CONVERSATION_NOT_FOUND"
            ];
            return $this->response('true',404,$error);
        }
        $messages= $conversations->messages()->get();
        return $this->response('false',Response::HTTP_OK,$messages);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param $id
     * @param Request $request
     * @return JsonResponse|void
     */
    public function store($id,Request $request)
    {
          $conversation= $request->user()
            ->conversations()
            ->where('id','=',$id)
            ->first();
        if(!$conversation) {
            $error = [
                "message" => "Conversation not found",
                "code" => "CONVERSATION_NOT_FOUND"
            ];
            return $this->response('true',404,$error);
        }

        $validate=Validator::make(['type'=>$request->type],[
            'type'=>'required|in:text,audio',
        ],$this->messages);

        if ($validate->fails())
        {
            return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
        }

        switch ($request->type){
            case 'text':
                $validate=Validator::make(['message'=>$request->message],[
                    'message'=>'required',
                ],$this->messages);

                if ($validate->fails())
                {
                    return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
                }

                $wp= WhatsAppController::sendMessageText($conversation->phoneNumber->number,$request->message,$conversation->phone_number_id);
                if($wp->ok()){
                    $message=[
                        'message'=>$request->message,
                        'type'=>$request->type,
                        'whatsapp_id'=> $wp['messages'][0]['id'],
                        'send_user'=>1,
                        'conversation_id'=>$conversation->id
                    ];
                    $message=Messages::create($message);
                    if($message){
                        return $this->response('false', Response::HTTP_CREATED, '201 CREATED', $message);
                    }
                }
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
                break;
            case 'audio':
                $validate=Validator::make(['message'=>$request->message],[
                    'message'=>'required|mimes:aac,mpeg,amr,ogg,mp3|max:5000',
                ],$this->messages);

                if ($validate->fails())
                {
                    return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST', $validate->errors());
                }
                $path="conversations/$conversation->id";
                $file= FileController::saveFile($request->message,$request->user(),'audio',$path);
                $media_send =WhatsAppController::uploadMedia($file->path,$conversation->phone_number_id);
                if(!$media_send->ok()){
                    $errors=[
                        'message'=>'Error to upload media to whatsapp',
                        'code'=>'ERROR_UPLOAD_MEDIA_WHATSAPP'
                    ];
                    return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST',$errors);
                }
                $id= $media_send->json()['id'];
                $send_song=WhatsAppController::sendMessageMediaObject($conversation->phoneNumber->number,$id,$conversation->phone_number_id,'audio');

                if($send_song->ok()){
                    $message=[
                        'message'=>$file->name,
                        'type'=>$request->type,
                        'whatsapp_id'=> $send_song['messages'][0]['id'],
                        'send_user'=>1,
                        'conversation_id'=>$conversation->id
                    ];
                    $message=Messages::create($message);
                    if($message){
                        return $this->response('false', Response::HTTP_CREATED, '201 CREATED', $message);
                    }
                }
                return $this->response('true', Response::HTTP_BAD_REQUEST, '400 BAD REQUEST');
                break;
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Messages  $messages
     * @return \Illuminate\Http\Response
     */
    public function show(Messages $messages)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Messages  $messages
     * @return \Illuminate\Http\Response
     */
    public function edit(Messages $messages)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  \App\Models\Messages  $messages
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Messages $messages)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Messages  $messages
     * @return \Illuminate\Http\Response
     */
    public function destroy(Messages $messages)
    {
        //
    }


}
