<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendTokenResetPassword extends Mailable
{
    use Queueable, SerializesModels;


    public $subject="Recupera tu contraseña";
    public $data;

    public $replyTo=[
        [ 'name'=>'Yes delivery',
            'address'=>'support@devsfotec.com']
    ];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data=$data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('layouts.password');
    }
}
