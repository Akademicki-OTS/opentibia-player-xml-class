<?php
/*
Open Tibia XML player class
Version: 1.2.2
Author: Pawel 'Pavlus' Janisio
License: MIT
Github: https://github.com/PJanisio/opentibia-player-xml-class
*/

class xmlPlayer
{
	//predefined variables
	//private
	private $dataPath = '';
	private $realPath = '';
	private $housesPath = '';
	private $mapPath = '';
	private $monsterPath = '';
	private $guildPath = '';
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
	public $outfitUrl = '';
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
	public $age = 0;
	//arrays
	public $skills = array();
	public $look = array();
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
	public $kills = array();
	public $boost = array();
	public $playerGuilds = array();
	public $slotsData = array();

	/*
		  Checks paths and define directories
		  */
	public function __construct($dataPath)
	{
		$this->dataPath = $dataPath;
		$this->realPath = realpath($this->dataPath);

		//check if this is real path and directory
		if ($this->realPath == false or !is_dir($this->realPath)) {
			$this->throwError('Data path invalid!', 1);
		}

		//check if there exists players and accounts directory	
		if (!is_dir($this->realPath . '/players') or !is_dir($this->realPath . '/accounts')) {
			$this->throwError('Players/Accounts path is invalid!', 1);
		} else {
			$this->playersDir = $this->realPath . '/players/';
			$this->accountsDir = $this->realPath . '/accounts/';
			$this->housesPath = $this->realPath . '/houses/';
			$this->mapPath = $this->realPath . '/world/';
			$this->monsterPath = $this->realPath . '/monster/';
			$this->guildPath = $this->realPath . '/guilds.xml';
		}
	}

	/*
		  Throwing error function
		  */
	public function throwError($errorTxt, $showError = 1)
	{
		$this->errorTxt = $errorTxt;
		if ($showError == 1) {
			throw new Exception($this->errorTxt);
		}
	}

	/*
		  Check if it's a player (if false then - monster)
		  */
	public function isPlayer($playerName)
	{
		// Construct the full path to the player file
		$filePath = $this->playersDir . $playerName . '.xml';
		// Check if the file exists
		return file_exists($filePath);
	}

	/*
	 * Loads an external player XML file from a given directory.
	 * This method only loads the player file (player.xml) and skips account/VIP data.
	 */
	public function prepareExternalPlayer($playerName, $externalDir)
	{
		// Ensure the directory path ends with a slash
		$externalDir = rtrim($externalDir, '/') . '/';
		// Build the file path for the player XML
		$filePath = $externalDir . $playerName . '.xml';

		if (!file_exists($filePath)) {
			$this->throwError("External player file not found: " . $filePath, 1);
		}

		// Load the player XML file
		$this->xmlPlayerFilePath = $filePath;
		$this->xmlPlayer = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_PARSEHUGE);
		if ($this->xmlPlayer === FALSE) {
			$this->throwError("Error loading external player file!", 1);
		}

