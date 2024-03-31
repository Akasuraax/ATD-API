<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class QrCodeController extends Controller
{
    public function generateQrCode($pieceId){
        $url = route('deletePiece', ['pieceId' => $pieceId]);

        return QrCode::size(200)->generate($url);
    }

    public function deletePiece($pieceId){
        return app(PieceController::class)->deletePiece($pieceId);
    }
}
