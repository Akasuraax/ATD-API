<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
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
        return Warehouse::select('name', 'address', 'zipcode', 'capacity', 'archive')->where('archive', false)->get();
    }

    public function deleteWarehouse($id){
        $warehouse = Warehouse::find($id);

        if($warehouse){
            $warehouse->archive = true;
            $warehouse->save();
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

    public function updateWarehouse($id, Request $request){
        $warehouse = Warehouse::find($id);
        if($warehouse){
            $requestData = $request->all();
            foreach($requestData as $key => $value){
                if(in_array($key, $warehouse->getFillable())){
                    $warehouse->$key = $value;
                }
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
