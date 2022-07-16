<?php
$str = file_get_contents('raw_json.json');
$json = json_decode($str);
echo '<pre>' . print_r($json, true) . '</pre>';
?>