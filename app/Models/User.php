<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type_identification',
        'identification',
        'name',
        'lastname',
        'email',
        'gender',
        'cellphone',
        'date_birth',
        'cod_nationality',
        'cod_dpa',
        'address',
        'size',
        'password',
        'photography',
        'identification_front_photography',
        'identification_back_photography',
        'verified',
        'email_verified_at',
        'remember_token_valid_time',
        'active',
        'google_id',
        'cod_rol',
        'cod_dpa'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'email_verified_at',
        'remember_token',
        'remember_token_valid_time',
        'google_id',
        'created_at',
        'updated_at',
        'deleted',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Return the vehicles of user
     *
     * @return HasMany
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class,'cod_user');
    }

    /**
     *
     * Return the role of user
     *
     * @return BelongsTo
     */
    public function rol(){
        return $this->belongsTo(Rol::class,'cod_rol');
    }

    /**
     *
     * Return the role of user
     *
     * @return BelongsTo
     */
    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Country::class,'cod_nationality');
    }

    /**
     *
     * Return the role of user
     *
     * @return BelongsTo
     */
    public function dpa(): BelongsTo
    {
        return $this->belongsTo(Dpa::class,'cod_dpa');
    }

    /**
     * Return the conversations of user
     *
     * @return HasMany
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class,'cod_user');
    }

    /**
     * Return the operate cities of user working
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function operateCities(){
        return $this->belongsToMany(OperateCity::class,'operate_city_user','cod_user','cod_operate_city');
    }

}
