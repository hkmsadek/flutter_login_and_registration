<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Teamnoti extends Model
{
    protected $fillable = [
        'user_id', 'club_id', 'seen','msg'
    ];
    public function club(){
    	return $this->belongsTo('App\Club','club_id');
    }
}
