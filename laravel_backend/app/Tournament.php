<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

// this model is the actual sessions of the league

class Tournament extends Model
{
     protected $fillable = [
        'tournamentName','startingTime','playingTime', 'league_id'
    ];


    public function div(){
    	return $this->hasMany('App\Division');
    }
    protected $casts = [
        'league_id' => 'integer',
    ];
    public function league(){
        return $this->belongsTo('App\League', 'league_id');
    }
    public function setting(){
        return $this->hasOne('App\Setting', 'tournament_id');
    }
    public function players(){
        return $this->hasMany('App\Americanoplayer');
    }
    

    
    
}
