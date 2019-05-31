<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Fixtureandresult extends Model
{
    protected $fillable = [
        'division_id', 
        'tmschedule_id',
        'tournament_id', 
        'round',
        'homeTeam', 
        'awayTeam',
        'setOne', 
        'setTwo',
        'setThree', 
        'setFour',
        'setFive', 
        'result',
        'point',
        'court_id',
        'awayTeamPoint',
        'homeTeamPoint',
        'homeTeamSetWon',
        'awayTeamSetWon',
        'homeTeamGameWon',
        'awayTeamGameWon',
        'over'
    ];
    protected $casts = [
        'division_id' => 'integer',
        'tmschedule_id' => 'integer',
        'tournament_id' => 'integer',
        'round' => 'integer',
        'homeTeam' => 'integer',
        'awayTeam' => 'integer',
        'court_id' => 'integer',
        'over' => 'integer',
        
    ];
    public function date(){
        return $this->belongsTo('App\Tmschedule', 'tmschedule_id');
    }
    public function homeTeam(){
        return $this->belongsTo('App\Team', 'homeTeam');
    }
    public function awayTeam(){
        return $this->belongsTo('App\Team', 'awayTeam');
    }
    public function home(){
        return $this->belongsTo('App\Team', 'homeTeam');
    }
    public function away(){
        return $this->belongsTo('App\Team', 'awayTeam');
    }
    public function div(){
        return $this->belongsTo('App\Division', 'division_id');
    }
    public function court(){
        return $this->belongsTo('App\Court', 'court_id');
    }
    public function tr(){
        return $this->belongsTo('App\Tournament', 'tournament_id');
    }
   

}
