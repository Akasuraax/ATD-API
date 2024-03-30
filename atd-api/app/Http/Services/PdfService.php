<?php

namespace App\Services;

use TCPDF;

class PdfService
{
    public function generatePdf($content)
    {
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->writeHTML($content);

        $pdfFileName = 'example_' . time() . '.pdf';

        $pdf->Output(public_path('pdf/' . $pdfFileName), 'F');

        return public_path('pdf/' . $pdfFileName);
    }
}
