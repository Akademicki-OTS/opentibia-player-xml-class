<?php
//example run

error_reporting(E_ALL);
include('xml.player.php');

//data path
$data = 'testbench'; 

//start constructor
$player = new xmlPlayer($data);

    //open both account file and player file, you can switch to other player anytime and class will aouto-close previous one
    $player->prepare('Mr Black');
    
    //show structure of xml file in class way
    $player->showStructurePlayer();

    //i dont want to add example xml files ;] so please see outpu
    //output:

    /*
    object(SimpleXMLElement)#2 (14) {
  ["@attributes"]=>
  array(14) {
    ["name"]=>
    string(8) "Mr Black"
    ["account"]=>
    string(6) "******"
    ["sex"]=>
    string(1) "1"
    ["lookdir"]=>
    string(1) "0"
    ["exp"]=>
    string(10) "2211038673"
    ["voc"]=>
    string(1) "2"
    ["level"]=>
    string(3) "874"
    ["access"]=>
    string(1) "0"
    ["cap"]=>
    string(4) "9030"
    ["bless"]=>
    string(1) "0"
    ["maglevel"]=>
    string(3) "162"
    ["lastlogin"]=>
    string(10) "1707772453"
    ["promoted"]=>
    string(1) "1"
    ["banned"]=>
    string(1) "0"
  }
  ["spawn"]=>
  object(SimpleXMLElement)#4 (1) {
    ["@attributes"]=>
    array(3) {
      ["x"]=>
      string(3) "126"
      ["y"]=>
      string(2) "52"
      ["z"]=>
      string(1) "4"
    }
  }
  ["temple"]=>
  object(SimpleXMLElement)#5 (1) {
    ["@attributes"]=>
    array(3) {
      ["x"]=>
      string(3) "160"
      ["y"]=>
      string(2) "54"
      ["z"]=>
      string(1) "7"
    }
  }
  */

?>