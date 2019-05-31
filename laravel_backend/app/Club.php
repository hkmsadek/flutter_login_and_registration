<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
     protected $fillable = [
        'user_id', 'clubName','clubDesc'
    ];
    protected $casts = [
        'user_id' => 'integer',
    ];

    public function tournament(){
    	return $this->hasOne('App\Tournament');
    }
    public function league(){
        return $this->hasMany('App\League')->orderBy('created_at', 'desc');
    }
    public function leagueCount(){
        return $this->hasMany('App\League')->selectRaw('id, club_id, count(club_id) as total')
        ->groupBy('club_id');
    }
    
    public function users(){
    	return $this->belongsTo('App\User','user_id');
    }
}
