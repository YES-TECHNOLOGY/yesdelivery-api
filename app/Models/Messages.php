<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable=[
        'id',
        'whatsapp_id',
        'message',
        'status',
        'type',
        'timestamp_read',
        'timestamp_delivered',
        'send_user',
        'conversation_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden=[
        'created_at',
        'updated_at',
        'conversation_id'
    ];
}
