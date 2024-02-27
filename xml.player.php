<?php
/*
Open Tibia XML player class
Version: 0.2.12
Author: Pawel 'Pavlus' Janisio
License: MIT
Github: https://github.com/PJanisio/opentibia-player-xml-class
*/



class xmlPlayer {

//predefined variables
//private
private $dataPath = '';
private $realPath = '';
private $housesPath = '';
private $showError = 1; //shows backtrace of error message //def: 1
//public
//strings
public $errorTxt = ''; //placeholder for error text //def: ''
public $playerName = '';
public $skull = '';
public $playersDir = '';
public $accountsDir = '';
public $lastElement = ''; //double check if will be needed
public $xmlPlayerFilePath = ''; //exact path for PREPARED player
public $xmlAccountFilePath = ''; //exact path for PREPARED account
public $structurePlayer = '';
public $structureAccount = '';
public $vocationName = '';
//bools
public $xmlPlayer = NULL; //handler for player
public $xmlAccount = NULL; //handler for account
//ints and floats
public $account = 0;
public $food = 0;
public $reqMana = 0;
public $magicLevelPercent = 0;
public $expNextLevel = 0;
public $expPercNextLevel = 0;
public $expLevel = 0;
//arrays
public $skills = array();
public $characters = array(); //names of other players on the same account
public $spawn = array();
public $temple = array();
public $frags = array();
public $lastModified = array();
public $health = array();
public $mana = array();
public $storage = array();
public $ban = array(); //ban status,start,end,comment
public $dead = array();
public $house = array();

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
			$this->housesPath = $this->realPath.'/houses/';
			}	

}

/*
Throwing error function
*/
public function throwError($errorTxt, $showError) {
			
			
			if($showError == 1) {
			echo $errorTxt;
			throw new Exception($this->errorTxt);			
		}

}

/*
Opens xml stream for player and account file
*/
public function prepare($playerName) {
//function to open xml stream

		$playerName = trim(stripslashes($playerName));
			$this->xmlPlayerFilePath = $this->playersDir.$playerName.'.xml';
		
			$this->xmlPlayer = simplexml_load_file($this->xmlPlayerFilePath, 'SimpleXMLElement', LIBXML_PARSEHUGE);		
			
			if($this->xmlPlayer === FALSE) //returns not boolean false what the heck
				$this->throwError('Player do not exists!', 1);
				else {
				$this->xmlAccountFilePath = $this->accountsDir.$this->getAccount().'.xml';
				$this->xmlAccount = simplexml_load_file($this->xmlAccountFilePath, 'SimpleXMLElement', LIBXML_PARSEHUGE);
				
			if ($this->xmlAccount === FALSE) 
				$this->throwError('Account file for player do not exists!', 1);

					}
					if($this->xmlAccount AND $this->xmlPlayer)
						return TRUE;
						
	//no need to close the file manually, will be auto-closed after reading content!
}

/*
Get functions
*/


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
Get premium days
*/
public function getPremDays() {

return intval($this->xmlAccount['premDays']);

}

/*
Get other characters on the same account
*/
public function getCharacters() {

for($k =0; $k < count($this->xmlAccount->characters->character); $k++) {
    $character = $this->xmlAccount->characters->character[$k]['name'];
            array_push($this->characters, $character);
            
    }
    
       return $this->characters; //array of objects

}

/*
Get sex
enum playersex_t {
	PLAYERSEX_FEMALE = 0,
	PLAYERSEX_MALE = 1,
	PLAYERSEX_OLDMALE = 2,
	PLAYERSEX_DWARF = 3,
	PLAYERSEX_NIMFA = 4, 
};
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
Get experience for any level
specialDivider works when custom formula is used to calculate experience
*/

public function getExpForLevel($level, $specialDivider = 1) {

	$this->expLevel = $this->expLevel = ((((50*$level/3 - 100)*$level + 850/3)*$level - 200)/$specialDivider);

	return intval($this->expLevel);
	
	}

/*
Get experience for player next level
*/

public function getExpForNextLevel($specialDivider = 1) {

	$currentExp = $this->getExp();
	$nextLevel = $this->getLevel() +  1;

		//get exp for next level
	$this->expNextLevel = ((((50*$nextLevel/3 - 100)*$nextLevel + 850/3)*$nextLevel - 200)/$specialDivider) - $currentExp;

	return intval($this->expNextLevel);
	
	}



