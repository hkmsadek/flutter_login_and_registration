<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Clubteam extends Model
{
    protected $fillable = [
        'club_id', 'team_id', 'status'
    ];
    protected $casts = [
        'club_id' => 'integer',
        'team_id' => 'integer',
        
        
    ];
    public function teamName(){
    	return $this->belongsTo('App\Team','team_id');
    }
    public function team(){
    	return $this->belongsTo('App\Team','team_id');
    }
    public function clubdetail(){
    	return $this->belongsTo('App\Club','club_id');
    }
    
    public function group(){
        return $this->hasOne('App\Group','team_id');
    }
}
