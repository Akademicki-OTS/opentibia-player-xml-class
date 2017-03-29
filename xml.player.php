<?php
/*
Open Tibia XML player class
Version: 0.1.4
Author: Pawel 'Pavlus' Janisio
License: MIT
Github: https://github.com/PJanisio/opentibia-player-xml-class
*/



class xmlPlayer {

//predefined variables

public $showError = 1; //shows backtrace of error message //def: 1
public $errorTxt = ''; //placeholder for error text //def: ''
public $playerName = '';
public $playersDir = '';
public $accountsDir = '';
public $xmlPlayer = NULL;
public $xmlAccount = NULL;
public $account = 0;
public $structurePlayer = '';
public $structureAccount = '';
public $spawn = array();
public $temple = array();
public $skull = '';
public $lastModified = array();
public $health = array();
public $mana = array();

/*
Checks paths and define directories
*/
public function __construct($dataPath) {

$this->dataPath = $dataPath;
	$this->realPath = realpath($this->dataPath);
	
		//check if this is real path and directory
		if($this->realPath == false OR !is_dir($this->realPath)) {
			$this->throwError('Data path invalid!', 1);
		}
			
		//check if there exists player anc accounts directory	
		if(!is_dir($this->realPath.'/players') OR !is_dir($this->realPath.'/accounts') ) {
			$this->throwError('Players/Accounts path is invalid!', 1);
		}
		else
			{
			$this->playersDir = $this->realPath.'/players/';
			$this->accountsDir = $this->realPath.'/accounts/';
			}	

}

/*
Throwing error function
*/
public function throwError($errorTxt, $showError) {
			
			
			if($showError == 1) {
			echo '<b>'.$errorTxt.'</b><br>';
			throw new Exception($this->errorTxt);			
		}

}

/*
Opens xml stream for player and account file
*/
public function prepare($playerName) {
//function to open xml stream, not to open it every time for one player and account

		$playerName = trim(stripslashes($playerName));
		
		$this->xmlPlayer = @simplexml_load_file($this->playersDir.$playerName.'.xml');		
			
			if($this->xmlPlayer === FALSE) //returns not boolean false what the heck
				$this->throwError('Player do not exists!', 1);
				else {
				
				$this->xmlAccount = @simplexml_load_file($this->accountsDir.$this->getAccount().'.xml');
				
			if ($this->xmlAccount === FALSE) 
				$this->throwError('Account file for player do not exists!', 1);

					}
					if($this->xmlAccount AND $this->xmlPlayer)
						return TRUE;
}

/*
Show xml structure for player file
*/
public function showStructurePlayer() {
echo '<pre>', var_dump($this->xmlPlayer), '</pre>';

}

/*
Show xml structure for account file
*/
public function showStructureAccount() {
echo '<pre>', var_dump($this->xmlAccount), '</pre>';

}


/*
Show last modyfied player files (by save or by class action)
*/
public function showLastModifiedPlayers($minutes, $dateFormat = NULL) {

if(!isset($minutes))
$minutes = 5;

if(!isset($dateFormat))
$dateFormat = 'Y-m-d H:i:s';

$files = scandir($this->playersDir);
foreach($files as $file) {
  $stat = stat($this->playersDir.$file);
	
	$lastmod = $stat['mtime'];
		$now = time();
	
	if($now - $lastmod < $minutes*60) 
	$this->lastModified[$file] = date($dateFormat, $lastmod);
		}
		
		return $this->lastModified;

}


/*
Get account number/name
*/
public function getAccount() {

return strval($this->xmlPlayer['account']);

}

/*
Get sex
*/
public function getSex() {

return intval($this->xmlPlayer['sex']);

}


/*
Get look direction
*/
public function getLookdir() {

return intval($this->xmlPlayer['lookdir']);

}


/*
Get experience points
*/
public function getExp() {

return intval($this->xmlPlayer['exp']);

}


/*
Get vocation
*/
public function getVocation() {

return intval($this->xmlPlayer['voc']);

}


/*
Get level
*/
public function getLevel() {

return intval($this->xmlPlayer['level']);

}

/*
Get access
*/
public function getAccess() {

return intval($this->xmlPlayer['access']);

}


/*
Get capacity
*/
public function getCapacity() {

return intval($this->xmlPlayer['cap']);

}


/*
Get bless level
*not standard
*/
public function getBless() {

return intval($this->xmlPlayer['bless']);

}

/*
Get magiclevel
*/
public function getMagicLevel() {

return intval($this->xmlPlayer['maglevel']);

}


/*
Get lastlogin
Available formats at: http://php.net/manual/en/function.date.php
F.e: Y-m-d H:i:s
*/
public function getLastLogin($format = NULL) {

$time = intval($this->xmlPlayer['lastlogin']);

if($format != NULL)
return date($format, $time);
	else
		return $time;

}

/*
Get promoted status
*/
public function getPromotion() {

return intval($this->xmlPlayer['promoted']);

}


/*
Get ban status
*/
public function getBanStatus() {

return intval($this->xmlPlayer['banned']);
}


/*
Get spawn position as an array
*/
public function getSpawnCoordinates() {

$this->spawn['x'] = intval($this->xmlPlayer->spawn['x']);
$this->spawn['y'] = intval($this->xmlPlayer->spawn['y']);
$this->spawn['z'] = intval($this->xmlPlayer->spawn['z']);

return $this->spawn;

}

/*
Get temple position as an array
*/
public function getTempleCoordinates() {

$this->temple['x'] = intval($this->xmlPlayer->temple['x']);
$this->temple['y'] = intval($this->xmlPlayer->temple['y']);
$this->temple['z'] = intval($this->xmlPlayer->temple['z']);

return $this->temple;

}

/*
Get skull type
	SKULL_NONE = 0,
	SKULL_YELLOW = 1,
	SKULL_WHITE = 3,
	SKULL_RED = 4
*/
public function getSkull() {

$this->skull = $this->xmlPlayer->skull['type'];

switch ($this->skull) {
	case 1:
		return $this->skull = 'YELLOW_SKULL';
		break;
	case 3:
		return $this->skull = 'WHITE_SKULL';
		break;
	case 4:
		return $this->skull = 'RED_SKULL';
		break;
	default:
		return $this->skull = 'NO_SKULL';
		break;
					}

}


/*
Get health
now
max
*/
public function getHealth() {

$this->health['now'] = intval($this->xmlPlayer->health['now']);
$this->health['max'] = intval($this->xmlPlayer->health['max']);

return $this->health;

}


/*
Get food level
food maximum level = 1200000 (?)
food > 1000 - gaining health and mana
*/
public function getFoodLevel() {

$this->food = intval($this->xmlPlayer->health['food'] );

return $this->food;

}


/*
Get mana information
*/
public function getMana() {

$this->mana['now'] = intval($this->xmlPlayer->mana['now']);
$this->mana['max'] = intval($this->xmlPlayer->mana['max']);
$this->mana['spent'] = intval($this->xmlPlayer->mana['spent']);

return $this->mana;

}


/*
Magic level percentage
*/
public function getMagicLevelPercent() {

//use mana spent and formula

}


}

?>