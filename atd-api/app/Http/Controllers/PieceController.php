<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PieceController extends Controller
{
    public function createPiece(Request $request)
    {
        try{
            $validateData = $request->validate([
                'expired_date' => 'required|date_format:Y-m-d H:i',
                'weight' => 'required|int',
                'id_warehouse' => 'required|int',
                'id_product' => 'required|int'
            ]);

        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        if(Warehouse::find($validateData['id_warehouse'])->archive)
            return response()->json(['message' => 'The warehouse you put doesn\'t exist'], 404);

        if(Product::find($validateData['id_product'])->archive)
            return response()->json(['message' => 'The product you put doesn\'t exist'], 404);

        $piece = Piece::create([
           'expired_date' => $validateData['expired_date'],
            'weight' => $validateData['weight'],
            'id_warehouse' => $validateData['id_warehouse'],
            'id_product' => $validateData['id_product']
        ]);

        $response = [
            'piece' => $piece
        ];

        return Response($response, 201);
    }

    public function getPieces()
    {
        return Piece::select('id', 'expired_date', 'weight', 'id_warehouse', 'id_product', 'archive')->where('archive', false)->get();
    }

    public function getPiece($id)
    {
        return Piece::select('id', 'expired_date', 'weight', 'id_warehouse', 'id_product', 'archive')->where('archive', false)->where('id', $id)->get();
    }

    public function deletePiece($id)
    {
        $piece = Piece::find($id);

        if($piece && !$piece->archive){
            $piece->archive = true;
            $piece->save();

            $response = [
                'message'=>'Deleted !'
            ];
            $status = 200;
        }else{
            $response = [
                'message'=>'Your element doesn\'t exists'
            ];
            $status = 404;
        }

        return Response($response, $status);
    }
    public function updatePiece($id, Request $request)
    {
        $piece = Piece::find($id);

        if($piece && !$piece->archive){
            try{
                $requestData = $request->validate([
                    'expired_date' => 'date_format:Y-m-d H:i',
                    'weight' => 'int',
                    'id_warehouse' => 'int',
                    'id_product' => 'int'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            foreach($requestData as $key => $value){
                if(in_array($key, $piece->getFillable())) {
                    if($key == 'id_warehouse'){
                        if(!Warehouse::find($value) || Warehouse::find($value)->archive)
                            return response()->json(['message' => 'The warehouse you put doesn\'t exist'], 404);
                    }else if($key == 'id_product'){
                        if(!Warehouse::find($value) || Product::find($value)->archive)
                            return response()->json(['message' => 'The product you put doesn\'t exist'], 404);
                    }else {
                        $piece->$key = $value;
                    }
                }
            }
            $piece->save();

            $response = [
                'piece'=> $piece
            ];

            $status = 200;
        } else{
            $response = [
                'message'=>'Your element doesn\'t exists'
            ];
            $status = 404;
        }

        return Response($response, $status);
    }


}
