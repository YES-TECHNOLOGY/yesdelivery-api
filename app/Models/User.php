<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'identification',
        'name',
        'lastname',
        'email',
        'gender',
        'active',
        'password',
        'photography',
        'cod_rol',
        'driving_license_photography',
        'remember_token',
        'remember_token_valid_time'
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
     *
     * Returns the projects that the user has
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projects(){
        return $this->belongsToMany(Project::class);
    }

    /**
     *
     * Return the role of user
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rol(){
        return $this->belongsTo(Rol::class,'cod_rol');
    }
}
