<?php
/*
Open Tibia XML player class
Version: 0.3.14
Author: Pawel 'Pavlus' Janisio
License: MIT
Github: https://github.com/PJanisio/opentibia-player-xml-class
*/

class XmlPlayer {

    // Predefined variables
    // Private
    private string $dataPath = '';
    private string $realPath = '';
    private string $housesPath = '';
    private string $mapPath = '';
    private string $monsterPath = '';
    private int $showError = 1; // Shows backtrace of error message //def: 1

    // Public
    // Strings
    public string $errorTxt = ''; // Placeholder for error text //def: ''
    public string $playerName = '';
    public string $skull = '';
    public string $playersDir = '';
    public string $accountsDir = '';
    public string $lastElement = ''; // Double check if will be needed
    public string $xmlPlayerFilePath = ''; // Exact path for PREPARED player
    public string $xmlAccountFilePath = ''; // Exact path for PREPARED account
    public string $structurePlayer = '';
    public string $structureAccount = '';
    public string $vocationName = '';
    public string $outfitUrl = '';
    // Bools
    public ?SimpleXMLElement $xmlPlayer = NULL; // Handler for player
    public ?SimpleXMLElement $xmlAccount = NULL; // Handler for account
    // Ints and Floats
    public int $account = 0;
    public int $food = 0;
    public int $reqMana = 0;
    public int $magicLevelPercent = 0;
    public int $expNextLevel = 0;
    public int $expPercNextLevel = 0;
    public int $expLevel = 0;
    // Arrays
    public array $skills = [];
    public array $look = []; 
    public array $characters = []; // Names of other players on the same account
    public array $spawn = [];
    public array $temple = [];
    public array $frags = [];
    public array $lastModified = [];
    public array $health = [];
    public array $mana = [];
    public array $storage = [];
    public array $ban = []; // Ban status, start, end, comment
    public array $dead = [];
    public array $house = [];

    /**
     * Constructor that checks paths and defines directories
     * 
     * @param string $dataPath
     */
    public function __construct(string $dataPath) {
        $this->dataPath = $dataPath;
        $this->realPath = realpath($this->dataPath);

        if ($this->realPath === false || !is_dir($this->realPath)) {
            $this->throwError('Data path invalid!', 1);
        }

        if (!is_dir($this->realPath.'/players') || !is_dir($this->realPath.'/accounts')) {
            $this->throwError('Players/Accounts path is invalid!', 1);
        } else {
            $this->playersDir = $this->realPath.'/players/';
            $this->accountsDir = $this->realPath.'/accounts/';
            $this->housesPath = $this->realPath.'/houses/';
            $this->mapPath = $this->realPath.'/world/';
            $this->monsterPath = $this->realPath.'/monster/';
        }
    }

    /**
     * Throws an error and optionally shows backtrace
     * 
     * @param string $errorTxt
     * @param int $showError
     */
    private function throwError(string $errorTxt, int $showError): void {
        if ($showError === 1) {
            echo $errorTxt;
            throw new Exception($errorTxt);            
        }
    }

    /**
     * Opens XML stream for player and account file
     * 
     * @param string $playerName
     * @return bool
     */
    public function prepare(string $playerName): bool {
        $playerName = trim(stripslashes($playerName));
        $this->xmlPlayerFilePath = $this->playersDir.$playerName.'.xml';

        $this->xmlPlayer = simplexml_load_file($this->xmlPlayerFilePath, 'SimpleXMLElement', LIBXML_PARSEHUGE);

        if ($this->xmlPlayer === false) {
            $this->throwError('Player does not exist!', 1);
        } else {
            $this->xmlAccountFilePath = $this->accountsDir.$this->getAccount().'.xml';
            $this->xmlAccount = simplexml_load_file($this->xmlAccountFilePath, 'SimpleXMLElement', LIBXML_PARSEHUGE);

            if ($this->xmlAccount === false) {
                $this->throwError('Account file for player does not exist!', 1);
            }
        }

        return $this->xmlAccount && $this->xmlPlayer;
    }

