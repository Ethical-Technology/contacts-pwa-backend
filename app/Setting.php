<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        'twilio_sid', 'twilio_token', 'twilio_number','timezone'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */

    public function user(){
        return $this->belongsTo('App\User');
    }
}
