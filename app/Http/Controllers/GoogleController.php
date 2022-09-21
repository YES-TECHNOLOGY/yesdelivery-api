<?php

namespace App\Http\Controllers;


class GoogleController extends Controller
{
    public static function verifyRecaptcha($token=''): bool
    {
        $key_secret=env('APP_RECAPTCHA_KEY_SECRET');
        $url="https://www.google.com/recaptcha/api/siteverify";
        $data=[
            'secret'=>$key_secret,
            'response'=>$token
        ];
        $options=[
            'http'=>[
                'header'=>"Content-Type: application/x-www-form-urlencoded",
                "method"=>'POST',
                'content'=>http_build_query($data)
            ]
        ];
        $context=stream_context_create($options);
        $result=file_get_contents($url, false, $context);
        $resultJson = json_decode($result);
        if ($resultJson->success != true ||$resultJson->score >= 0.5) {
            return false;
        }

        return true;

    }
}
