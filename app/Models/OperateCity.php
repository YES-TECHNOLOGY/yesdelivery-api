<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\Json;

class OperateCity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'minimum_price',
        'night_km_price',
        'day_km_price',
        'night_min_price',
        'day_min_price',
        'additional_price',
        'night_start_time',
        'night_end_time',
        'active',
        'comment',
        'cod_dpa',
        'polygon'
    ];

    protected $casts = [
        'polygon' => Json::class,
        'night_start_time' => 'datetime',
        'night_end_time' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function dpa(){
        return $this->belongsTo(Dpa::class,'cod_dpa','cod_dpa');
    }

    public function users(){
        return $this->belongsToMany(User::class,'operate_city_user','cod_operate_city','cod_user');
    }
}
