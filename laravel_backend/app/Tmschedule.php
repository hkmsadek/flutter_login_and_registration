<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tmschedule extends Model
{
    protected $fillable = [
        'tournament_id', 'divisionName', 'gameLength','startingDate','startingTime','weekday','division_id'
    ];

    public function div(){
    	return $this->belongsTo('App\Division','division_id')->select('id', 'divisionName');
    }
    protected $casts = [
        'tournament_id' => 'integer',
        'division_id' => 'integer',
        
        
        
    ];
}
