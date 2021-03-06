<?php
    error_reporting(0);
	header("Content-type: application/json"); 
	header("Access-Control-Allow-Origin: *");
	$fileName = 'data.json';
	
	function throwError($msg){
	    $result = [];
	    $result['message'] = $msg;
        $result['code'] = 500;
        echo json_encode($result);
        die();
	}
	function throwSuccess($data){
	    $result = [];
	    $result['data'] = $data;
        $result['code'] = 200;
        echo json_encode($result);
        die();
	}
	
	if(!isset($_GET['method'])) throwError("No recognized API call ".$_GET['method']." please use one of the known methods.");

    if($_GET['method'] == 'pending_attempts'){
        
        
        $fileContent = file_get_contents($fileName);
        if(!$fileContent) throwError('Imposible to read the database file');
        $jSON = json_decode($fileContent);
        
        if(isset($_GET['p1']) || $_GET['p1']!=''){
            $jSON->pending_attempts = array_values(array_filter($jSON->pending_attempts, function($item){
                return ($item->level == $_GET['p1']);
            }));
        }
        
        throwSuccess($jSON);
    }
    if($_GET['method'] == 'get_levels' && !isset($_GET['p1'])){
        $files = scandir('levels/');
        
        $levels = array_map(function($item){
            $levelJson = file_get_contents('levels/'.$item);
            $levelInfo = json_decode($levelJson);
            return [
                "slug" => preg_replace('/\\.[^.\\s]{3,4}$/', '', $item),
                "difficulty" => $levelInfo->difficulty,
                "thumb" => $levelInfo->thumb,
                "title" => $levelInfo->title
            ];
        },array_slice($files,2));
        throwSuccess($levels);
    }
    else if($_GET['method'] == 'get_levels' && isset($_GET['p1'])){
        $levelJson = file_get_contents('levels/'.$_GET['p1'].'.json');
        $levelInfo = json_decode($levelJson);
        if(!$levelInfo) throwError(json_last_error());
        throwSuccess($levelInfo);
    }
    else if($_GET['method'] == 'add_attempt'){
        
        $incomingJSON = file_get_contents('php://input');
        $incomingAttempt = json_decode($incomingJSON);
        
        if(!isset($incomingAttempt->username)) throwError('Missing username');
        if(!isset($incomingAttempt->level)) throwError('Missing level');
        if(!isset($incomingAttempt->character)) throwError('Missing character');
        if(!isset($incomingAttempt->commands) || !is_array($incomingAttempt->commands)) throwError('Missing commands or is not an array');
        
        /*
        if ( !preg_match('/^[A-Za-z][A-Za-z0-9]{5,31}$/', $incomingAttempt->username) ) throwError('Invalid username "'.$incomingAttempt->username.'" (only letters and numbers permited)');
        */
        
        foreach($incomingAttempt->commands as $move)
        {
            if(!in_array($move, ["runRight", "runLeft", "jumpRight", "jumpLeft", "climb", "open", "push", "kill"]))
            {
                if(is_string($move)) throwError('The movement '.$move.' its not allowed');
                else throwError('The movements should an array of strings');
            }
        }

        //if(!in_array($incomingAttempt->character, ["batman"])) throwError('The avatar slug '.$incomingAttempt->character.' its not valid');
        
        $fileContent = file_get_contents($fileName);
        if(!$fileContent) throwError('Imposible to read the database file');
        
        $jSON = json_decode($fileContent);
		array_push($jSON->pending_attempts, [
		    "id" => uniqid(),
		    "created_at" => time(),
		    "username" => $incomingAttempt->username,
		    "level" => $incomingAttempt->level,
		    "character" => $incomingAttempt->character,
		    "commands" => $incomingAttempt->commands
		]);

        file_put_contents($fileName, json_encode($jSON));
        
        $jSON->pending_attempts = array_values(array_filter($jSON->pending_attempts, function($item){ return ($item->level == $incomingAttempt->level); }));
        throwSuccess($jSON);
    }
    else if($_GET['method'] == 'delete_attempt'){
        
        $incomingJSON = file_get_contents('php://input');
        $incomingAttempt = json_decode($incomingJSON);
        
        if(!isset($incomingAttempt->id)) throwError('Missing id to delete');
        
        $fileContent = file_get_contents($fileName);
        if(!$fileContent) throwError('Imposible to read the database file');
        
        $jSON = json_decode($fileContent);
        $newPendingAttempts = [];
        $level = null;
		foreach($jSON->pending_attempts as $attempt)
		{
		    if($attempt->id != $incomingAttempt->id) $newPendingAttempts[] = $attempt;
		    else $level = $attempt->level;
		}
		
		if(count($jSON->pending_attempts) == count($newPendingAttempts)) throwError('The attempt with id '.$incomingAttempt->id.' was not found');

        $jSON->pending_attempts = $newPendingAttempts;
        file_put_contents($fileName, json_encode($jSON));
        
        //filter the attempts for the specific level
        if($level) $jSON->pending_attempts = array_values(array_filter($jSON->pending_attempts, function($item){ return ($item->level == $level); }));
        
        throwSuccess($jSON);
    }
    else if($_GET['method'] == 'clean_attempts'){
        
        $jSON = [];
        $jSON["pending_attempts"] = [];
        file_put_contents($fileName, json_encode($jSON));
        
        throwSuccess('ok');
    }
    else{
        throwError("No recognized API call '".$_GET['method']."' please use one of the known methods.");
    }