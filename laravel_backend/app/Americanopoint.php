<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Americanopoint extends Model
{
    protected $fillable = ['player_id', 'points', 'match_id', 'groupName', 'round1', 'tournament_id',
    'round2','round3','round4','round5','round6','round7', 'final'];
    public function player(){
        return $this->belongsTo('App\User', 'player_id')->select('id', 'firstName', 'lastName', 'profilePic');
    }
}
