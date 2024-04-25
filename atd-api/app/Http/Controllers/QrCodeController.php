<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class QrCodeController extends Controller
{
    public function generateQrCode($pieceId){

        $piece = Piece::select("*")
            ->where("id", $pieceId)
            ->with('product')
            ->first();

        $pieceData = [
            'name' => $piece->product->name,
            'count' => $piece->count,
            'measure' => $piece->product->measure,
            'expired_date' => $piece->expired_date,
            'delete_url' => route('deletePiece', ['pieceId' => $pieceId]),
        ];
        $jsonData = json_encode($pieceData);
        return QrCode::size(200)->generate($jsonData);
    }

    public function deletePiece($pieceId){
        return app(PieceController::class)->deletePiece($pieceId);
    }
}
