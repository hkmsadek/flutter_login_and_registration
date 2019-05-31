<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    protected $fillable = [
        'tournament_id', 'team_id','matchePlayed','won','loss','points','division_id','draw', 'match_id','totalSets','totalGames'
    ];
    public function team()
    {
    	return $this->belongsTo('App\Team', 'team_id');
    }
    protected $casts = [
        'team_id' => 'integer',
        'tournament_id' => 'integer',
        'division_id' => 'integer',
        'match_id' => 'integer',
        
        
    ];
    

}