    /**
     * Calculate health based on vocation and level
     * 
     * @param int $vocation
     * @param int $level
     * @return int
     */
    public function calculateHealth(int $vocation, int $level): int {
        switch ($vocation) {
            case 1: // Sorcerer
            case 2: // Druid
                return (($level - 1) * 5) + 150;
            case 3: // Paladin
                return (($level - 1) * 10) + 150;
            case 4: // Knight
                return (($level - 1) * 15) + 150;
            default:
                $this->throwError('Invalid vocation for health calculation.', 1);
        }
    }

    /**
     * Calculate mana based on vocation and level
     * 
     * @param int $vocation
     * @param int $level
     * @return int
     */
    public function calculateMana(int $vocation, int $level): int {
        switch ($vocation) {
            case 1: // Sorcerer
            case 2: // Druid
                return (($level - 1) * 30);
            case 3: // Paladin
                return (($level - 1) * 15);
            case 4: // Knight
                return (($level - 1) * 5);
            default:
                $this->throwError('Invalid vocation for mana calculation.', 1);
        }
    }

    /**
     * Creates a new player with given attributes and loads inventory from external XML file
     * 
     * @param string $playerName
     * @param int $accountNumber
     * @param int $vocation
     * @param int $level
     * @param int $maglevel
     * @param int $fistLevel
     * @param int $clubLevel
     * @param int $swordLevel
     * @param int $axeLevel
     * @param int $distanceLevel
     * @param int $shieldLevel
     * @param int $fishLevel
     * @param string $inventoryFilePath Path to the inventory XML file
     * @param int $promoted
     * @return bool
     */
    public function createPlayer(
        string $playerName,
        int $accountNumber,
        int $vocation,
        int $level,
        int $maglevel,
        int $fistLevel,
        int $clubLevel,
        int $swordLevel,
        int $axeLevel,
        int $distanceLevel,
        int $shieldLevel,
        int $fishLevel,
        string $inventoryFilePath, // New parameter for the inventory file path
        int $promoted = 1
    ): bool {
        if ($vocation < 1 || $vocation > 4) {
            $this->throwError('Invalid vocation. Valid values are 1 to 4.', 1);
        }
    
        $exp = $this->getExpForLevel($level, 5);
        $health = $this->calculateHealth($vocation, $level);
        $mana = $this->calculateMana($vocation, $level);
        $manaSpent = $this->getRequiredMana($vocation, $maglevel);
    
        // Load inventory from external XML file
        $inventoryXml = file_get_contents($inventoryFilePath);
        if ($inventoryXml === false) {
            $this->throwError('Failed to load inventory file.', 2);
        }
    
        $playerTemplate = '<?xml version="1.0"?>
    <player name="' . $playerName . '" account="' . $accountNumber . '" sex="1" lookdir="3" exp="' . $exp . '" voc="' . $vocation . '" level="' . $level . '" access="0" cap="300" bless="0" магевел="' . $maglevel . '" lastlogin="1720695462" promoted="' . $promoted . '" banned="0">
        <spawn x="160" y="54" z="7"/>
        <temple x="160" y="54" z="7"/>
        <skull type="0" kills="0" ticks="0" absolve="0" pzlocked="0" infightticks="0"/>
        <health now="' . $health . '" max="' . $health . '" food="0"/>
        <mana now="' . $mana . '" max="' . $mana . '" spent="' . $manaSpent . '"/>
        <ban banned="0" banstart="0" banend="0" comment="" reason="" action="" deleted="0" finalwarning="0" banrealtime=""/>
        <look type="130" head="20" body="30" legs="40" feet="50"/>
        <skills>
            <skill skillid="0" level="' . $fistLevel . '" tries="0"/>
            <skill skillid="1" level="' . $clubLevel . '" tries="0"/>
            <skill skillid="2" level="' . $swordLevel . '" tries="0"/>
            <skill skillid="3" level="' . $axeLevel . '" tries="0"/>
            <skill skillid="4" level="' . $distanceLevel . '" tries="0"/>
            <skill skillid="5" level="' . $shieldLevel . '" tries="0"/>
            <skill skillid="6" level="' . $fishLevel . '" tries="0"/>
        </skills>
        <spells></spells>
        <deaths></deaths>
        <inventory>' . $inventoryXml . '</inventory>
    </player>';
    
        // Save the player's data
        $filePath = $this->playersDir . $playerName . '.xml';
        if (!is_dir($this->playersDir)) {
            $this->throwError('Players directory does not exist: ' . $this->playersDir, 1);
        }
    
        $fileCreated = file_put_contents($filePath, $playerTemplate);
        if ($fileCreated === false) {
            return false;
        }
    
        // Add the new character to the account file
        $accountFilePath = $this->accountsDir . $accountNumber . '.xml';
        if (!file_exists($accountFilePath)) {
            $this->throwError('Account file does not exist: ' . $accountFilePath, 1);
        }
    
        $xmlAccount = simplexml_load_file($accountFilePath);
        if ($xmlAccount === false) {
            $this->throwError('Failed to load account file: ' . $accountFilePath, 1);
        }
    
        if (!isset($xmlAccount->characters)) {
            $xmlAccount->addChild('characters');
        }
    
        $newCharacter = $xmlAccount->characters->addChild('character');
        $newCharacter->addAttribute('name', $playerName);
    
        $accountUpdated = $xmlAccount->asXML($accountFilePath);
        if ($accountUpdated === false) {
            $this->throwError('Failed to update account file: ' . $accountFilePath, 1);
        }
    
        return true;
    }

