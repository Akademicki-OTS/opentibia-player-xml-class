<?php
/*
Open Tibia XML player class
Version: 0.0.1
Author: Pawel 'Pavlus' Janisio
License: MIT
Github: https://github.com/PJanisio/....
*/



class xmlPlayer {

//predefined variables

public $showFullError = 1; //shows backtrace of error message //def: 1
public $errorTxt = ''; 







public function __construct($dataPath) {

$this->dataPath = $dataPath;
	$this->realPath = realpath($this->dataPath);
	
		
		if($this->realPath !== false AND is_dir($this->realPath)) {
        return $this->dataPath;
    }
		else {
			$this->throwError('Invalid path', 1);
			return FALSE;
			}




}


public function throwError($errorTxt, $showError) {
			echo '<b>'.$errorTxt.'</b><br>';
			
			if($showError == 1)
			throw new Exception($this->errorTxt);
			
		}
		



}

?>