/*
Get percentage value for next level as float
*/

public function getExpPercentNextLevel($specialDivider = 1) {

	$currentLevelExp = $this->getExpForLevel($this->getLevel(), $specialDivider);
	$nextLevelExp = $this->getExpForLevel($this->getLevel()+1, $specialDivider);
	$expForNextLvl = $this->getExpForNextLevel($specialDivider);

	$this->expPercNextLevel = round(($expForNextLvl/($nextLevelExp - $currentLevelExp)*100), 1);
		
	return floatval(abs($this->expPercNextLevel)); //return percent
	
	}



/*
Get vocation
enum playervoc_t {
	VOCATION_NONE = 0,
	VOCATION_SORCERER = 1,
	VOCATION_DRUID = 2,
	VOCATION_PALADIN = 3,
	VOCATION_KNIGHT = 4
};
*/
public function getVocation() {

return intval($this->xmlPlayer['voc']);

}

/*
Get vocation name and check promotion
*/
public function getVocationName() {

	$vocation = $this->getVocation();
	$promotion = $this->getPromotion();


	switch ([$vocation, $promotion]) {
		case [0, 0]:
			$this->vocationName = 'No vocation';
		break;
	
		case [1, 0]:
			$this->vocationName = 'Sorcerer';
		break;

		case [1, 1]:
			$this->vocationName = 'Master Sorcerer';
		break;

		case [2, 0]:
			$this->vocationName = 'Druid';
		break;

		case [2, 1]:
			$this->vocationName = 'Elder Druid';
		break;

		case [3, 0]:
			$this->vocationName = 'Paladin';
		break;

		case [3, 1]:
			$this->vocationName = 'Royal Paladin';
		break;

		case [4, 0]:
			$this->vocationName = 'Knight';
		break;

		case [4, 1]:
			$this->vocationName = 'Elite Knight';
		break;
		
	}

	return $this->vocationName;

}


/*
Get level
*/
public function getLevel() {

return intval($this->xmlPlayer['level']);

}


/*
Get skill levels
*/
public function getSkills() {


	$this->skills['fist'] = intval($this->xmlPlayer->skills->skill[0]['level']);
	$this->skills['club'] = intval($this->xmlPlayer->skills->skill[1]['level']);
	$this->skills['sword'] = intval($this->xmlPlayer->skills->skill[2]['level']);
	$this->skills['axe'] = intval($this->xmlPlayer->skills->skill[3]['level']);
	$this->skills['distance'] = intval($this->xmlPlayer->skills->skill[4]['level']);
	$this->skills['shield'] = intval($this->xmlPlayer->skills->skill[5]['level']);


	return $this->skills; //array
	
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
		return intval($time);

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
    
$this->ban['status'] = intval($this->xmlPlayer->ban['banned']); //0;1
$this->ban['start'] = intval($this->xmlPlayer->ban['banstart']); //timestamp
$this->ban['end'] = intval($this->xmlPlayer->ban['banend']); //timestamp
$this->ban['comment'] = strval($this->xmlPlayer->ban['comment']); 
$this->ban['action'] = strval($this->xmlPlayer->ban['action']); 
$this->ban['reason'] = strval($this->xmlPlayer->ban['reason']); 
$this->ban['banrealtime'] = strval($this->xmlPlayer->ban['banrealtime']); 
$this->ban['deleted'] = intval($this->xmlPlayer->ban['deleted']); //0;1
$this->ban['finalwarning'] = intval($this->xmlPlayer->ban['finalwarning']); //0;1

return $this->ban;
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
	case 3:
		return $this->skull = 'WHITE_SKULL';
	case 4:
		return $this->skull = 'RED_SKULL';
	default:
		return $this->skull = 'NO_SKULL';
					}

}

/*
Get frags as an array
*/
public function getFrags() {

$this->frags['kills'] = intval($this->xmlPlayer->skull['kills']); //int
$this->frags['ticks'] = intval($this->xmlPlayer->skull['ticks']);
$this->frags['absolve'] = intval($this->xmlPlayer->skull['absolve']);

return $this->frags; //array

}


