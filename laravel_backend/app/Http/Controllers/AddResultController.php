<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tmschedule;
use App\Group;
use App\Court;
use App\Fixtureandresult;
use App\Rank;
use App\Setting;
use  DB;
class AddResultController extends Controller
{
    public function addResult(Request $request)
    {
        /*
            TO ADD RESULT PROPERTIES NEEDED 
            setOne:null,
            setTwo:null,
            setThree:null,
            id:null,  // id AND match_id ARE SAME... 
            match_id:null, // id AND match_id ARE SAME... 
            homeTeamPoint:null,
            awayTeamPoint:null,
            tournament_id:null,
            homeTeamId:null,
            awayTeamId:null,
            division_id:null,
            isWo:0, // always default 0

            EXAMPLE REQUEST BODY 

            setOne:"7-3" // 7 IS HOME TEAM POINT AND 3 IS AWAY TEAM AND CONCAT THE RESULT 7-3
            setTwo:null 
            setThree:null
            id:425
            match_id:425
            homeTeamPoint:null  // WHEN U ADD FIRST RESULT, IT IS NULL, BUT IN SECOND AND THIRD YOU WILL HAVE A VALUE SO U NEED TO SEND IT WITH THE REQUIREST
            awayTeamPoint:null // WHEN U ADD FIRST RESULT, IT IS NULL, BUT IN SECOND AND THIRD YOU WILL HAVE A VALUE SO U NEED TO SEND IT WITH THE REQUIREST
            homeTeamId:1
            awayTeamId:6
            tournament_id:"83"
            division_id:104
            isWo:0


            EXAMPLE RESPONSES 
            
            {
                "ranks": false, // MEANS THE GAME IS NOT FINISHED SO THERE IS NO RANKS FOR THIS
                "point": {
                    "homeTeamPoint": 1, // REMIMBER TO ADD THIS RESULT IN YOUR SECOND AND THIRD RESULT SET ADDING
                    "awayTeamPoint": 0,  // REMIMBER TO ADD THIS RESULT IN YOUR SECOND AND THIRD RESULT SET ADDING
                    "setOne": "7-2",
                    "setTwo": null,
                    "setThree": null,
                    "over": 0 // GAME IS STILL ON. ONCE ITS 1 THEN GAME IS OVER AND NO MORE ADDING RESULTS
                }
            }
            
        */
        
        if(!$request->has('isWo') || !$request->has('division_id') || !$request->has('tournament_id') || !$request->has('awayTeamId')
            || !$request->has('homeTeamId') || !$request->has('awayTeamPoint') || !$request->has('homeTeamPoint') || !$request->has('match_id')
            || !$request->has('id') || !$request->has('setThree') || !$request->has('setTwo') || !$request->has('setOne')
        ){
            return response()->json([
                'success' => false, 
                'message' => 'All request body is required'
            ],200);
        }

        $matchId=$request->id;

        // see if match is already over or not... 
        $isOver=Fixtureandresult::where('id', $matchId)->where('over', 1)->count();
        if($isOver>0){
            return response()->json([
                'success' => false, 
                'message' => 'The result of this match is over and someone has already entered the result.'
            ],200);
        }
       

        /*get the tournament game settingts*/
        $rules=Setting::where('tournament_id',$request->tournament_id)->get();
        // check if it is a walkover or not.. 
        if(request('isWo')>0){
            // it's a walk over so insert result and points from here and return 
            $data = $this->setWalkOver($rules,$matchId);
            return response()->json([
                'success' => true, 
                'message' => 'Result has been added.'
            ],200);
        }

        /*if setOne is present and setTwo and setThree is not yet added*/
        if(  request('setOne') && !request('setTwo') && !request('setThree') ){ // very first entry
            /*call setOne entry*/
            $result=explode('-', request('setOne'));
            $data = $this->setOneEntry($rules,$result,$matchId);
            
        }elseif ( request('setOne') && request('setTwo') && !request('setThree') ) {
            $result=explode('-', request('setTwo'));
            $data = $this->setTwoEntry($rules,$result,$matchId);

        }else{ // last set is being inserted
            $result=explode('-', request('setThree'));
           $data = $this->setThreeEntry($rules,$result,$matchId);
        
        }

        
       return response()->json([
            'success' => true, 
            'message' => 'Result has been added.'
        ],200);

    }

