<?php
// $date = "/Date(1658147439398)/";
// $date1 = explode("(",$date);
// $date2 = explode(")",$date1[1]);
// //echo substr($date, 7,10);
// //$epoch = substr($date[0], 7,10);
// //$epoch2 = 1655958857230;
// echo date("Y-m-d H:i:s", substr($date2[0], 0, -3));
// //echo substr($epoch, 0, 10);
setlocale(LC_MONETARY,"en_US");
echo number_format(300, 2);
?>