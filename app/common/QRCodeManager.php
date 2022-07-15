<?php

namespace App\Common;

use PHPQRCode\QRcode;

class QRCodeManager{
    function genQRCode($text){
        $file = "tmp/qrcode_".uniqid().".png";
        QRcode::png($text, $file, 'L', 4, 4);
        echo "QRCode generated";
        return $file;
    }
}