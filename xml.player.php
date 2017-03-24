<?php
/*
Open Tibia XML player class
Version: 0.0.2
Author: Pawel 'Pavlus' Janisio
License: MIT
Github: https://github.com/PJanisio/....
*/



class xmlPlayer {

//predefined variables

public $showError = 1; //shows backtrace of error message //def: 1
public $errorTxt = ''; //placeholder for error text //def: ''
public $playerName = '';
public $playersDir = '';
public $accountsDir = '';
public $xml = NULL;
public $account = 0;




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


public function throwError($errorTxt, $showError) {
			
			
			if($showError == 1) {
			echo '<b>'.$errorTxt.'</b><br>';
			throw new Exception($this->errorTxt);			
		}

}


public function prepare($playerName) {
//function to open xml stream, not to open it every time for one player

		$this->xml = @simplexml_load_file($this->playersDir.$playerName.'.xml');
		
			if($this->xml === FALSE) //returns not boolean false what the heck
				$this->throwError('Player do not exists!', 1);
				else
					return $this->xml;
}

public function showStructure() {
//correct
return var_dump($this->xml);

}


public function getAccount() {

return $this->xml['account'];

}


public function getSex() {

return $this->xml['sex'];

}

}

?>