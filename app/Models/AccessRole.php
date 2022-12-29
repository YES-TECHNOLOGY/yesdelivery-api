<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessRole extends Model
{
    use HasFactory;

    protected $fillable=[
        'cod_access',
        'cod_rol'
    ];

    protected $hidden=[
        "created_at",
        "updated_at",
    ];
}