/*
Get health
now
max
*/
public function getHealth() {

$this->health['now'] = intval($this->xmlPlayer->health['now']);
$this->health['max'] = intval($this->xmlPlayer->health['max']);

return $this->health; //array

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
Get required mana level
cpp source -> unsigned int Player::getReqMana(int maglevel, playervoc_t voc)
not tested yet :)
*/
public function getRequiredMana($mlevel = NULL) {

//use mana spent and formula
$vocationMultiplayer = array(1, 1.1, 1.1, 1.4, 3);

if(!isset($mlevel))
	$mlevel = $this->getMagicLevel();

$this->reqMana = intval(( 400 * pow($vocationMultiplayer[$this->getVocation()], $mlevel -1)));

if ($this->reqMana % 20 < 10) //CIP must have been bored when they invented this odd rounding
    $this->reqMana = $this->reqMana - ($this->reqMana % 20);
  else
    $this->reqMana = $this->reqMana - ($this->reqMana % 20) + 20;

return intval($this->reqMana);

}

/*
Get percentage magic level
cpp source -> void Player::sendStats()
*/
public function getMagicLevelPercent() {

$this->getMana();
$this->magicLevelPercent = intval(100*($this->mana['spent']/(1.* $this->getRequiredMana($this->getMagicLevel() + 1) )));

return intval($this->magicLevelPercent);

}


/*
Get houses players own or is invited
this method doesnt need to use prepare for the player file
not tested yet
*/
public function getHouses($playerName) {

	$houseFound = array(); //start array where player is stored

	$houses = glob($this->housesPath.'*.xml');


		foreach($houses as $house){
				//opens a file
				$open = htmlentities(file_get_contents($house));
				//check if player is found
				$found = strpos($open, $playerName);

				if($found > 0) {
					//add housename to array
					//we can use later to display houises name player owns
					$houseFound[] .= $house; 
				}
				else {

					return $this->house['count'] = 0; //player doesnt have any houses
				}

			}
				foreach($houseFound as $playerHouse){
					//lets open and check what the node name is
					$xml = simplexml_load_file($playerHouse);
					$this->house['name'] = basename($playerHouse, '.xml').PHP_EOL;
					//var_dump($xml);
			}

			//return array of informations like count houses, houses names and houses ownership
			//todo - several houses or several guests and subowners check
			 $this->house['count'] = count($houseFound);
			 $this->house['housename'] = basename($playerHouse, '.xml').PHP_EOL;
			 $this->house['owner'] = $xml->owner['name'];
			 $this->house['subowner'] = $xml->subowner['name'];
			 $this->house['guest'] = $xml->guest['name'];


}



public function getStorageValues() {

foreach ($this->xmlPlayer->storage->data as $item) {
	
	$key = strval($item['key']);
	$value = strval($item['value']);
	$this->storage[$key] = $value;
}

return $this->storage; //array

}


public function getDeaths() {
    
    
    foreach ($this->xmlPlayer->deaths->death as $id) {
            $this->dead[] = $id;
        }

       return $this->dead; //array of objects

}





/*
Set functions
*/


/*
Set new password
*/

public function setPassword($password) {
    
        $this->xmlAccount['pass'] = $password;
        $makeChange = $this->xmlAccount->asXML($this->xmlAccountFilePath);
        
        if($makeChange) {
            
            return TRUE;
        }
            else {
                return FALSE;
            }
            
}


/*
Set new password
*/

public function setPremDays($count) {
    
	$this->xmlAccount['premDays'] = $count;
	$makeChange = $this->xmlAccount->asXML($this->xmlAccountFilePath);
	
	if($makeChange) {
		
		return TRUE;
	}
		else {
			return FALSE;
		}
		
}


/*
Set sex
*/

public function setSex($number) {

	if($number >= 0 AND $number < 5) {

		$this->xmlPlayer['sex'] = $number;
		$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

	}
	else {
			$this->throwError('Error: Range of arguments allowed: 0-4', 1);
	}
    
	if($makeChange) {
		
		return TRUE;
	}
		else {
			return FALSE;
		}
		
}


/*
Remove character from account and delete player file
set second argument to TRUE if you want to remove account file altogether
*/

public function removeCharacter($charName, $accountRemove = NULL) {
    
	foreach($this->xmlAccount->characters->character as $seg) {
        
		if($seg['name'] == $charName) {
		    //remove child attribute from account file
			$dom = dom_import_simplexml($seg);
			$dom->parentNode->removeChild($dom);
			$makeChange = $this->xmlAccount->asXML($this->xmlAccountFilePath);
			//remove player file
			$makeRemove = unlink($this->xmlPlayerFilePath);
				if($accountRemove == TRUE) {
					$makeRemoveAcc = unlink($this->xmlAccountFilePath);
				}
        	}
			else {
				$this->throwError('Error: Player doesn`t exists.', 1);
		}
    }
    if(isset($makeChange) AND isset($makeRemove) AND isset($makeRemoveAcc)) {
        
        return TRUE;
    }
    else {
        return FALSE;
    }
    
}

