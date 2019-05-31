<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Americanomatches extends Model
{
    protected $fillable = [
        'playerOne',
        'playerTwo' ,
        'playerThree' ,
        'playerFour' ,
        'round' , 
        'groupName',
        'tournament_id',
        'court_id'
       
    ];

    public function player1(){
        return $this->belongsTo('App\User', 'playerOne')->select('id', 'firstName', 'lastName', 'profilePic');
    }
    public function player2(){
        return $this->belongsTo('App\User', 'playerTwo')->select('id', 'firstName', 'lastName', 'profilePic');
    }
    public function player3(){
        return $this->belongsTo('App\User', 'playerThree')->select('id', 'firstName', 'lastName', 'profilePic');
    }
    public function player4(){
        return $this->belongsTo('App\User', 'playerFour')->select('id', 'firstName', 'lastName', 'profilePic');
    }
    public function court(){
        return $this->belongsTo('App\Americanocourt', 'court_id');
    }
}
