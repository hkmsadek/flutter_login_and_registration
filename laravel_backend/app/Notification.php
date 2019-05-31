<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'team_id', 'seen','msg'
    ];
    public function team(){
    	return $this->belongsTo('App\Team','team_id');
    }
}
