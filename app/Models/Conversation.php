<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable=[
        'id',
        'recipient_phone_number',
        'display_phone_number',
        'phone_number_id',
        'status',
        'status_user',
        'type_order',
        'latitude',
        'longitude',
        'reference',
        'deleted',
        'cod_user',
        'operate_city_id'
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden=[
        'recipient_phone_number',
        'display_phone_number',
        'status_user',
        'phone_number_id',
        'cod_user',
        'deleted',
        'created_at',
        'updated_at',
    ];

    /**
     * Return the messages of conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Messages::class);
    }

    /**
     * Return the phone number of conversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function phoneNumber(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WhatsappNumber::class,'recipient_phone_number','id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class,'cod_user','id');
    }

    public function operateCity(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OperateCity::class,'operate_city_id','id');
    }
}
