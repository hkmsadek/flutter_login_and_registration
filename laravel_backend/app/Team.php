<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
     protected $fillable = [
        'playerOneId', 'playerTwoId', 'teamName'
    ];

    public function player1()
    {
    	return $this->belongsTo('App\User','playerOneId');
    }
    public function player2()
    {
        return $this->belongsTo('App\User','playerTwoId');
    }
    public function club()
    {
    	return $this->hasMany('App\Clubteam');
    }
    protected $casts = [
        'playerOneId' => 'integer',
        'playerTwoId' => 'integer',
        
    ];
    
}
