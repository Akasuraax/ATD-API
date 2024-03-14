<?php

namespace App\Http\Controllers;

use App\Models\Annexe;
use App\Models\Vehicle;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnnexesController extends Controller
{
    public function createAnnexe(Request $request){
        try{
            $validateData = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'zipcode' => 'required|digits:5|integer'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $exist = Annexe::where('address', $validateData['address'])->where('zipcode', $validateData['zipcode'])->first();
        if($exist)
            return response()->json(['message' => 'This annexe with this address already exist !'], 409);

        $annexe = Annexe::create([
            'name' => $validateData['name'],
            'address' => $validateData['address'],
            'zipcode' => $validateData['zipcode']
        ]);

        return Response(['annexe' => $annexe], 201);
    }

    public function getAnnexes(Request $request){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "annexes." . $field;

        $annexe = Annexe::select('id', 'name', 'address', 'zipcode', 'archive')
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

        return response()->json($annexe);
    }

    public function getAnnexesAll(Request $request){

        $annexes = Annexe::select('id', 'name')
                            ->get();

        if($annexes) {
            return response()->json($annexes);
        } else {
            return response("Your element doesn\'t exists", 404);
        }
    }
    public function getAnnexe($id){
        return  Annexe::find($id) ? Annexe::select('id', 'name', 'address', 'zipcode', 'archive')->where('id', $id)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function deleteAnnexe($id){
        try{
            $annexe = Annexe::findOrFail($id);
            if($annexe->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            $annexe->archive();
            $annexe = Annexe::findOrFail($id);
            return response()->json(['annexe' => $annexe,  'message' => "Deleted !"], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateAnnexe($id, Request $request)
    {
        try {
            $annexe = Annexe::findOrFail($id);
            try {
                $requestData = $request->validate([
                    'name' => 'string|max:255',
                    'address' => 'string',
                    'zipcode' => 'digits:5|integer',
                    'archive' => 'boolean'
                ]);
            } catch (ValidationException $e) {
                return response()->json(['errors' => $e->errors()], 422);
            }

            if(isset($requestData['address'])){
                if(isset($requestData['zipcode']))
                    $exist = Annexe::where('address', $requestData['address'])->where('zipcode', $requestData['zipcode'])->first();
                else
                    $exist = Annexe::where('address', $requestData['address'])->where('zipcode', $annexe->zipcode)->first();
                if ($exist)
                    return response()->json(['message' => 'This annexe with this address already exist !'], 409);
            }else if(isset($requestData['zipcode'])) {
                $exist = Annexe::where('address', $annexe->address)->where('zipcode', $requestData['zipcode'])->first();
                if ($exist)
                    return response()->json(['message' => 'This annexe with this address already exist !'], 409);
            }

            foreach ($requestData as $key => $value) {
                if (in_array($key, $annexe->getFillable()))
                    $annexe->$key = $value;
            }
            $annexe->save();

            return response()->json(['annexe' => $annexe], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
