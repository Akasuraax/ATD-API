<?php

namespace App\Services;

use App\Http\Controllers\FileController;
use App\Models\Activity;
use App\Models\File;
use App\Models\Journey;
use App\Models\Vehicle;
use TCPDF;

class PdfService
{
    public function generatePdf(array $content, Activity $activity, Journey $journey, Vehicle $vehicle)
    {
        $pdf = new TCPDF();

        $pdf->AddPage();
        $html = "<h1>Votre Itinéraire</h1><br>";
        $html .= "<p>Véhicule : $vehicle->name</p>";
        $html .= "<p>Plaque : $vehicle->license_plate</p><br>";

        $html .= "<h1>Trajet : $journey->name</h1><br>";
        foreach ($content as $line) {
            $html .= "<p>$line</p>";
        }

        $pdf->writeHTML($html);

        $pdfFileName = $activity->id . '-' .'journey' . '.pdf';

        $count = File::where('name', 'LIKE', '%'. pathinfo($pdfFileName, PATHINFO_FILENAME) . '%')->where('archive',false)->count();
        if($count>0) {
            $file = File::where('name', 'LIKE', '%'. pathinfo($pdfFileName, PATHINFO_FILENAME) . '%')->where('archive',false)->first();
            app(FileController::class)->deleteActivityFile($activity->id, $file->id);
        }

        $activityFolderPath = public_path('./storage/activities/' . $activity->id);

        if (!is_dir($activityFolderPath)) {
            mkdir($activityFolderPath, 0777, true);
        }



        $pdfFilePath = $activityFolderPath . '/' . $pdfFileName;

        $pdf->Output($pdfFilePath, 'F');

        return "storage/activities/" . $activity->id  . '/' . $pdfFileName;
    }
}
