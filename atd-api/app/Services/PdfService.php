<?php

namespace App\Services;

use App\Models\Activity;
use TCPDF;

class PdfService
{
    public function generatePdf(array $content, Activity $activity, int $id_journey)
    {
        $pdf = new TCPDF();

        $pdf->AddPage();
        $html = "<h1>Votre Itinéraire</h1><br>";

        foreach ($content as $line) {
            $html .= "<p>$line</p>";
        }

        $pdf->writeHTML($html);

        $pdfFileName = $activity->id . '-' . $id_journey . '.pdf';

        $pdf->Output(public_path('./storage/pdf/' . $pdfFileName), 'F');

        return public_path('./storage/pdf/' . $pdfFileName);
    }
}
