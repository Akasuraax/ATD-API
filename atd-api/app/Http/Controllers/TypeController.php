<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
class TypeController extends Controller
{
    public function createType(Request $request){
        try {
            $validateData = $request->validate([
                'name' => 'required|string|max:128',
                'description' => 'nullable|string',
                'access_to_warehouse' => 'boolean',
                'access_to_journey' => 'boolean',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $type = Type::create([
            'name' => $validateData['name'],
            'description' => $validateData['description'],
            'access_to_warehouse' => $validateData['access_to_warehouse'],
            'access_to_journey' => $validateData['access_to_journey'],
            'archive' => false
        ]);

        $response = [
            'type' => $type
        ];

        return Response($response, 201);

    }

    public function getTypes(){
        return Type::select('name','description','access_to_warehouse', 'access_to_journey', 'archive')->get();
    }

    public function deleteType($id){
        $type = Type::find($id);

        if($type){
            $type->update(['archive' => true]);
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

    public function updateType($id, Request $request){
        $type = Type::find($id);
        if($type){
            $requestData = $request->all();
            foreach($requestData as $key => $value){
                if(in_array($key, $type->getFillable())){
                    $type->$key = $value;
                }
            }
            $type->save();

            $response = [
                'type' => $type
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
