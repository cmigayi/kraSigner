<?php
$url = $_SERVER['ROOT_URL']; //returns the current URL
$parts = explode('/',$url);
$dir = $_SERVER['SERVER_NAME'];
$base_dir = __DIR__;

echo $server_name = "http://".$_SERVER['SERVER_NAME']. ':' . $_SERVER['SERVER_PORT'];
?>