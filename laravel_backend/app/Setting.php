<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'tournament_id', 'winTwoStraitSet','setWon','setLost','lastSetUnfinishedOne','lastSetUnfinishedTwo','looseWithWalkOver','winWithWalkOver','numberOfTeamMoveUp','numberOfTeamMoveDown'
    ];
    
}
