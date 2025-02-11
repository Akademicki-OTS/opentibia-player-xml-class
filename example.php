<?php
//example run

error_reporting(E_ALL);
include('xml.player.php');

//data path
$data = 'testbench'; 

//start constructor
$player = new xmlPlayer($data);


$playerName = 'Pavlus'; // we will deal with this player :)

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

            $skills = $player->getSkills();

              echo 'club skill level: '.$skills['club'].'<br>';
              echo 'shield skill level: '.$skills['shield'].'<br>';

          
                //check player houses
                $player->getHouses('Pavlus');

              if($player->house['count'] > 0) {

          echo 'Owns: '.$player->house['owner'].'<br>';
          echo 'Subowns: '.$player->house['subowner'].'<br>';
          echo 'Guest of: '.$player->house['guest'].'<br>';
          echo 'Doorowner of: '.$player->house['doorowner'].'<br><br>';

                }

            //show player outfit

            $image = $player->showOutfit();

         ?>

         <img src="<?php echo $image; ?>"><br>

         <?php

         //show structure of xml file in class way
        echo 'Struture of xml player file: <br>';
        
         $player->showStructurePlayer();


         //get guilds as an array

         $g = $player->getGuild();

         //get equipment data

         $equipment = $player->getEquipment();
            print_r($equipment);



         

