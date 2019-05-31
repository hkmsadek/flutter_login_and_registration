<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $fillable = [
        'tournament_id', 'divisionName', 'isPlayOff'
    ];
    public function rank()
    {
    	return $this->hasMany('App\Rank', 'division_id')->selectRaw('id, tournament_id,team_id,division_id, sum(won) as won, sum(loss) as loss, sum(draw) as draw, sum(points) as points, sum(matchePlayed) as matchePlayed, sum(totalSets) as totalSets, sum(totalGames) totalGames')
    	->orderBy('points','desc')->orderBy('totalSets','desc')->orderBy('totalGames', 'desc')->groupBy('team_id');
    }
    public function tmsch(){
    	return $this->hasOne('App\Tmschedule', 'division_id');
    }
    protected $casts = [
        'tournament_id' => 'integer',
       
        
        
    ];
    
}
