<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Fixtureandresult;
use App\Rank;
use App\Setting;
use App\User;
use Auth;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;
class EditResultController extends Controller
{
   public function insertResult($tmId,$divId,$over,$matchId,$homeTeamPoint,$awayTeamPoint)
    {
        $point=Fixtureandresult::where('id',$matchId)->update([
            'setOne' => request('setOne'),
            'setTwo' => request('setTwo'),
            'setThree' => request('setThree'),
            'homeTeamPoint' => $homeTeamPoint,
            'awayTeamPoint' => $awayTeamPoint,
            'over' => $over,
        ]);

        if($over>0){
            $ranks=true;

        }else{
            $ranks=false;
        }
        
        

        $point=[
            'homeTeamPoint' => $homeTeamPoint,
            'awayTeamPoint' => $awayTeamPoint,
            'setOne' => request('setOne'),
            'setTwo' => request('setTwo'),
            'setThree' => request('setThree'),
            'over' => $over,
        ];

        return [
            'ranks' => $ranks,
            'point' => $point,
        ];


        
        
    }	
   public function edit(Request $request)
   {
   		$matchId=$request->id;
        // check if it's 24 hours or not... 
        $user=$this->getCurrentUser($request);
        if(!$user){
            return response()->json([
                'success' => false, 
                'message' => 'User is not found, may be token was not provided.',
            ],200);
        }

        // restricting edit for players within 24 hours time.. 
        if($user->userType=='player'){
            // check for 24 hours.. 
            $today=Carbon::now();
            // get the last updated time... 
            $lastUpdated = Fixtureandresult::where('id',$matchId)->first(['updated_at']);
            $diff=$today->diffInHours($lastUpdated->updated_at);
            if($diff<24){
                return response()->json([
                    'success' => false, 
                    'message' => 'You cannot update the result before 24 hours.',
                ],200);
            }

        }
        


       
        if(request('setOne')=='0-0' && request('setTwo')=='0-0' && request('setThree')=='0-0'){
            $point=[
                'homeTeamPoint' => null,
                'awayTeamPoint' => null,
                'setOne' => null,
                'setTwo' => null,
                'setThree' => null,
                'over' => 0,
            ];
            // update the result back to normal 
            Fixtureandresult::where('id',$matchId)->update([
                'setOne' => null,
                'setTwo' => null,
                'setThree' => null,
                'homeTeamPoint' => null,
                'awayTeamPoint' => null,
                'over' => 0,
            ]);
            // delete the points 
            Rank::where('match_id', $matchId)->delete();
            return response()->json([
                'ranks' => false,
                'point' => $point,

            ],200);

        }

        
        /*get the tournament game settingts*/
        $rules=Setting::where('tournament_id',$request->tournament_id)->get();
        /*if setOne is present and setTwo and setThree is not yet added*/
        if( request('setOne') && !request('setTwo') && !request('setThree') ){ // very first entry
            /*call setOne entry*/
            $result=explode('-', request('setOne'));
            $data = $this->setOneEntry($rules,$result,$matchId);
            
        }elseif ( request('setOne') && request('setTwo') && !request('setThree') ) {
            // enter all sets one by one
            $result=explode('-', request('setOne'));
            $result2=explode('-', request('setTwo'));
            /*first add set one and then add set two*/
            $setOneResult=$this->setOneEntry($rules,$result,$matchId);
            $data = $this->setTwoEntry($rules,$result2,$matchId,$setOneResult);

        }else{ // last set is being inserted
        	 // enter all sets one by one
            $result1=explode('-', request('setOne'));
            $result2=explode('-', request('setTwo'));

            $setOneResult=$this->setOneEntry($rules,$result1,$matchId);
            
            $setTwoResult=$this->setTwoEntry($rules,$result2,$matchId,$setOneResult);
            
            // check if it is already over in this stage
            if($setTwoResult['point']['over']){
                return response()->json($setTwoResult,200);
            }
            
            $result3=explode('-', request('setThree'));
            $data = $this->setThreeEntry($rules,$setTwoResult,$result3,$matchId);
        
        }

        
       return response()->json($data,200);
   }