    /**
     * Show XML structure for player file
     * 
     * @return void
     */
    public function showStructurePlayer(): void {
        echo '<pre>', var_dump($this->xmlPlayer), '</pre>';
    }

    /**
     * Show XML structure for account file
     * 
     * @return void
     */
    public function showStructureAccount(): void {
        echo '<pre>', var_dump($this->xmlAccount), '</pre>';
    }

    /**
     * Show last modified player files (by save or by class action)
     * 
     * @param int $minutes
     * @param string|null $dateFormat
     * @return array
     */
    public function showLastModifiedPlayers(int $minutes, ?string $dateFormat = null): array {
        $dateFormat = $dateFormat ?? 'Y-m-d H:i:s';

        $files = scandir($this->playersDir);
        foreach ($files as $file) {
            $stat = stat($this->playersDir.$file);
            $lastmod = $stat['mtime'];
            $now = time();

            if ($now - $lastmod < $minutes * 60) {
                $this->lastModified[$file] = date($dateFormat, $lastmod);
            }
        }

        return $this->lastModified;
    }

    /**
     * Get account number/name
     * 
     * @return string
     */
    public function getAccount(): string {
        return strval($this->xmlPlayer['account']);
    }

    /**
     * Get premium days
     * 
     * @return int
     */
    public function getPremDays(): int {
        return intval($this->xmlAccount['premDays']);
    }

    /**
     * Get other characters on the same account
     * 
     * @return array
     */
    public function getCharacters(): array {
        foreach ($this->xmlAccount->characters->character as $character) {
            $this->characters[] = strval($character['name']);
        }

        return $this->characters;
    }

    /**
     * Get sex
     * 
     * @return int
     */
    public function getSex(): int {
        return intval($this->xmlPlayer['sex']);
    }

    /**
     * Get looktype and look direction
     * 
     * @return array
     */
    public function getLookType(): array {
        $this->look['lookdir'] = intval($this->xmlPlayer['lookdir']);
        $this->look['type'] = intval($this->xmlPlayer->look['type']); 
        $this->look['head'] = intval($this->xmlPlayer->look['head']); 
        $this->look['body'] = intval($this->xmlPlayer->look['body']); 
        $this->look['legs'] = intval($this->xmlPlayer->look['legs']); 
        $this->look['feet'] = intval($this->xmlPlayer->look['feet']); 

        return $this->look;
    }

    /**
     * Get experience points
     * 
     * @return int
     */
    public function getExp(): int {
        return intval($this->xmlPlayer['exp']);
    }

    /**
     * Get experience for any level
     * Special divider works when custom formula is used to calculate experience
     * 
     * @param int $level
     * @param int $specialDivider
     * @return int
     */
    public function getExpForLevel( $level, int $specialDivider = 1): int {

        $level = intval($level);

        $this->expLevel = ((((50 * $level / 3 - 100) * $level + 850 / 3) * $level - 200) / $specialDivider);
        return intval($this->expLevel);
    }

