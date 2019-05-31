<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tm extends Model
{
    protected $fillable = [
        'user_id', 'league_id'
    ];
    public function user()
    {
    	return $this->belongsTo('App\User')->select('id', 'firstName', 'lastName', 'email',
    	 'profilePic', 'phone');
    }
    protected $casts = [
        'user_id' => 'integer',
        'league_id' => 'integer',
        
        
        
    ];
}
