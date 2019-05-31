<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Americanoaddedplayer extends Model
{
    protected $fillable = ['groupName', 'tournament_id', 'player_id'];
    public function user (){
        return $this->belongsTo('App\User', 'player_id');
    }
}
