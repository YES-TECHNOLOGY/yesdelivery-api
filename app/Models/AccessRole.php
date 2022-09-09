<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessRole extends Model
{
    use HasFactory;

    protected $primaryKey=['cod_access','cod_rol'];

    protected $fillable=[
        'cod_access',
        'cod_rol',
        'active'
    ];
}