    /**
     * Get experience for player next level
     * 
     * @param int $specialDivider
     * @return int
     */
    public function getExpForNextLevel(int $specialDivider = 1): int {
        $currentExp = $this->getExp();
        $nextLevel = $this->getLevel() + 1;

        // Get exp for next level
        $this->expNextLevel = ((((50 * $nextLevel / 3 - 100) * $nextLevel + 850 / 3) * $nextLevel - 200) / $specialDivider) - $currentExp;

        return intval($this->expNextLevel);
    }

    /**
     * Get percentage value for next level as float
     * 
     * @param int $specialDivider
     * @return float
     */
    public function getExpPercentNextLevel(int $specialDivider = 1): float {
        $currentLevelExp = $this->getExpForLevel($this->getLevel(), $specialDivider);
        $nextLevelExp = $this->getExpForLevel($this->getLevel() + 1, $specialDivider);
        $expForNextLvl = $this->getExpForNextLevel($specialDivider);

        $this->expPercNextLevel = round(($expForNextLvl / ($nextLevelExp - $currentLevelExp) * 100), 1);

        return floatval(abs($this->expPercNextLevel)); // Return percent
    }

    /**
     * Get vocation
     * 
     * @return int
     */
    public function getVocation(): int {
        return intval($this->xmlPlayer['voc']);
    }

