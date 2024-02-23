<?php

namespace App\Http\Controllers;

use App\Models\Demand;
use App\Models\Type;
use App\Services\DeleteService;
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

    public function getTypes()
    {

        return Type::select('name', 'description', 'access_to_warehouse', 'access_to_journey', 'archive')->where('archive', false)->get();
    }
    public function deleteType($id){
        try{
            $type = Type::find($id);
            if(!$type || $type->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $type->archive = true;
            $demands = Demand::where('id_type', $id)->where('archive', false)->get();
            if(!$demands->isEmpty()){
                foreach($demands as $demand){
                    $service = new DeleteService();
                    $service->deleteDemandService($demand->id);
                }
            }

            $type->save();

            return response()->json(['message' => 'Deleted successfully.'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
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
