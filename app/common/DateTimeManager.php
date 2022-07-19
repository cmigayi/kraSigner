<?php

namespace App\Common;

class DateTimeManager{
    private $log;

    public function __construct($log){
        $this->log = $log;
    }

    public function cleanDateTime($unreadableDate){
        $clean = explode("(",$unreadableDate);
        $clean2 = explode(")",$clean[1]);
        $this->log->info("Clean date: from $unreadableDate to $clean2[0]");
        return $clean2[0];
    }

    public function convertEpochTimeToDateTime($epoch){
        $date = date("Y/m/d", substr($epoch, 0, -3));
        $this->log->info("Convert epoch date: from $epoch to $date");
        return $date;
    }

    public function getDateFromUnreadableDateEpochDate($unreadableDate){
        $cleanedEpoch = $this->cleanDateTime($unreadableDate);
        return $this->convertEpochTimeToDateTime($cleanedEpoch);
    }
}