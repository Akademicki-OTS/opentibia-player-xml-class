<?php
//example run

error_reporting(E_ALL);
include('xml.player.php');

//data path
$data = 'testbench'; 

//start constructor
$player = new xmlPlayer($data);


$playerName = 'Uwu'; // we will deal with this player :)

    //open both account file and player file, you can switch to other player anytime and class will aouto-close previous one
    if($player->prepare($playerName) == TRUE) {

      echo 'Player '.$playerName.' has been loaded <br>';

    }
          
          //check if player has finalwarning flag
          $ban = $player->getBanStatus();
            //display chosen argument
            echo 'Finalwarning: '.$ban['finalwarning'].'<br>';

            //lets see how many percent he needs for next level
         echo 'He needs:'.$player->getExpPercentNextLevel().' % for next level<br><br>';

          echo 'Struture of xml player file: <br>';
         //show structure of xml file in class way
         $player->showStructurePlayer();
?>