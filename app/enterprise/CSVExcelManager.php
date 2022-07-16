<?php

namespace App\Enterprise;

use Spatie\SimpleExcel\SimpleExcelWriter;

class CSVExcelManager{
    private $writer;

    public function __construct($pathToCsv){
        $this->writer = SimpleExcelWriter::create($pathToCsv);
    }


    public function writeToTracker($invoiceNumber,$signingStatus,$kraUrl,$templateStatus,$emailStatus){
        $this->writer->addRow([
            'InvoiceNumber' => $invoiceNumber,
            'Signed' => $signingStatus,
            'KRALink' => $kraUrl,
            'InvoiceTemplated' => $templateStatus,
            'EmailSent' => $emailStatus
        ]);
    }

    public function writeToTrackerRows($rows){
        $this->writer->addRows($rows);
    }
}