/*
Ban player
Args:
duration: set in houres
reason: will be displayed on site
*/


public function setBan($duration, $reason, $comment, $finalwarning, $deleted, $extend = NULL) {

	$this->getBanStatus();
	if($this->ban['status'] == 1 AND $extend == NULL) {

		$this->throwError('Error: Player is already banned.', 1);
	}
		else {
				//check if player has already finalwarning if so, put deleted
			if($this->ban['finalwarning'] == 1) {

				$deleted = 1;
			}

			$durationHoures = $duration*3600;

			$this->xmlPlayer->ban['banned'] = 1; //0;1
			$this->xmlPlayer->ban['banstart'] = time(); //timestamp
			$this->xmlPlayer->ban['banend'] = time() + $durationHoures; //timestamp
			$this->xmlPlayer->ban['banrealtime'] = date('Y-m-d H:i:s', $this->ban['end']);
			$this->xmlPlayer->ban['comment'] = $comment;
			$this->xmlPlayer->ban['action'] = 'Account ban - XML class';
			$this->xmlPlayer->ban['reason'] = $reason;
			$this->xmlPlayer->ban['deleted'] = $deleted; //0;1
			$this->xmlPlayer->ban['finalwarning'] = $finalwarning; //0;1

			$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

		}
    
		if($makeChange) {
		
			return TRUE;
		}
			else {
				return FALSE;
			}
	
		}


/*
Unban player
Optional args:
removeFW - removing final warning
removeDel - removing perm ban
*/

public function removeBan($removeFW = NULL, $removeDel = NULL) {

	$this->getBanStatus();
	if($this->ban['status'] == 0) {

		$this->throwError('Error: Player is not banned. Dont need any action', 1);
	}
		else {


			$this->xmlPlayer->ban['banned'] = 0; //0;1
			$this->xmlPlayer->ban['banstart'] = 0; //timestamp
			$this->xmlPlayer->ban['banend'] = 0; //timestamp
			//we do not clear banrealtime to get information when last ban happened
			//$this->xmlPlayer->ban['banrealtime'] = '';
			$this->xmlPlayer->ban['comment'] = '';
			$this->xmlPlayer->ban['action'] = '';
			$this->xmlPlayer->ban['reason'] = '';

			if($removeFW == 1) {
				$this->xmlPlayer->ban['finalwarning'] = 0; //0;1
			}
			if($removeDel == 1 ) {
				$this->xmlPlayer->ban['deleted'] = 0; //0;1
			}
			$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

		}
    
		if($makeChange) {
		
			return TRUE;
		}
			else {
				return FALSE;
			}
	
		}




/*
Set access
*/

public function setAccess($number) {

		$this->xmlPlayer['accesss'] = intval($number);
		$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

	if($makeChange) {
		
		return TRUE;
	}
		else {
			return FALSE;
		}
		
}



/*
Set promotion
*/
public function setPromotion($number) {

	$this->xmlPlayer['promoted'] = intval($number);
		$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

	if($makeChange) {
		
		return TRUE;
	}
		else {
			return FALSE;
		}
	
	}


/*
Set capacity
*/
public function setCapacity($number) {

	$this->xmlPlayer['cap'] = intval($number);
		$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

	if($makeChange) {
		
		return TRUE;
	}
		else {
			return FALSE;
		}
	
	}


	
/*
Change player name
TODO: change name in guilds and houses
*/
public function setName($name) {

		//changing player file
	$currentName = $this->xmlPlayer['name'];

	$this->xmlPlayer['name'] = strval($name);
		$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

	$rename = rename($this->xmlPlayerFilePath, $this->playersDir.$name);
	 //changing account file

	 foreach($this->xmlAccount->characters->character as $seg) {
        
		if($seg['name'] == $currentName) {

			$seg['name'] = trim($name);
			$makeChangeAcc = $this->xmlAccount->asXML($this->xmlAccountFilePath);
		}

	}

	if($makeChange AND $makeChangeAcc) {
		
		return TRUE;
	}
		else {
			return FALSE;
		}
	
	}


	

//end class
}