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

        $exist = Type::where('name', ucfirst(strtolower($validateData['name'])))->first();
        if($exist)
            return response()->json(['message' => 'This type already exist !'], 409);


        $type = Type::create([
            'name' => ucfirst(strtolower($validateData['name'])),
            'description' => $validateData['description'],
            'access_to_warehouse' => $validateData['access_to_warehouse'],
            'access_to_journey' => $validateData['access_to_journey'],
        ]);

        return Response(['type' => $type], 201);
    }

    public function getTypes(Request $request)
    {
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "types." . $field;

        $type = Type::select('id', 'name', 'description', 'access_to_warehouse', 'access_to_journey', 'archive')
            ->where(function ($query) use ($fieldFilter, $operator, $value) {
                if ($fieldFilter && $operator && $value !== '*') {
                    switch ($operator) {
                        case 'contains':
                            $query->where($fieldFilter, 'LIKE', '%' . $value . '%');
                            break;
                        case 'equals':
                            $query->where($fieldFilter, '=', $value);
                            break;
                        case 'startsWith':
                            $query->where($fieldFilter, 'LIKE', $value . '%');
                            break;
                        case 'endsWith':
                            $query->where($fieldFilter, 'LIKE', '%' . $value);
                            break;
                        case 'isEmpty':
                            $query->whereNull($fieldFilter);
                            break;
                        case 'isNotEmpty':
                            $query->whereNotNull($fieldFilter);
                            break;
                        case 'isAnyOf':
                            $values = explode(',', $value);
                            $query->whereIn($fieldFilter, $values);
                            break;
                    }
                }
            })
            ->orderBy($field, $sort)
            ->paginate($perPage, ['*'], 'page', $page + 1);

        return response()->json($type);
    }

    public function getType($id){
        return Type::find($id) ? Type::select('id', 'name', 'description', 'access_to_warehouse', 'access_to_journey', 'archive')->where('id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);

    }

    public function deleteType($id){
        try{
            $type = Type::findOrFail($id);
            if($type->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
            $type->archive = true;

            $demands = Demand::where('id_type', $id)->where('archive', false)->get();
            if(!$demands->isEmpty()){
                foreach($demands as $demand){
                    $service = new DeleteService();
                    $service->deleteService($demand->id, 'App\Models\Demand');
                }
            }
            $type->save();

            return response()->json(['type' => $type], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateType($id, Request $request){
        try{
            $type = Type::findOrFail($id);
            try {
                $requestData = $request->validate([
                    'name' => 'string|max:128',
                    'description' => 'string',
                    'access_to_warehouse' => 'boolean',
                    'access_to_journey' => 'boolean',
                    'archive' => 'boolean'
                ]);
            }catch(ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            if(isset($requestData['name'])) {
                $exist = Type::where('name', ucfirst(strtolower($requestData['name'])))->first();
                if ($exist)
                    return response()->json(['message' => 'This type already exist !'], 409);
            }

            foreach($requestData as $key => $value){
                if(in_array($key, $type->getFillable())){
                    if($key == 'name')
                        $type->$key = ucfirst(strtolower($value));
                    else
                        $type->$key = $value;
                }
            }
            $type->save();

            return response()->json(['type' => $type], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }



}
