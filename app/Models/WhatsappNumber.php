<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappNumber extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable= [
        'name',
        'number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the conversations for the whatsapp number.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class,'recipient_phone_number');
    }

}
