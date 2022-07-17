<?php

namespace App\Common;

use Dompdf\Dompdf;
use Dompdf\Options;

class HTMLToPDFManager{
    // instantiate and use the dompdf class
    private $dompdf;
    private $log;

    public function __construct($log){
        $this->log = $log;

        $options = new Options();
        $options->set('chroot', __DIR__);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $this->dompdf = new Dompdf($options);
        //$this->dompdf = new Dompdf();
    }

    public function genPDF($htmlTemplate,$invoice){
        $this->dompdf->loadHtml($htmlTemplate);
        $file = "invoices/invoice_".$invoice->InvoiceNumber.".pdf";

        // (Optional) Setup the paper size and orientation
        $this->dompdf->setPaper('A4', 'landscape');

        // Render the HTML as PDF
        $this->dompdf->render();
        echo "PDF rendered";
        $this->log->info("PDF rendered");

        // Output the generated PDF to Browser
        //$this->dompdf->stream();

        $output = $this->dompdf->output();
        $this->log->info("PDF output generated");
        file_put_contents($file, $output);
        $this->log->info("PDF file: ".$file);
        return $file;
    }
}