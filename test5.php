<?php
$itemsCount = 0;
$page = 1;
$total = 100;
$itemsPerPage = 6;
for($i=0;$i<=$total;$i++){
    if($itemsCount == $itemsPerPage){
        // close opened page
        echo "--------close page: ".$page."--------<br/>";
        $page = $page+1;
        $itemsCount = 0;
    }
    if($itemsCount == 0 && $i<$total){
        // open page
        echo "-------open page: ".$page."--------<br/>";
    }
    echo $i."<br/>";
    if($i==$total){
        // close opened page
        echo "--------close page: ".$page."--------<br/>";
    }
    $itemsCount=$itemsCount+1;
} 