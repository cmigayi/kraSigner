<?php

namespace App\Common;

class MoneyManager{
    private $log;

    public function __construct($log){
        $this->log = $log;
    }

    public function formatToMoney($number){
        $money = number_format($number,2);
        $this->log->info("Format to money: from $number to $money");
        return $money;
    }
}