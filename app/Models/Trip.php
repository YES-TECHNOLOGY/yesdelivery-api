<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable=[
        'id',
        'order',
        'price_order',
        'latitude_origin',
        'longitude_origin',
        'latitude_destination',
        'longitude_destination',
        'distance',
        'estimated_distance',
        'duration',
        'estimated_duration',
        'start_time',
        'end_time',
        'waiting_time',
        'qualification_driver',
        'qualification_client',
        'distance_price',
        'time_price',
        'adicional_price',
        'vehicle_id',
        'conversation_id',
        'status',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden=[
        'created_at',
        'updated_at',
    ];

    /**
     * Return the conversation of trip
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function conversation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Conversation::class,'conversation_id');
    }

    /**
     * Return the vehicle of trip
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vehicle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vehicle::class,'vehicle_id');
    }

}
