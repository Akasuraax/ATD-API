<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Models\Warehouse;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class WarehouseController extends Controller
{
    public function createWarehouse(Request $request){

        try {
            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'zipcode' => 'required|digits:5|integer',
                'capacity' => 'required|integer'
            ]);
        }catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $warehouse = Warehouse::create([
            'name' => $validateData['name'],
            'address' => $validateData['address'],
            'zipcode' => $validateData['zipcode'],
            'capacity' => $validateData['capacity'],
        ]);

        $response =[
            'warehouse' => $warehouse
        ];

        return Response($response, 201);
    }

    public function getWarehouse(){
        return Warehouse::select('id', 'name', 'address', 'zipcode', 'capacity', 'archive')->where('archive', false)->get();
    }

    public function deleteWarehouse($id){
        try{
            $warehouse = Warehouse::find($id);

            if(!$warehouse || $warehouse->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $warehouse->archive = true;

            $pieces = Piece::where('id_warehouse', $id)->where('archive', false)->get();
            if(!$pieces->isEmpty()){
                foreach($pieces as $piece) {
                    $service = new DeleteService();
                    $service->deletePieceService($piece->id);
                }
            }
            $warehouse->save();
            return response()->json(['message' => 'Deleted successfully, everything linked to the warehouse was also deleted.'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateWarehouse($id, Request $request){
        $warehouse = Warehouse::find($id);
        if($warehouse && !$warehouse->archive){
            try{
                $requestData = $request->validate([
                    'name' => 'string|max:255',
                    'address' => 'string',
                    'zipcode' => 'digits:5|integer',
                    'capacity' => 'integer'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            foreach($requestData as $key => $value){
                if(in_array($key, $warehouse->getFillable()))
                    $warehouse->$key = $value;
            }
            $warehouse->save();

            $response = [
                'warehouse' => $warehouse
            ];

            $status = 200;
        }else{
            $response = [
                'message'=>'Your element doesn\'t exist'
            ];
            $status = 404;
        }

        return response()->json($response, $status);
    }
}
