<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'weekday', 'gameLength','homeTeam','awayTeam','setOne','setTwo','setThree','result','point'
    ];
    protected $casts = [
        'homeTeam' => 'integer',
        'awayTeam' => 'integer',
       
        
        
    ];
}