		// Return TRUE to indicate success
		return TRUE;
	}

	/*
		  Opens xml stream for player and account file
		  */
	public function prepare($playerName)
	{
		//function to open xml stream
		$playerName = trim(stripslashes($playerName));
		$this->xmlPlayerFilePath = $this->playersDir . $playerName . '.xml';

		$this->xmlPlayer = simplexml_load_file($this->xmlPlayerFilePath, 'SimpleXMLElement', LIBXML_PARSEHUGE);

		if ($this->xmlPlayer === FALSE) { //returns not boolean false what the heck
			$this->throwError('Player does not exist!', 1);
		} else {
			$this->xmlAccountFilePath = $this->accountsDir . $this->getAccount() . '.xml';
			$this->xmlAccount = simplexml_load_file($this->xmlAccountFilePath, 'SimpleXMLElement', LIBXML_PARSEHUGE);

			if ($this->xmlAccount === FALSE)
				$this->throwError('Account file for player does not exist!', 1);
		}
		if ($this->xmlAccount && $this->xmlPlayer)
			return TRUE;
	}

	/*
	  ===========================================================	
	  Get functions
	  ===========================================================	
	  */

	/*
		  Show xml structure for player file
		  */
	public function showStructurePlayer()
	{
		echo '<pre>', var_dump($this->xmlPlayer), '</pre>';
	}

	/*
		  Show xml structure for account file
		  */
	public function showStructureAccount()
	{
		echo '<pre>', var_dump($this->xmlAccount), '</pre>';
	}

	/*
		  Show last modified player files (by save or by class action)
		  */
	public function showLastModifiedPlayers($minutes, $dateFormat = NULL)
	{
		if (!isset($dateFormat))
			$dateFormat = 'Y-m-d H:i:s';

		$files = scandir($this->playersDir);
		foreach ($files as $file) {
			$stat = stat($this->playersDir . $file);
			$lastmod = $stat['mtime'];
			$now = time();

			if ($now - $lastmod < $minutes * 60)
				$this->lastModified[$file] = date($dateFormat, $lastmod);
		}

		return $this->lastModified;
	}

	/*
		  Get account number/name
		  */
	public function getAccount()
	{
		return isset($this->xmlPlayer['account']) ? strval($this->xmlPlayer['account']) : '';
	}

	/*
		  Get premium days
		  */
	public function getPremDays()
	{
		return isset($this->xmlAccount['premDays']) ? intval($this->xmlAccount['premDays']) : 0;
	}

	/*
		  Get other characters on the same account
		  */
	public function getCharacters()
	{
		if (isset($this->xmlAccount->characters) && isset($this->xmlAccount->characters->character)) {
			foreach ($this->xmlAccount->characters->character as $char) {
				$this->characters[] = strval($char['name']);
			}
		}
		return $this->characters;
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
	public function getSex()
	{
		return isset($this->xmlPlayer['sex']) ? intval($this->xmlPlayer['sex']) : 0;
	}

	/*
		  Get looktype and look direction
		  */
	public function getLookType()
	{
		if (isset($this->xmlPlayer['lookdir']))
			$this->look['lookdir'] = intval($this->xmlPlayer['lookdir']);
		if (isset($this->xmlPlayer->look)) {
			$this->look['type'] = isset($this->xmlPlayer->look['type']) ? intval($this->xmlPlayer->look['type']) : 0;
			$this->look['head'] = isset($this->xmlPlayer->look['head']) ? intval($this->xmlPlayer->look['head']) : 0;
			$this->look['body'] = isset($this->xmlPlayer->look['body']) ? intval($this->xmlPlayer->look['body']) : 0;
			$this->look['legs'] = isset($this->xmlPlayer->look['legs']) ? intval($this->xmlPlayer->look['legs']) : 0;
			$this->look['feet'] = isset($this->xmlPlayer->look['feet']) ? intval($this->xmlPlayer->look['feet']) : 0;
		}
		return $this->look;
	}

	/*
		  Get experience points
		  */
	public function getExp()
	{
		return isset($this->xmlPlayer['exp']) ? intval($this->xmlPlayer['exp']) : 0;
	}

	/*
		  Get experience for any level
		  specialDivider works when custom formula is used to calculate experience
		  */
	public function getExpForLevel($level, $specialDivider = 1)
	{
		$this->expLevel = ((((50 * $level / 3 - 100) * $level + 850 / 3) * $level - 200) / $specialDivider);
		return intval($this->expLevel);
	}

	/*
		  Get experience for player next level
		  */
	public function getExpForNextLevel($specialDivider = 1)
	{
		$currentExp = $this->getExp();
		$nextLevel = $this->getLevel() + 1;
		$this->expNextLevel = ((((50 * $nextLevel / 3 - 100) * $nextLevel + 850 / 3) * $nextLevel - 200) / $specialDivider) - $currentExp;
		return intval($this->expNextLevel);
	}

	/*
		  Get percentage value for next level as float
		  */
	public function getExpPercentNextLevel($specialDivider = 1)
	{
		$currentLevelExp = $this->getExpForLevel($this->getLevel(), $specialDivider);
		$nextLevelExp = $this->getExpForLevel($this->getLevel() + 1, $specialDivider);
		$expForNextLvl = $this->getExpForNextLevel($specialDivider);
		$this->expPercNextLevel = round(($expForNextLvl / ($nextLevelExp - $currentLevelExp) * 100), 1);
		return floatval(abs($this->expPercNextLevel));
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
	public function getVocation()
	{
		return isset($this->xmlPlayer['voc']) ? intval($this->xmlPlayer['voc']) : 0;
	}

	/*
		  Get vocation name and check promotion
		  */
	public function getVocationName()
	{
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
	public function getLevel()
	{
		return isset($this->xmlPlayer['level']) ? intval($this->xmlPlayer['level']) : 0;
	}

	/*
		  Get skill tries
		  */
	public function getReqSkillTries($skill, $level, $voc)
	{
		// Skill bases for each skill type
		$skillBases = [50, 50, 50, 50, 30, 100, 20];
		// Skill multipliers for each skill type and vocation
		$skillMultipliers = [
			[1.5, 1.5, 1.5, 1.2, 1.1], // Fist
			[2.0, 2.0, 1.8, 1.2, 1.1], // Club
			[2.0, 2.0, 1.8, 1.2, 1.1], // Sword
			[2.0, 2.0, 1.8, 1.2, 1.1], // Axe
			[2.0, 2.0, 1.8, 1.1, 1.4], // Distance
			[1.5, 1.5, 1.5, 1.1, 1.1], // Shielding
			[1.1, 1.1, 1.1, 1.1, 1.1]  // Fishing
		];
		$reqSkillTries = $skillBases[$skill] * pow($skillMultipliers[$skill][$voc], $level - 11);
		return intval($reqSkillTries);
	}

	/*
		  Get skill percent for next level
		  */
	public function getSkillPercentForNextLevel($skillId)
	{
		if (!isset($this->xmlPlayer->skills->skill[$skillId])) {
			return 0;
		}
		$currentSkill = $this->xmlPlayer->skills->skill[$skillId];
		$currentLevel = isset($currentSkill['level']) ? intval($currentSkill['level']) : 0;
		$currentTries = isset($currentSkill['tries']) ? intval($currentSkill['tries']) : 0;
		$voc = $this->getVocation();
		$reqTriesNextLevel = $this->getReqSkillTries($skillId, $currentLevel + 1, $voc);
		if ($reqTriesNextLevel == 0) {
			return 100;
		}
		$progress = ($currentTries / $reqTriesNextLevel) * 100;
		$roundedProgress = round(max(0, min(100, $progress)));
		return intval($roundedProgress);
	}

	/*
		  Get skill levels and skill percentage to next level
		  */
	public function getSkills()
	{
		if (isset($this->xmlPlayer->skills)) {
			$skills = $this->xmlPlayer->skills->skill;
			$this->skills['fist'] = isset($skills[0]['level']) ? intval($skills[0]['level']) : 0;
			$this->skills['club'] = isset($skills[1]['level']) ? intval($skills[1]['level']) : 0;
			$this->skills['sword'] = isset($skills[2]['level']) ? intval($skills[2]['level']) : 0;
			$this->skills['axe'] = isset($skills[3]['level']) ? intval($skills[3]['level']) : 0;
			$this->skills['distance'] = isset($skills[4]['level']) ? intval($skills[4]['level']) : 0;
			$this->skills['shield'] = isset($skills[5]['level']) ? intval($skills[5]['level']) : 0;

			$this->skills['fist_percent'] = $this->getSkillPercentForNextLevel(0);
			$this->skills['club_percent'] = $this->getSkillPercentForNextLevel(1);
			$this->skills['sword_percent'] = $this->getSkillPercentForNextLevel(2);
			$this->skills['axe_percent'] = $this->getSkillPercentForNextLevel(3);
			$this->skills['distance_percent'] = $this->getSkillPercentForNextLevel(4);
			$this->skills['shield_percent'] = $this->getSkillPercentForNextLevel(5);
		}
		return $this->skills;
	}

	/*
		  Get access
		  */
	public function getAccess()
	{
		return isset($this->xmlPlayer['access']) ? intval($this->xmlPlayer['access']) : 0;
	}

	/*
		  Get capacity
		  */
	public function getCapacity()
	{
		return isset($this->xmlPlayer['cap']) ? intval($this->xmlPlayer['cap']) : 0;
	}

	/*
		  Get bless level (not standard)
		  */
	public function getBless()
	{
		return isset($this->xmlPlayer['bless']) ? intval($this->xmlPlayer['bless']) : 0;
	}

	/*
		  Get magiclevel
		  */
	public function getMagicLevel()
	{
		return isset($this->xmlPlayer['maglevel']) ? intval($this->xmlPlayer['maglevel']) : 0;
	}

	/*
		  Get lastlogin
		  */
	public function getLastLogin($format = NULL)
	{
		$time = isset($this->xmlPlayer['lastlogin']) ? intval($this->xmlPlayer['lastlogin']) : 0;
		if ($format != NULL)
			return date($format, $time);
		else
			return $time;
	}

	/*
		  Get promoted status
		  */
	public function getPromotion()
	{
		return isset($this->xmlPlayer['promoted']) ? intval($this->xmlPlayer['promoted']) : 0;
	}

	/*
		  Get ban status
		  */
	public function getBanStatus()
	{
		if (isset($this->xmlPlayer->ban)) {
			$this->ban['status'] = isset($this->xmlPlayer->ban['banned']) ? intval($this->xmlPlayer->ban['banned']) : 0;
			$this->ban['start'] = isset($this->xmlPlayer->ban['banstart']) ? intval($this->xmlPlayer->ban['banstart']) : 0;
			$this->ban['end'] = isset($this->xmlPlayer->ban['banend']) ? intval($this->xmlPlayer->ban['banend']) : 0;
			$this->ban['comment'] = isset($this->xmlPlayer->ban['comment']) ? strval($this->xmlPlayer->ban['comment']) : '';
			$this->ban['action'] = isset($this->xmlPlayer->ban['action']) ? strval($this->xmlPlayer->ban['action']) : '';
			$this->ban['reason'] = isset($this->xmlPlayer->ban['reason']) ? strval($this->xmlPlayer->ban['reason']) : '';
			$this->ban['banrealtime'] = isset($this->xmlPlayer->ban['banrealtime']) ? strval($this->xmlPlayer->ban['banrealtime']) : '';
			$this->ban['deleted'] = isset($this->xmlPlayer->ban['deleted']) ? intval($this->xmlPlayer->ban['deleted']) : 0;
			$this->ban['finalwarning'] = isset($this->xmlPlayer->ban['finalwarning']) ? intval($this->xmlPlayer->ban['finalwarning']) : 0;
		}
		return $this->ban;
	}

	/*
		  Get spawn position as an array
		  */
	public function getSpawnCoordinates()
	{
		if (isset($this->xmlPlayer->spawn)) {
			$this->spawn['x'] = isset($this->xmlPlayer->spawn['x']) ? intval($this->xmlPlayer->spawn['x']) : 0;
			$this->spawn['y'] = isset($this->xmlPlayer->spawn['y']) ? intval($this->xmlPlayer->spawn['y']) : 0;
			$this->spawn['z'] = isset($this->xmlPlayer->spawn['z']) ? intval($this->xmlPlayer->spawn['z']) : 0;
		}
		return $this->spawn;
	}

	/*
		  Get temple position as an array
		  */
	public function getTempleCoordinates()
	{
		if (isset($this->xmlPlayer->temple)) {
			$this->temple['x'] = isset($this->xmlPlayer->temple['x']) ? intval($this->xmlPlayer->temple['x']) : 0;
			$this->temple['y'] = isset($this->xmlPlayer->temple['y']) ? intval($this->xmlPlayer->temple['y']) : 0;
			$this->temple['z'] = isset($this->xmlPlayer->temple['z']) ? intval($this->xmlPlayer->temple['z']) : 0;
		}
		return $this->temple;
	}

	/*
		  Get skull type
		  SKULL_NONE = 0,
		  SKULL_YELLOW = 1,
		  SKULL_WHITE = 3,
		  SKULL_RED = 4
		  */
	public function getSkull()
	{
		if (isset($this->xmlPlayer->skull) && isset($this->xmlPlayer->skull['type'])) {
			$this->skull = intval($this->xmlPlayer->skull['type']);
		} else {
			$this->skull = 0;
		}
		switch ($this->skull) {
			case 1:
				return 'YELLOW_SKULL';
			case 3:
				return 'WHITE_SKULL';
			case 4:
				return 'RED_SKULL';
			default:
				return 'NO_SKULL';
		}
	}

	/*
		  Get frags as an array
		  */
	public function getFrags()
	{
		if (isset($this->xmlPlayer->skull)) {
			$this->frags['kills'] = isset($this->xmlPlayer->skull['kills']) ? intval($this->xmlPlayer->skull['kills']) : 0;
			$this->frags['ticks'] = isset($this->xmlPlayer->skull['ticks']) ? intval($this->xmlPlayer->skull['ticks']) : 0;
			$this->frags['absolve'] = isset($this->xmlPlayer->skull['absolve']) ? intval($this->xmlPlayer->skull['absolve']) : 0;
		}
		return $this->frags;
	}

	/*
		  Get health (now and max)
		  */
	public function getHealth()
	{
		if (isset($this->xmlPlayer->health)) {
			$this->health['now'] = isset($this->xmlPlayer->health['now']) ? intval($this->xmlPlayer->health['now']) : 0;
			$this->health['max'] = isset($this->xmlPlayer->health['max']) ? intval($this->xmlPlayer->health['max']) : 0;
		}
		return $this->health;
	}

	/*
		  Get food level
		  */
	public function getFoodLevel()
	{
		$this->food = isset($this->xmlPlayer->health['food']) ? intval($this->xmlPlayer->health['food']) : 0;
		return $this->food;
	}

	/*
		  Get mana information
		  */
	public function getMana()
	{
		if (isset($this->xmlPlayer->mana)) {
			$this->mana['now'] = isset($this->xmlPlayer->mana['now']) ? intval($this->xmlPlayer->mana['now']) : 0;
			$this->mana['max'] = isset($this->xmlPlayer->mana['max']) ? intval($this->xmlPlayer->mana['max']) : 0;
			$this->mana['spent'] = isset($this->xmlPlayer->mana['spent']) ? intval($this->xmlPlayer->mana['spent']) : 0;
		}
		return $this->mana;
	}

	/*
		  Get required mana level
		  */
	public function getRequiredMana($mlevel = NULL)
	{
		$vocationMultiplayer = array(1, 1.1, 1.1, 1.4, 3);
		if (!isset($mlevel))
			$mlevel = $this->getMagicLevel();
		$this->reqMana = intval((400 * pow($vocationMultiplayer[$this->getVocation()], $mlevel - 1)));
		if ($this->reqMana % 20 < 10)
			$this->reqMana = $this->reqMana - ($this->reqMana % 20);
		else
			$this->reqMana = $this->reqMana - ($this->reqMana % 20) + 20;
		return intval($this->reqMana);
	}

	/*
		  Get percentage magic level
		  */
	public function getMagicLevelPercent()
	{
		$this->getMana();
		$this->magicLevelPercent = intval(100 * ($this->mana['spent'] / (1.0 * $this->getRequiredMana($this->getMagicLevel() + 1))));
		return intval($this->magicLevelPercent);
	}

	/*
		  Get houses players own or are invited to
		  */
	public function getHouses($playerName)
	{
		$houseFound = array();
		$houses = glob($this->housesPath . '*.xml');
		$playerName = trim(strtolower($playerName));

		foreach ($houses as $house) {
			$xml = simplexml_load_file($house);
			if (isset($xml->owner)) {
				foreach ($xml->owner as $owner) {
					if (strtolower($owner['name']) == $playerName) {
						$houseFound[] = $house;
					}
				}
			}
			if (isset($xml->subowner)) {
				foreach ($xml->subowner as $subowner) {
					if (strtolower($subowner['name']) == $playerName) {
						$houseFound[] = $house;
					}
				}
			}
			if (isset($xml->doorowner)) {
				foreach ($xml->doorowner as $doorowner) {
					if (strtolower($doorowner['name']) == $playerName) {
						$houseFound[] = $house;
					}
				}
			}
			if (isset($xml->guest)) {
				foreach ($xml->guest as $guest) {
					if (strtolower($guest['name']) == $playerName) {
						$houseFound[] = $house;
					}
				}
			}
		}

		$this->house['count'] = count($houseFound);
		$this->house['owner'] = '';
		$this->house['subowner'] = '';
		$this->house['doorowner'] = '';
		$this->house['guest'] = '';

		foreach ($houseFound as $playerHouse) {
			$xml = simplexml_load_file($playerHouse);
			if (isset($xml->owner)) {
				foreach ($xml->owner as $owner) {
					if (strtolower($owner['name']) == $playerName) {
						$this->house['owner'] .= basename($playerHouse, '.xml') . ', ';
					}
				}
			}
			if (isset($xml->subowner)) {
				foreach ($xml->subowner as $subowner) {
					if (strtolower($subowner['name']) == $playerName) {
						$this->house['subowner'] .= basename($playerHouse, '.xml') . ', ';
					}
				}
			}
			if (isset($xml->doorowner)) {
				foreach ($xml->doorowner as $doorowner) {
					if (strtolower($doorowner['name']) == $playerName) {
						$this->house['doorowner'] .= basename($playerHouse, '.xml') . ', ';
					}
				}
			}
			if (isset($xml->guest)) {
				foreach ($xml->guest as $guest) {
					if (strtolower($guest['name']) == $playerName) {
						$this->house['guest'] .= basename($playerHouse, '.xml') . ', ';
					}
				}
			}
		}
		return $this->house;
	}

	/*
		  Get storage values
		  */
	public function getStorageValues()
	{
		if (isset($this->xmlPlayer->storage) && isset($this->xmlPlayer->storage->data)) {
			foreach ($this->xmlPlayer->storage->data as $item) {
				$key = isset($item['key']) ? strval($item['key']) : '';
				$value = isset($item['value']) ? strval($item['value']) : '';
				if ($key !== '') {
					$this->storage[$key] = $value;
				}
			}
		}
		return $this->storage;
	}

	/*
		  Get deaths
		  */
	public function getDeaths()
	{
		if (isset($this->xmlPlayer->deaths) && isset($this->xmlPlayer->deaths->death)) {
			foreach ($this->xmlPlayer->deaths->death as $id) {
				$this->dead[] = $id;
			}
		}
		return $this->dead;
	}

	/*
		  Get player age (in seconds)
		  */
	public function getAge()
	{
		$this->age = isset($this->xmlPlayer['age']) ? intval($this->xmlPlayer['age']) : 0;
		return $this->age;
	}

	/*
		  Get kills statistics from the <skull> node
		  */
	public function getKills()
	{
		$this->kills = [];
		if (isset($this->xmlPlayer->skull)) {
			$this->kills['totalKills'] = isset($this->xmlPlayer->skull['totalKills']) ? intval($this->xmlPlayer->skull['totalKills']) : 0;
			$this->kills['totalDeaths'] = isset($this->xmlPlayer->skull['totalDeaths']) ? intval($this->xmlPlayer->skull['totalDeaths']) : 0;
			$this->kills['nsKills'] = isset($this->xmlPlayer->skull['nsKills']) ? intval($this->xmlPlayer->skull['nsKills']) : 0;
			$this->kills['wsKills'] = isset($this->xmlPlayer->skull['wsKills']) ? intval($this->xmlPlayer->skull['wsKills']) : 0;
			$this->kills['ysKills'] = isset($this->xmlPlayer->skull['ysKills']) ? intval($this->xmlPlayer->skull['ysKills']) : 0;
			$this->kills['rsKills'] = isset($this->xmlPlayer->skull['rsKills']) ? intval($this->xmlPlayer->skull['rsKills']) : 0;
		}
		return $this->kills;
	}

	/*
		  Get boost status
		  */
	public function getBoostStatus()
	{
		$this->boost = [];
		if (isset($this->xmlPlayer->boost)) {
			$this->boost['status'] = isset($this->xmlPlayer->boost['status']) ? intval($this->xmlPlayer->boost['status']) : 0;
			$this->boost['ticks'] = isset($this->xmlPlayer->boost['ticks']) ? intval($this->xmlPlayer->boost['ticks']) : 0;
			$this->boost['task'] = isset($this->xmlPlayer->boost['task']) ? intval($this->xmlPlayer->boost['task']) : 0;
			$this->boost['timestamp'] = isset($this->xmlPlayer->boost['timestamp']) ? intval($this->xmlPlayer->boost['timestamp']) : 0;
			$this->boost['reroll'] = isset($this->xmlPlayer->boost['reroll']) ? intval($this->xmlPlayer->boost['reroll']) : 0;
		}
		return $this->boost;
	}

	/*
		  Create an outfit url (ots.me)
		  */
	public function showOutfit()
	{
		$look = $this->getLookType();
		$this->outfitUrl = 'https://outfit-images.ots.me/772/animoutfit.php?id=' . $look['type'] .
			'&addons=1&head=' . $look['head'] .
			'&body=' . $look['body'] .
			'&legs=' . $look['legs'] .
			'&feet=' . $look['feet'] .
			'&mount=0&direction=3';
		return $this->outfitUrl;
	}

	/*
		  Get guild name and member status
		  */
	public function getGuild()
	{
		if (!file_exists($this->guildPath)) {
			$this->throwError('Guilds file not found!', 1);
			return array();
		}
		$guildsXml = simplexml_load_file($this->guildPath, 'SimpleXMLElement', LIBXML_PARSEHUGE);
		if ($guildsXml === false) {
			$this->throwError('Could not parse guilds.xml!', 1);
			return array();
		}
		$playerName = isset($this->xmlPlayer['name']) ? strval($this->xmlPlayer['name']) : '';
		$playerGuilds = array();
		foreach ($guildsXml->guild as $guildNode) {
			foreach ($guildNode->member as $member) {
				if (strval($member['name']) === $playerName) {
					$statusInt = intval($member['status']);
					$statusName = 'GUILD_NONE';
					switch ($statusInt) {
						case 0:
							$statusName = 'GUILD_INVITED';
							break;
						case 1:
							$statusName = 'GUILD_MEMBER';
							break;
						case 2:
							$statusName = 'GUILD_VICE';
							break;
						case 4:
							$statusName = 'GUILD_LEADER';
							break;
					}
					$guildName = strval($guildNode['name']);
					$playerGuilds[] = array(
						'guildName' => $guildName,
						'guildStatus' => $statusName,
						'guildStatusId' => $statusInt
					);
				}
			}
		}
		return $playerGuilds;
	}

	/*
		  Get account points (zrzutkaPoints)
		  */
	public function getPoints()
	{
		return isset($this->xmlAccount['zrzutkaPoints']) ? intval($this->xmlAccount['zrzutkaPoints']) : 0;
	}

	/*
		  Get account email
		  */
	public function getEmail()
	{
		return isset($this->xmlAccount['email']) ? strval($this->xmlAccount['email']) : '';
	}

	/*
		  Get items id in player slots (equipment)
		  */
	public function getEquipment()
	{
		$slotNames = [
			0 => 'SLOT_WHEREEVER',
			1 => 'SLOT_HEAD',
			2 => 'SLOT_NECKLACE',
			3 => 'SLOT_BACKPACK',
			4 => 'SLOT_ARMOR',
			5 => 'SLOT_RIGHT',
			6 => 'SLOT_LEFT',
			7 => 'SLOT_LEGS',
			8 => 'SLOT_FEET',
			9 => 'SLOT_RING',
			10 => 'SLOT_AMMO',
			11 => 'SLOT_DEPOT'
		];
		$this->slotsData = [];
		if (!isset($this->xmlPlayer->inventory->slot)) {
			return $this->slotsData;
		}
		foreach ($this->xmlPlayer->inventory->slot as $slot) {
			$slotId = (int) $slot['slotid'];
			$itemId = isset($slot->item) ? (int) $slot->item['id'] : 0;
			$this->slotsData[$slotId] = [
				'slotName' => isset($slotNames[$slotId]) ? $slotNames[$slotId] : 'UNKNOWN_SLOT',
				'itemId' => $itemId
			];
		}
		return $this->slotsData;
	}

	/*
		  ===========================================================
		  Set functions
		  ===========================================================
	   */

	/*
	  Set new password
	  */
	public function setPassword($password)
	{
		$this->xmlAccount['pass'] = $password;
		$makeChange = $this->xmlAccount->asXML($this->xmlAccountFilePath);
		return $makeChange ? TRUE : FALSE;
	}

	/*
	  Set premium days value
	  */

	public function setPremDays($count)
	{
		$this->xmlAccount['premDays'] = $count;
		$makeChange = $this->xmlAccount->asXML($this->xmlAccountFilePath);
		return $makeChange ? TRUE : FALSE;
	}

	/*
		Set sex
	   */

	public function setSex($number)
	{
		if ($number >= 0 and $number < 5) {
			$this->xmlPlayer['sex'] = $number;
			$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
		} else {
			$this->throwError('Error: Range of arguments allowed: 0-4', 1);
		}
		return isset($makeChange) && $makeChange ? TRUE : FALSE;
	}

	/*
		Remove character from account and delete player file
		set second argument to TRUE if you want to remove account file altogether
	   */

	public function removeCharacter($charName, $accountRemove = NULL)
	{
		foreach ($this->xmlAccount->characters->character as $seg) {
			if ($seg['name'] == $charName) {
				$dom = dom_import_simplexml($seg);
				$dom->parentNode->removeChild($dom);
				$makeChange = $this->xmlAccount->asXML($this->xmlAccountFilePath);
				$makeRemove = unlink($this->xmlPlayerFilePath);
				if ($accountRemove == TRUE) {
					$makeRemoveAcc = unlink($this->xmlAccountFilePath);
				}
			} else {
				$this->throwError('Error: Player doesn`t exist.', 1);
			}
		}
		return (isset($makeChange) && isset($makeRemove)) ? TRUE : FALSE;
	}

	/*
		Ban player
		Args:
		duration: set in houres
		reason: will be displayed on site
	   */

	public function setBan($duration, $reason, $comment, $finalwarning, $deleted, $extend = NULL)
	{
		$this->getBanStatus();
		if ($this->ban['status'] == 1 and $extend == NULL) {
			$this->throwError('Error: Player is already banned.', 1);
		} else {
			if ($this->ban['finalwarning'] == 1) {
				$deleted = 1;
			}
			$durationHoures = $duration * 3600;
			$this->xmlPlayer->ban['banned'] = 1;
			$this->xmlPlayer->ban['banstart'] = time();
			$this->xmlPlayer->ban['banend'] = time() + $durationHoures;
			$this->xmlPlayer->ban['banrealtime'] = date('Y-m-d H:i:s', $this->ban['end']);
			$this->xmlPlayer->ban['comment'] = $comment;
			$this->xmlPlayer->ban['action'] = 'Account ban - XML class';
			$this->xmlPlayer->ban['reason'] = $reason;
			$this->xmlPlayer->ban['deleted'] = $deleted;
			$this->xmlPlayer->ban['finalwarning'] = $finalwarning;
			$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
		}
		return isset($makeChange) && $makeChange ? TRUE : FALSE;
	}

	/*
		Unban player
		Optional args:
		removeFW - removing final warning
		removeDel - removing perm ban
	   */

	public function removeBan($removeFW = NULL, $removeDel = NULL)
	{
		$this->getBanStatus();
		if ($this->ban['status'] == 0) {
			$this->throwError('Error: Player is not banned. No action needed', 1);
		} else {
			$this->xmlPlayer->ban['banned'] = 0;
			$this->xmlPlayer->ban['banstart'] = 0;
			$this->xmlPlayer->ban['banend'] = 0;
			$this->xmlPlayer->ban['comment'] = '';
			$this->xmlPlayer->ban['action'] = '';
			$this->xmlPlayer->ban['reason'] = '';
			if ($removeFW == 1) {
				$this->xmlPlayer->ban['finalwarning'] = 0;
			}
			if ($removeDel == 1) {
				$this->xmlPlayer->ban['deleted'] = 0;
			}
			$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
		}
		return isset($makeChange) && $makeChange ? TRUE : FALSE;
	}

	/*
		Set access
	   */

	public function setAccess($number)
	{
		$this->xmlPlayer['access'] = intval($number);
		$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
		return isset($makeChange) && $makeChange ? TRUE : FALSE;
	}

	/*
		Set promotion
	   */

	public function setPromotion($number)
	{
		$this->xmlPlayer['promoted'] = intval($number);
		$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
		return isset($makeChange) && $makeChange ? TRUE : FALSE;
	}

	/*
		Set capacity
	   */

	public function setCapacity($number)
	{
		$this->xmlPlayer['cap'] = intval($number);
		$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
		return isset($makeChange) && $makeChange ? TRUE : FALSE;
	}

	/*
		Change player name
		you have to manually change in guilds and houses when otserv is online
	   */

	public function setName($name)
	{
		$currentName = $this->xmlPlayer['name'];
		$this->xmlPlayer['name'] = strval($name);
		$makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
		$rename = rename($this->xmlPlayerFilePath, $this->playersDir . $name . '.xml');
		foreach ($this->xmlAccount->characters->character as $seg) {
			if ($seg['name'] == $currentName) {
				$seg['name'] = trim($name);
				$makeChangeAcc = $this->xmlAccount->asXML($this->xmlAccountFilePath);
			}
		}
		return (isset($makeChange) && isset($makeChangeAcc)) ? TRUE : FALSE;
	}

	/*
		Set new points value node: zrzutkaPoints
	   */

	public function setPoints($points)
	{
		$this->xmlAccount['zrzutkaPoints'] = intval($points);
		$saveStatus = $this->xmlAccount->asXML($this->xmlAccountFilePath);
		return $saveStatus !== false;
	}

	/*
		Set new email value
	   */

	public function setEmail($email)
	{
		$this->xmlAccount['email'] = $email;
		$saveStatus = $this->xmlAccount->asXML($this->xmlAccountFilePath);
		return $saveStatus !== false;
	}



	/*
	   removePlayers House
	   This method can be used only when server is not running
	   */


	public function removePlayersHouses($playerName)
	{

		$houseFound = array(); //start array where player is stored

		$houses = glob($this->housesPath . '*.xml');


		foreach ($houses as $house) {
			//opens a file
			$open = htmlentities(file_get_contents($house));
			//check if player is found
			//var_dump($open);
			$found = strpos($open, $playerName);

			if ($found > 0) {
				//add housename to array
				//we can use later to display houises name player owns
				$houseFound[] = $house;
			}

		}
		//we need to define empty strings
		$this->house['count'] = count($houseFound);


		foreach ($houseFound as $playerHouse) {
			//lets open each house and check access rights for player
			$xml = simplexml_load_file($playerHouse);
			//var_dump($xml);


			//now we need to iterate of each ownership tag and delete the node
			foreach ($xml->owner as $owner) {

				if ($owner['name'] == $playerName) {

					unset($xml->owner['name']);
				}
			}

			foreach ($xml->subowner as $subowner) {

				if ($subowner['name'] == $playerName) {

					unset($xml->subowner['name']);
				}
			}
			foreach ($xml->doorowner as $doorowner) {

				if ($doorowner['name'] == $playerName) {

					unset($xml->doorowner['name']);
				}
			}

			foreach ($xml->guest as $guest) {

				if ($guest['name'] == $playerName) {

					unset($xml->guest['name']);
				}
			}


			$makeChange = $xml->asXML($playerHouse);

		}


		if ($makeChange) {

			return TRUE;
		} else {
			return FALSE;
		}

	}


	//end class
}