    public function setOneEntry($rules,$result,$matchId)
    {
          /*check who won homeTeam-awayTeam*/
           $homeTeam=$result[0];
           $awayTeam=$result[1];
          
           if($homeTeam-$awayTeam>=1){ // homeTeam has to win by 2 points difference
                $homeTeamPoint=$rules[0]->setWon;
                $awayTeamPoint=$rules[0]->setLost;
           }elseif($awayTeam-$homeTeam>=1){
                $homeTeamPoint=$rules[0]->setLost;
                $awayTeamPoint=$rules[0]->setWon;
           }else{
                $homeTeamPoint=0;
                $awayTeamPoint=0;
           }
          return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint);
	}
	public function setTwoEntry($rules, $result,$matchId,$setOneResult)
    {
        
         /*check who won homeTeam-awayTeam*/
            $homeTeam=$result[0];
            $awayTeam=$result[1];
            
            $homeTeamPoint=$setOneResult['point']['homeTeamPoint']; 
            $awayTeamPoint=$setOneResult['point']['awayTeamPoint'];
            /*there are three possibilites. Straign win, or draw, other team win*/
            if($homeTeamPoint>0 && $homeTeamPoint>$awayTeamPoint){ // this mean homeTeam won previous game
                /*so check if homeTeam win this game too or not*/
               if($homeTeam-$awayTeam>=1){ // homeTeam won the game so it's a straigt win.
                    $homeTeamPoint=$rules[0]->winTwoStraitSet;
                    $awayTeamPoint=0;

                    $result= $this->insertResult(request('tournament_id'),request('division_id'),1,$matchId,$homeTeamPoint,$awayTeamPoint);
                    /*add the points in point table*/
                    $table= $this->addPoints($homeTeamPoint,$awayTeamPoint);
                    return $result;
                    
                }elseif($awayTeam-$homeTeam>=1){ // away team won this, so no straght win for homeTeam
                    $awayTeamPoint+=$rules[0]->setWon;
                    $homeTeamPoint+=$rules[0]->setLost;
                   //check if the game was over, if over remove the points 
                    if(request('over')){
                        $this->removePoints();
                    }

                    
                   return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint);
                    
                }else{
                    if(request('over')){
                        $this->removePoints();
                    }
                   return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint);
                   
                } 

            }elseif ( $awayTeamPoint>0 && $awayTeamPoint>$homeTeamPoint ) { // awayTeam won the previous game
                /*so check if awayTeam win this game too or not*/
                if($awayTeam-$homeTeam>=1){ // awayteam won the game so it's a straigt win.
                    $homeTeamPoint=0;
                    $awayTeamPoint=$rules[0]->winTwoStraitSet;
                    $this->addPoints($homeTeamPoint,$awayTeamPoint);
                    
                   return $this->insertResult(request('tournament_id'),request('division_id'),1,$matchId,$homeTeamPoint,$awayTeamPoint);
                    
                }elseif($homeTeam-$awayTeam>=1){ // home team won this, so no straght win for homeTeam
                    
                    $awayTeamPoint+=$rules[0]->setLost;
                    $homeTeamPoint+=$rules[0]->setWon;
                    if(request('over')){
                        $this->removePoints();
                    }
                   return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint);
                    
                }else{
                    if(request('over')){
                        $this->removePoints();
                    }
                   return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint);
                   
                }
            }else{
                /*there were no results in the first game*/

                // This case should not possible 

                \Log::info('this is not a possible case');
                if($homeTeam-$awayTeam>=1){ // homeTeam has to win by 2 points difference
                    $awayTeamPoint+=$rules[0]->setLost;
                    $homeTeamPoint+=$rules[0]->setWon;
                        
                }elseif($awayTeam-$homeTeam>=1){
                    $awayTeamPoint+=$rules[0]->setWon;
                    $homeTeamPoint+=$rules[0]->setLost;
                }else{
                    $homeTeamPoint=$homeTeamPoint;
                    $awayTeamPoint=$awayTeamPoint;
                } 
               if(request('over')){
                        $this->removePoints();
                    }
                return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint);
                
            }
        
    }
    public function setThreeEntry($rules,$setTwoResult,$result3,$matchId)
    {
        /*check who won homeTeam-awayTeam*/
        \Log::info('ok hello');
        $homeTeam=$result3[0];
        $awayTeam=$result3[1];
        // getting result from set two
        $homeTeamPoint=$setTwoResult['point']['homeTeamPoint']; 
        $awayTeamPoint=$setTwoResult['point']['awayTeamPoint'];
        
        /*check who won this last set*/

        if($homeTeam-$awayTeam>=2){ // homeTeam has to win by 2 points difference
            // home team is a winner
            //check if game is finnished or not 
            if( ($homeTeam>=6 && $awayTeam<=6) || ($awayTeam>=6 && $homeTeam<=6) ){
                // finished 
                \Log::info('finished game');
                $awayTeamPoint+=$rules[0]->setLost;
                $homeTeamPoint+=$rules[0]->setWon;
            }else{
                \Log::info('unfinished difference 2 won by home team');
                $homeTeamPoint+=$rules[0]->lastSetUnfinishedTwo;
                $awayTeamPoint+=$rules[0]->setLost;
            }

            
            
       }elseif($awayTeam-$homeTeam>=2){ // way team is a winner
            //check if game is finnished or not 
            if( ($homeTeam>=6 && $awayTeam<=6) || ($awayTeam>=6 && $homeTeam<=6) ){
                // finished 
                \Log::info('finished game');
                $awayTeamPoint+=$rules[0]->setWon;
                $homeTeamPoint+=$rules[0]->setLost;
            }else{
                \Log::info('unfinished difference 2 won by away team');
                $homeTeamPoint+=$rules[0]->setLost;
                $awayTeamPoint+=$rules[0]->lastSetUnfinishedTwo;
            }
           
       }else if( ($homeTeam-$awayTeam>=1) || ($awayTeam-$homeTeam>=1))  {  // game diffrence one
            // check if game is finisshed or not
            if( ($homeTeam>=6 && $awayTeam<=6) || ($awayTeam>=6 && $homeTeam<=6) ){
                \Log::info('Game is finisshed with difference one');
                if($homeTeam>$awayTeam){
                    $awayTeamPoint+=$rules[0]->setLost;
                    $homeTeamPoint+=$rules[0]->setWon;
                }else{
                    $awayTeamPoint+=$rules[0]->setWon;
                    $homeTeamPoint+=$rules[0]->setLost;
                }

            }else{
                
                if($homeTeam>$awayTeam){
                    \Log::info('unfinished difference 1 won by home team');
                    $homeTeamPoint+=$rules[0]->lastSetUnfinishedOne;
                    $awayTeamPoint+=$rules[0]->setLost;
                }else{
                    \Log::info('unfinished difference 1 won by way team');
                    $homeTeamPoint+=$rules[0]->setLost;
                    $awayTeamPoint+=$rules[0]->lastSetUnfinishedOne;
                }
               
            }
           
            
       }else{
            // no results 
           \Log::info('last set no results');
           $homeTeamPoint=$homeTeamPoint;
           $awayTeamPoint=$awayTeamPoint;
       }


       $this->addPoints($homeTeamPoint,$awayTeamPoint);
       return $this->insertResult(request('tournament_id'),request('division_id'),1,$matchId,$homeTeamPoint,$awayTeamPoint);

       

    }

    public function addPoints($homeTeamPoint,$awayTeamPoint)
    {
      $homeTeamTotalSets=0;
      $awayTeamTotalSets=0;
      $homeTeamTotalLostSets=0;
      $awayTeamTotalLostSets=0;
      

      $homeTeamTotalGames=0;
      $awayTeamTotalGames=0;
     
        if(request('setOne')){
         $set1=explode('-', request('setOne'));
         if($set1[0]>$set1[1]){
            // home team won
            //set
            $homeTeamTotalSets++;
            $awayTeamTotalLostSets++;
            //game 
            $homeTeamTotalGames+=$set1[0];
            $awayTeamTotalGames+=$set1[1];
            

         }else{
             // away team won . //set
            $awayTeamTotalSets++;
            $homeTeamTotalLostSets++;

            // games
            $homeTeamTotalGames+=$set1[0];
            $awayTeamTotalGames+=$set1[1];
            
         }
      }
      if(request('setTwo')){
         $set1=explode('-', request('setTwo'));
         if($set1[0]>$set1[1]){
            // home team won
            //set
            $homeTeamTotalSets++;
            $awayTeamTotalLostSets++;
            //game 
           $homeTeamTotalGames+=$set1[0];
            $awayTeamTotalGames+=$set1[1];
            

         }else{
             // away team won . //set
            $awayTeamTotalSets++;
            $homeTeamTotalLostSets++;

            // games
            $homeTeamTotalGames+=$set1[0];
            $awayTeamTotalGames+=$set1[1];
            
         }
      }
      if(request('setThree')){
         $set1=explode('-', request('setThree'));
         if($set1[0]>$set1[1]){
            // home team won
            //set
            $homeTeamTotalSets++;
            $awayTeamTotalLostSets++;
            //game 
            $homeTeamTotalGames+=$set1[0];
            $awayTeamTotalGames+=$set1[1];
            

         }else{
             // away team won . //set
            $awayTeamTotalSets++;
            $homeTeamTotalLostSets++;

            // games
            $homeTeamTotalGames+=$set1[0];
            $awayTeamTotalGames+=$set1[1];
            
         }
      }

       

      $homeTeamTotalSets=$homeTeamTotalSets-$homeTeamTotalLostSets;
      
      $awayTeamTotalSets=$awayTeamTotalSets-$awayTeamTotalLostSets;
       $this->addHomeTeamPoints($homeTeamPoint,$awayTeamPoint,$homeTeamTotalSets,$homeTeamTotalGames);
       $this->addAwayTeamPoints($homeTeamPoint,$awayTeamPoint,$awayTeamTotalSets,$awayTeamTotalGames);

    }
    public function removePoints(){

        Rank::where('match_id', request('match_id'))->delete();
    }
    public function addHomeTeamPoints($homeTeamPoint,$awayTeamPoint,$totalSets,$totalGames)
    {
        	$won=0;
            $loss=0;
            $draw=0;
            // determine who won this match
            if($homeTeamPoint>$awayTeamPoint){ // home team won the game
                $won=1;
            }elseif($homeTeamPoint<$awayTeamPoint){ // home team lost the game
                $loss=1;
            }else{
                $won=0;
                $loss=0;
                $draw=1;
            }
            // check if there are results already or not, if not create one 
            $isExists=Rank::where('match_id', request('match_id'))->where('team_id', request('homeTeamId'))->count();
            if($isExists){
               
                $rank=Rank::where('match_id',request('match_id'))->where('team_id',request('homeTeamId'))->update([
                     'tournament_id' =>request('tournament_id'), 
                     'team_id'=>request('homeTeamId'),
                     'won'=>$won,
                     'loss'=>$loss,
                     'draw'=>$draw,
                     'points'=>$homeTeamPoint,
                     'division_id'=>request('division_id'),
                     'match_id'=>request('match_id'),
                     'totalSets'=>$totalSets,
                     'totalGames'=>$totalGames,
                ]);
            }else{
                \Log::info(request('match_id'));
                $rank=Rank::create([
                     'tournament_id' =>request('tournament_id'), 
                     'team_id'=>request('homeTeamId'),
                     'won'=>$won,
                     'loss'=>$loss,
                     'draw'=>$draw,
                     'points'=>$homeTeamPoint,
                     'division_id'=>request('division_id'),
                     'match_id'=>request('match_id'),
                     'totalSets'=>$totalSets,
                     'totalGames'=>$totalGames,
                ]);
            }

            


    }
    public function addAwayTeamPoints($homeTeamPoint,$awayTeamPoint,$totalSets,$totalGames)
    {
        $won=0;
        $loss=0;
        $draw=0;
        // determine who won this match
        if($homeTeamPoint<$awayTeamPoint){ // away team won the game
            $won=1;
        }elseif($homeTeamPoint>$awayTeamPoint){ // away team lost the game
            $loss=1;
        }else{
            $won=0;
            $loss=0;
            $draw=1;
        }
        $isExists=Rank::where('match_id', request('match_id'))->where('team_id', request('awayTeamId'))->count();
            if($isExists){
                
                $rank=Rank::where('match_id',request('match_id'))->where('team_id',request('awayTeamId'))->update([
                     'tournament_id' =>request('tournament_id'), 
                     'team_id'=>request('awayTeamId'),
                     'won'=>$won,
                     'loss'=>$loss,
                     'draw'=>$draw,
                     'points'=>$awayTeamPoint,
                     'division_id'=>request('division_id'),
                     'match_id'=>request('match_id'),
                     'totalSets'=>$totalSets,
                     'totalGames'=>$totalGames,
                ]);
            }else{
                $rank=Rank::create([
                     'tournament_id' =>request('tournament_id'), 
                     'team_id'=>request('awayTeamId'),
                     'won'=>$won,
                     'loss'=>$loss,
                     'draw'=>$draw,
                     'points'=>$awayTeamPoint,
                     'division_id'=>request('division_id'),
                     'match_id'=>request('match_id'),
                     'totalSets'=>$totalSets,
                     'totalGames'=>$totalGames,
                ]);
            }

      
    }
    public function getCurrentUser(Request $request){
        if(!User::checkToken($request)){
            return response()->json([
             'message' => 'Token is required'
            ],422);
        }
         
         $user = JWTAuth::parseToken()->authenticate();
         return $user;
     }
    
}
