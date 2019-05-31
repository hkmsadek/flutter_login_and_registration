<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'tournament_id', 'division_id', 'team_id','old_div','progress'
    ];
    public function team(){
    	return $this->belongsTo('App\Team', 'team_id');
    }
    public function div(){
        return $this->belongsTo('App\Division', 'division_id');
    }
    public function olddiv(){
    	return $this->belongsTo('App\Division', 'old_div_id');
    }
    public function tmsch(){
    	return $this->belongsTo('App\Tmschedule', 'division_id');
    }
    public function divname(){
        return $this->hasOne('App\Division','id');
    }
}
