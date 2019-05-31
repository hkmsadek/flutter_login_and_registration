<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = ['club_id','eventImg','eventName', 'eventTime', 'eventLocation', 'eventDate', 'eventFee','eventLimit','eventDescription'];
    protected $casts = [
        'eventTime' => 'hh:mm'
    ];
    public function singedup(){
        return $this->hasMany('App\EventInvitation');
    }
    public function acceptedUsers(){
        return $this->hasMany('App\EventInvitation')->where('status', 'accepted')->orderBy('updated_at', 'desc')->limit(3);
    }
    public function invitation(){
        return $this->hasOne('App\EventInvitation');
    }

}