    public function insertResult($tmId,$divId,$over,$matchId,$homeTeamPoint,$awayTeamPoint, $setOne=null, $setTwo=null,$setThree=null)
    {
        $point=Fixtureandresult::where('id',$matchId)->update([
            'setOne' => $setOne,
            'setTwo' => $setTwo,
            'setThree' => $setThree,
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
            'setOne' => $setOne,
            'setTwo' => $setTwo,
            'setThree' => $setThree,
            'over' => $over,
        ];

        return [
            'ranks' => $ranks,
            'point' => $point,
        ];

        
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
          return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'),request('setThree'));

         
           
    }
    public function setTwoEntry($rules,$result,$matchId)
    {
        /*check who won homeTeam-awayTeam*/
            $homeTeam=$result[0];
            $awayTeam=$result[1];
            $homeTeamPoint=request('homeTeamPoint');
            $awayTeamPoint=request('awayTeamPoint');
            /*there are three possibilites. Straign win, or draw, other team win*/
            if($homeTeamPoint>0 && $homeTeamPoint>$awayTeamPoint){ // this mean homeTeam won previous game
                /*so check if homeTeam win this game too or not*/
               if($homeTeam-$awayTeam>=1){ // homeTeam won the game so it's a straigt win.
                    $homeTeamPoint=$rules[0]->winTwoStraitSet;
                    $awayTeamPoint=0;

                    
                    /*add the points in point table*/
                    $table= $this->addPoints($homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'),request('setThree'),request('homeTeamId'),request('awayTeamId'), request('tournament_id'),request('division_id'));
                    $result= $this->insertResult(request('tournament_id'),request('division_id'),1,$matchId,$homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'));
                    return $result;
                    
                }elseif($awayTeam-$homeTeam>=1){ // away team won this, so no straght win for homeTeam
                    $awayTeamPoint+=$rules[0]->setWon;
                    $homeTeamPoint+=$rules[0]->setLost;

                   return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'));
                    
                }else{
                   return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'));
                   
                } 

            }elseif ( $awayTeamPoint>0 && $awayTeamPoint>$homeTeamPoint ) { // awayTeam won the previous game
                /*so check if awayTeam win this game too or not*/
                if($awayTeam-$homeTeam>=1){ // awayteam won the game so it's a straigt win.
                    $homeTeamPoint=0;
                    $awayTeamPoint=$rules[0]->winTwoStraitSet;
                    $this->addPoints($homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'),request('setThree'),request('homeTeamId'),request('awayTeamId'), request('tournament_id'),request('division_id'));
                    
                   return $this->insertResult(request('tournament_id'),request('division_id'),1,$matchId,$homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'));
                    
                }elseif($homeTeam-$awayTeam>=1){ // home team won this, so no straght win for homeTeam
                    
                    $awayTeamPoint+=$rules[0]->setLost;
                    $homeTeamPoint+=$rules[0]->setWon;
                   return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'));
                    
                }else{
                   return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'));
                   
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
               
                return $this->insertResult(request('tournament_id'),request('division_id'),0,$matchId,$homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'),request('setThree'));
                
            }
    }
    public function setThreeEntry($rules,$result,$matchId)
    {
        /*check who won homeTeam-awayTeam*/
        
        $homeTeam=$result[0];
        $awayTeam=$result[1];
        $homeTeamPoint=request('homeTeamPoint');
        $awayTeamPoint=request('awayTeamPoint');
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


       $this->addPoints($homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'),request('setThree'),request('homeTeamId'),request('awayTeamId'), request('tournament_id'),request('division_id'));
       return $this->insertResult(request('tournament_id'),request('division_id'),1,$matchId,$homeTeamPoint,$awayTeamPoint,request('setOne'),request('setTwo'),request('setThree'));

       

    }
    public function setWalkOver($rules,$matchId){
        // check who won this game 
        if(request('homeTeamRes')>0){
            // add the results and then points 
            $homeTeamPoint=$rules[0]->winWithWalkOver;
            $awayTeamPoint=$rules[0]->looseWithWalkOver;
           
            // add the points 
            $this->addPoints($homeTeamPoint,$awayTeamPoint,'6-0','6-0','',request('homeTeamId'),request('awayTeamId'), request('tournament_id'),request('division_id'));
            // add the results 
            return $this->insertResult(request('tournament_id'),request('division_id'),1,request('match_id'),$homeTeamPoint,$awayTeamPoint,'6-0','6-0','');

        }else{
            $homeTeamPoint=$rules[0]->looseWithWalkOver;
            $awayTeamPoint=$rules[0]->winWithWalkOver;
           
            // add the points 
            $this->addPoints($homeTeamPoint,$awayTeamPoint,'0-6','0-6','',request('homeTeamId'),request('awayTeamId'), request('tournament_id'),request('division_id') );
            // add the results 
            return $this->insertResult(request('tournament_id'),request('division_id'),1,request('match_id'),$homeTeamPoint,$awayTeamPoint,'0-6','0-6','');
        }
    } 

    public function addPoints($homeTeamPoint,$awayTeamPoint, $setOne, $setTwo, $setThree)
    {
        
      $homeTeamTotalSets=0;
      $awayTeamTotalSets=0;
      $homeTeamTotalLostSets=0;
      $awayTeamTotalLostSets=0;
      

      $homeTeamTotalGames=0;
      $awayTeamTotalGames=0;
     
      if($setOne){
        $set1=explode('-', $setOne);
        if($set1[0]>$set1[1]){
           // home team won
           //set
           if($set1[0]-$set1[1]>=2){
               $homeTeamTotalSets++;
               $awayTeamTotalLostSets++;
           }
           //game 
           $homeTeamTotalGames+=$set1[0];
           $awayTeamTotalGames+=$set1[1];
           

        }
        if($set1[0]<$set1[1]){
            // away team won . //set
            if($set1[1]-$set1[0]>=2){
               $awayTeamTotalSets++;
               $homeTeamTotalLostSets++;
           }
           

           // games
           $homeTeamTotalGames+=$set1[0];
           $awayTeamTotalGames+=$set1[1];
           
        }
     }
     if($setTwo){
        $set1=explode('-', $setTwo);
        if($set1[0]>$set1[1]){
           // home team won
           //set
           if($set1[0]-$set1[1]>=2){
               $homeTeamTotalSets++;
               $awayTeamTotalLostSets++;
           }
           //game 
           $homeTeamTotalGames+=$set1[0];
           $awayTeamTotalGames+=$set1[1];
           

        }
       if($set1[0]<$set1[1]){
            // away team won . //set
           
           if($set1[1]-$set1[0]>=2){
               $awayTeamTotalSets++;
               $homeTeamTotalLostSets++;
           }
           

           // games
           $homeTeamTotalGames+=$set1[0];
           $awayTeamTotalGames+=$set1[1];
           
        }
     }
     if($setThree){
        $set1=explode('-', $setThree);
        if($set1[0]>$set1[1]){
           // home team won
           //set
           
           if($set1[0]-$set1[1]>=2){
               $homeTeamTotalSets++;
               $awayTeamTotalLostSets++;
           }
           //game 
           $homeTeamTotalGames+=$set1[0];
           $awayTeamTotalGames+=$set1[1];
           

        }
        if($set1[0]<$set1[1]){
            // away team won . //set
            if($set1[1]-$set1[0]>=2){
               $awayTeamTotalSets++;
               $homeTeamTotalLostSets++;
           }
           

           // games
           $homeTeamTotalGames+=$set1[0];
           $awayTeamTotalGames+=$set1[1];
           
        }
     }
       

      $homeTeamTotalSets=$homeTeamTotalSets-$homeTeamTotalLostSets;
      $awayTeamTotalSets=$awayTeamTotalSets-$awayTeamTotalLostSets;

      $hTeamTotalGames=$homeTeamTotalGames-$awayTeamTotalGames;
      $aTeamTotalGames=$awayTeamTotalGames-$homeTeamTotalGames;


      
       $this->addHomeTeamPoints($homeTeamPoint,$awayTeamPoint,$homeTeamTotalSets,$hTeamTotalGames);
       $this->addAwayTeamPoints($homeTeamPoint,$awayTeamPoint,$awayTeamTotalSets,$aTeamTotalGames);

    }
    public function addHomeTeamPoints($homeTeamPoint,$awayTeamPoint, $totalSets,$totalGames)
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
