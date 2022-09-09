<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dpa extends Model
{
    use HasFactory;

    protected $primaryKey='cod_dpa';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable=[
        'identify',
        'name',
        'type',
        'dpa_parent',
        'create_at',
        'update_at'
    ];

    public function subDpa(){
        return $this->hasMany(Dpa::class,'dpa_parent');
    }

    public function dpaParent(){
        return $this->belongsTo(Dpa::class,'dpa_parent');
    }

}
