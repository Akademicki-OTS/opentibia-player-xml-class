<?php
//example run

error_reporting(E_ALL);
include('xml.player.php');

//data path
$data = 'testbench'; 

//start constructor
$player = new xmlPlayer($data);

    //open both account file and player file, you can switch to other player anytime and class will aouto-close previous one
    $player->prepare('Uwu');
          
          //check if player is banned
          $ban = $player->getBanStatus();
            //display chosen argument
            echo $ban['banrealtime'];

            //show structure of xml file in class way
         $player->showStructurePlayer();

?>