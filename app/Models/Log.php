<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $primaryKey='cod_log';
    protected $fillable=[
        'type',
        'ip',
        'user_agent',
        'log',
        'origin',
        'id_user'
    ];
}
