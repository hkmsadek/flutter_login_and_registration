<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
     protected $fillable = [
        'leagueName', 'leagueDesc', 'competitionType','club_id'
    ];

    
    public function tournament(){
    	return $this->hasMany('App\Tournament');
    }
    public function managers(){
    	return $this->hasMany('App\Tm','league_id');
    }
    public function tr(){
    	return $this->hasMany('App\Tournament');
    }
    public function ismanager(){
        return $this->hasMany('App\Tm');
    }
    public function images(){
        return $this->hasMany('App\Divimage');
    }
    public function image(){
        return $this->hasOne('App\Divimage');
    }
    public function club(){
        return $this->belongsTo('App\Club', 'club_id');
    }


}