    /**
     * Get vocation name and check promotion
     * 
     * @return string
     */
    public function getVocationName(): string {
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

    /**
     * Get level
     * 
     * @return int
     */
    public function getLevel(): int {
        return intval($this->xmlPlayer['level']);
    }

    /**
     * Get skill levels
     * 
     * @return array
     */
    public function getSkills(): array {
        $this->skills['fist'] = intval($this->xmlPlayer->skills->skill[0]['level']);
        $this->skills['club'] = intval($this->xmlPlayer->skills->skill[1]['level']);
        $this->skills['sword'] = intval($this->xmlPlayer->skills->skill[2]['level']);
        $this->skills['axe'] = intval($this->xmlPlayer->skills->skill[3]['level']);
        $this->skills['distance'] = intval($this->xmlPlayer->skills->skill[4]['level']);
        $this->skills['shield'] = intval($this->xmlPlayer->skills->skill[5]['level']);

        return $this->skills;
    }

    /**
     * Get access
     * 
     * @return int
     */
    public function getAccess(): int {
        return intval($this->xmlPlayer['access']);
    }

    /**
     * Get capacity
     * 
     * @return int
     */
    public function getCapacity(): int {
        return intval($this->xmlPlayer['cap']);
    }

    /**
     * Get bless level
     * 
     * @return int
     */
    public function getBless(): int {
        return intval($this->xmlPlayer['bless']);
    }

    /**
     * Get magic level
     * 
     * @return int
     */
    public function getMagicLevel(): int {
        return intval($this->xmlPlayer['maglevel']);
    }

    /**
     * Get last login time
     * Available formats at: http://php.net/manual/en/function.date.php
     * 
     * @param string|null $format
     * @return int|string
     */
    public function getLastLogin(?string $format = null): int|string {
        $time = intval($this->xmlPlayer['lastlogin']);
        return $format !== null ? date($format, $time) : $time;
    }

    /**
     * Get promoted status
     * 
     * @return int
     */
    public function getPromotion(): int {
        return intval($this->xmlPlayer['promoted']);
    }

    /**
     * Get ban status
     * 
     * @return array
     */
    public function getBanStatus(): array {
        $this->ban = [];
        $this->ban['status'] = intval($this->xmlPlayer->ban['banned']); // 0;1
        $this->ban['start'] = intval($this->xmlPlayer->ban['banstart']); // Timestamp
        $this->ban['end'] = intval($this->xmlPlayer->ban['banend']); // Timestamp
        $this->ban['comment'] = strval($this->xmlPlayer->ban['comment']); 
        $this->ban['action'] = strval($this->xmlPlayer->ban['action']); 
        $this->ban['reason'] = strval($this->xmlPlayer->ban['reason']); 
        $this->ban['banrealtime'] = strval($this->xmlPlayer->ban['banrealtime']); 
        $this->ban['deleted'] = intval($this->xmlPlayer->ban['deleted']); // 0;1
        $this->ban['finalwarning'] = intval($this->xmlPlayer->ban['finalwarning']); // 0;1

        return $this->ban;
    }

    /**
     * Get spawn position as an array
     * 
     * @return array
     */
    public function getSpawnCoordinates(): array {
        $this->spawn['x'] = intval($this->xmlPlayer->spawn['x']);
        $this->spawn['y'] = intval($this->xmlPlayer->spawn['y']);
        $this->spawn['z'] = intval($this->xmlPlayer->spawn['z']);

        return $this->spawn;
    }

    /**
     * Get temple position as an array
     * 
     * @return array
     */
    public function getTempleCoordinates(): array {
        $this->temple['x'] = intval($this->xmlPlayer->temple['x']);
        $this->temple['y'] = intval($this->xmlPlayer->temple['y']);
        $this->temple['z'] = intval($this->xmlPlayer->temple['z']);

        return $this->temple;
    }

    /**
     * Get skull type
     * 
     * @return string
     */
    public function getSkull(): string {
        $this->skull = $this->xmlPlayer->skull['type'];

        switch (intval($this->skull)) {
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

    /**
     * Get frags as an array
     * 
     * @return array
     */
    public function getFrags(): array {
        $this->frags['kills'] = intval($this->xmlPlayer->skull['kills']); // Int
        $this->frags['ticks'] = intval($this->xmlPlayer->skull['ticks']);
        $this->frags['absolve'] = intval($this->xmlPlayer->skull['absolve']);

        return $this->frags;
    }

    /**
     * Get health now and max
     * 
     * @return array
     */
    public function getHealth(): array {
        $this->health['now'] = intval($this->xmlPlayer->health['now']);
        $this->health['max'] = intval($this->xmlPlayer->health['max']);

        return $this->health;
    }

    /**
     * Get food level
     * 
     * @return int
     */
    public function getFoodLevel(): int {
        return intval($this->xmlPlayer->health['food']);
    }

    /**
     * Get mana information
     * 
     * @return array
     */
    public function getMana(): array {
        $this->mana['now'] = intval($this->xmlPlayer->mana['now']);
        $this->mana['max'] = intval($this->xmlPlayer->mana['max']);
        $this->mana['spent'] = intval($this->xmlPlayer->mana['spent']);

        return $this->mana;
    }

    /**
     * Get required mana level
     * 
     * @param int $vocation
     * @param int $mlevel
     * @return int
     */
    public function getRequiredMana(int $vocation, int $mlevel): int {
        // Use mana spent and formula
        $vocationMultiplier = [1, 1.1, 1.1, 1.4, 3];

        $reqMana = intval((400 * pow($vocationMultiplier[$vocation], $mlevel - 1)));

        if ($reqMana % 20 < 10) {
            $reqMana = $reqMana - ($reqMana % 20);
        } else {
            $reqMana = $reqMana - ($reqMana % 20) + 20;
        }

        return intval($reqMana);
    }

    /**
     * Get percentage magic level
     * 
     * @param int $vocation
     * @param int $mlevel
     * @return int
     */
    public function getMagicLevelPercent(int $vocation, int $mlevel): int {
        $this->getMana();
        $this->magicLevelPercent = intval(100 * ($this->mana['spent'] / (1.0 * $this->getRequiredMana($vocation, $mlevel + 1))));

        return intval($this->magicLevelPercent);
    }

    /**
     * Get houses players own or are invited to
     * 
     * @param string $playerName
     * @return array
     */
    public function getHouses(string $playerName): array {
        $houseFound = []; // Start array where player is stored

        $houses = glob($this->housesPath.'*.xml');

        foreach ($houses as $house) {
            $open = htmlentities(file_get_contents($house));
            $found = strpos($open, $playerName);

            if ($found > 0) {
                $houseFound[] = $house; 
            }
        }

        $this->house['count'] = count($houseFound);
        $this->house['owner'] = '';
        $this->house['subowner'] = '';
        $this->house['doorowner'] = '';
        $this->house['guest'] = '';

        foreach ($houseFound as $playerHouse) {
            $xml = simplexml_load_file($playerHouse);

            foreach ($xml->owner as $owner) {
                if ($owner['name'] == $playerName) {
                    $this->house['owner'] .= basename($playerHouse, '.xml').', ';
                }
            }

            foreach ($xml->subowner as $subowner) {
                if ($subowner['name'] == $playerName) {
                    $this->house['subowner'] .= basename($playerHouse, '.xml').', ';
                }
            }
            foreach ($xml->doorowner as $doorowner) {
                if ($doorowner['name'] == $playerName) {
                    $this->house['doorowner'] .= basename($playerHouse, '.xml').', ';
                }
            }

            foreach ($xml->guest as $guest) {
                if ($guest['name'] == $playerName) {
                    $this->house['guest'] .= basename($playerHouse, '.xml').', ';
                }
            }
        }

        return $this->house; // Return array of houses and rights
    }

    /**
     * Get storage values
     * 
     * @return array
     */
    public function getStorageValues(): array {
        foreach ($this->xmlPlayer->storage->data as $item) {
            $key = strval($item['key']);
            $value = strval($item['value']);
            $this->storage[$key] = $value;
        }

        return $this->storage;
    }

    /**
     * Get deaths
     * 
     * @return array
     */
    public function getDeaths(): array {
        foreach ($this->xmlPlayer->deaths->death as $id) {
            $this->dead = $id;
        }

        return $this->dead;
    }

    /**
     * Create an outfit URL
     * 
     * @return string
     */
    public function showOutfit(): string {
        $look = $this->getLookType();
        $this->outfitUrl = 'https://outfit-images.ots.me/772/animoutfit.php?id='.$look['type'].'&head='.$look['head'].'&body='.$look['body'].'&legs='.$look['legs'].'&feet='.$look['feet'].'&direction=3';
        return $this->outfitUrl;
    }

    /**
     * Set new password
     * 
     * @param string $password
     * @return bool
     */
    public function setPassword(string $password): bool {
        $this->xmlAccount['pass'] = $password;
        $makeChange = $this->xmlAccount->asXML($this->xmlAccountFilePath);

        return $makeChange !== false;
    }

    /**
     * Set new premium days
     * 
     * @param int $count
     * @return bool
     */
    public function setPremDays(int $count): bool {
        $this->xmlAccount['premDays'] = $count;
        $makeChange = $this->xmlAccount->asXML($this->xmlAccountFilePath);

        return $makeChange !== false;
    }

    /**
     * Set sex
     * 
     * @param int $number
     * @return bool
     */
    public function setSex(int $number): bool {
        if ($number >= 0 && $number < 5) {
            $this->xmlPlayer['sex'] = $number;
            $makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
        } else {
            $this->throwError('Error: Range of arguments allowed: 0-4', 1);
        }

        return $makeChange !== false;
    }

    /**
     * Remove character from account and delete player file
     * Set second argument to TRUE if you want to remove account file altogether
     * 
     * @param string $charName
     * @param bool|null $accountRemove
     * @return bool
     */
    public function removeCharacter(string $charName, ?bool $accountRemove = null): bool {
        foreach ($this->xmlAccount->characters->character as $seg) {
            if ($seg['name'] == $charName) {
                $dom = dom_import_simplexml($seg);
                $dom->parentNode->removeChild($dom);
                $makeChange = $this->xmlAccount->asXML($this->xmlAccountFilePath);

                $makeRemove = unlink($this->xmlPlayerFilePath);

                if ($accountRemove === true) {
                    $makeRemoveAcc = unlink($this->xmlAccountFilePath);
                }
            } else {
                $this->throwError('Error: Player doesn`t exist.', 1);
            }
        }

        return isset($makeChange) && isset($makeRemove) && (isset($makeRemoveAcc) || !$accountRemove);
    }

    /**
     * Ban player
     * 
     * @param int $duration Set in hours
     * @param string $reason Will be displayed on site
     * @param string $comment Comment about the ban
     * @param int $finalwarning Final warning flag
     * @param int $deleted Deleted flag
     * @param bool|null $extend Extend existing ban
     * @return bool
     */
    public function setBan(int $duration, string $reason, string $comment, int $finalwarning, int $deleted, ?bool $extend = null): bool {
        $banStatus = $this->getBanStatus();
        if ($banStatus['status'] == 1 && $extend === null) {
            $this->throwError('Error: Player is already banned.', 1);
        } else {
            if ($banStatus['finalwarning'] == 1) {
                $deleted = 1;
            }

            $durationHours = $duration * 3600;

            $this->xmlPlayer->ban['banned'] = 1; // 0;1
            $this->xmlPlayer->ban['banstart'] = time(); // Timestamp
            $this->xmlPlayer->ban['banend'] = time() + $durationHours; // Timestamp
            $this->xmlPlayer->ban['banrealtime'] = date('Y-m-d H:i:s', $this->xmlPlayer->ban['banend']);
            $this->xmlPlayer->ban['comment'] = $comment;
            $this->xmlPlayer->ban['action'] = 'Account ban - XML class';
            $this->xmlPlayer->ban['reason'] = $reason;
            $this->xmlPlayer->ban['deleted'] = $deleted; // 0;1
            $this->xmlPlayer->ban['finalwarning'] = $finalwarning; // 0;1

            $makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
        }

        return $makeChange !== false;
    }

    /**
     * Unban player
     * 
     * @param bool|null $removeFW Removing final warning
     * @param bool|null $removeDel Removing permanent ban
     * @return bool
     */
    public function removeBan(?bool $removeFW = null, ?bool $removeDel = null): bool {
        $banStatus = $this->getBanStatus();
        if ($banStatus['status'] == 0) {
            $this->throwError('Error: Player is not banned. No action needed.', 1);
        } else {
            $this->xmlPlayer->ban['banned'] = 0; // 0;1
            $this->xmlPlayer->ban['banstart'] = 0; // Timestamp
            $this->xmlPlayer->ban['banend'] = 0; // Timestamp
            $this->xmlPlayer->ban['comment'] = '';
            $this->xmlPlayer->ban['action'] = '';
            $this->xmlPlayer->ban['reason'] = '';

            if ($removeFW === true) {
                $this->xmlPlayer->ban['finalwarning'] = 0; // 0;1
            }
            if ($removeDel === true) {
                $this->xmlPlayer->ban['deleted'] = 0; // 0;1
            }
            $makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);
        }

        return $makeChange !== false;
    }

    /**
     * Set access
     * 
     * @param int $number
     * @return bool
     */
    public function setAccess(int $number): bool {
        $this->xmlPlayer['access'] = intval($number);
        $makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

        return $makeChange !== false;
    }

    /**
     * Set promotion
     * 
     * @param int $number
     * @return bool
     */
    public function setPromotion(int $number): bool {
        $this->xmlPlayer['promoted'] = intval($number);
        $makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

        return $makeChange !== false;
    }

    /**
     * Set capacity
     * 
     * @param int $number
     * @return bool
     */
    public function setCapacity(int $number): bool {
        $this->xmlPlayer['cap'] = intval($number);
        $makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

        return $makeChange !== false;
    }

    /**
     * Change player name
     * You have to manually change in guilds and houses when the server is online
     * 
     * @param string $name
     * @return bool
     */
    public function setName(string $name): bool {
        // Changing player file
        $currentName = $this->xmlPlayer['name'];

        $this->xmlPlayer['name'] = strval($name);
        $makeChange = $this->xmlPlayer->asXML($this->xmlPlayerFilePath);

        $rename = rename($this->xmlPlayerFilePath, $this->playersDir.$name);

        // Changing account file
        foreach ($this->xmlAccount->characters->character as $seg) {
            if ($seg['name'] == $currentName) {
                $seg['name'] = trim($name);
                $makeChangeAcc = $this->xmlAccount->asXML($this->xmlAccountFilePath);
            }
        }

        if ($makeChange === false || $makeChangeAcc === false) {
            return false;
        }

        return $makeChange && $makeChangeAcc;
    }

    /**
     * This method can not be used as game engine stores houses information in memory and will overwrite saved data
     */

    /*
    public function removePlayersHouses(string $playerName): bool {
        $houseFound = array(); // Start array where player is stored

        $houses = glob($this->housesPath.'*.xml');

        foreach ($houses as $house) {
            $open = htmlentities(file_get_contents($house));
            $found = strpos($open, $playerName);

            if ($found > 0) {
                $houseFound[] = $house; 
            }
        }

        $this->house['count'] = count($houseFound);

        foreach ($houseFound as $playerHouse) {
            $xml = simplexml_load_file($playerHouse);

            // Now we need to iterate of each ownership tag and delete the node
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

        return $makeChange !== false;
    }
    */
}
