<?php

namespace App\Http\Controllers;

use App\Models\Demand;
use App\Models\Type;
use App\Services\DeleteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
class TypeController extends Controller
{
    public function createType(Request $request){
        try {
            $validateData = $request->validate([
                'name' => 'required|string|max:128',
                'description' => 'nullable|string',
                'type_image' => 'nullable|mimes:png,jpg,jpeg',
                'display' => 'required|boolean',
                'access_to_warehouse' => 'required|boolean',
                'access_to_journey' => 'required|boolean',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $exist = Type::where('name', ucfirst(strtolower($validateData['name'])))->where('archive',false)->first();
        if($exist)
            return response()->json(['message' => 'This type already exists!'], 409);
        if($validateData['display'] == 1 && !$request->type_image)
            return response()->json(['message' => 'You have to put an image if you want to display the type'], 422);
        if($validateData['display'] == 0 && $request->type_image)
            return response()->json(['message' => 'You can\'t put an image if you don\'t want to display the type'], 422);

        if($file = $request->type_image)
            $nameFile = 'icon' . '.' . $file->getClientOriginalExtension();

        $type = Type::create([
            'name' => ucfirst(strtolower($validateData['name'])),
            'description' => $validateData['description'],
            'display' => $validateData['display'],
            'image' => $nameFile ?? null,
            'access_to_warehouse' => $validateData['access_to_warehouse'],
            'access_to_journey' => $validateData['access_to_journey'],
        ]);

        if($request->type_image)
            $file->move(public_path() . '/storage/types/' . $type->id . '/', $nameFile);

        return response()->json(['type' => $type], 201);
    }

    public function getTypes(Request $request)
    {
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 0);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "types." . $field;

        $type = Type::select('*')
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
        return Type::find($id) ? Type::select('id', 'name', 'description', 'access_to_warehouse', 'access_to_journey', 'display','archive')->where('id', $id)->first() : response()->json(['message' => 'Element doesn\'t exist'], 404);

    }

    public function deleteType($id){
        try{
            $type = Type::findOrFail($id);
            if($type->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            $type->archive();
            $type = Type::findOrFail($id);

            return response()->json(['type' => $type], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateType($id, Request $request){
        try {
            $type = Type::findOrFail($id);

            $requestData = $request->validate([
                'name' => 'required|string|max:128',
                'description' => 'nullable|string',
                'display' => 'required|boolean',
                'type_image' => 'nullable|mimes:png, jpeg, jpg',
                'access_to_warehouse' => 'required|boolean',
                'access_to_journey' => 'required|boolean',
                'archive' => 'required|boolean'
            ]);

            $exist = Type::where('name', ucfirst(strtolower($requestData['name'])))->whereNotIn('id', [$id])->first();
            if ($exist)
                return response()->json(['message' => 'This type already exists!'], 409);
            if($requestData['display'] == 1 && !$request->type_image)
                return response()->json(['message' => 'You have to put an image if you want to display the type'], 422);
            if($requestData['display'] == 0 && $request->type_image)
                return response()->json(['message' => 'You can\'t put an image if you don\'t want to display the type'], 422);


            if ($request->hasFile('type_image')) {
                $file = $request->file('type_image');
                $nameFile = 'icon' . '.' . $file->getClientOriginalExtension();
                $file->move(public_path() . '/storage/types/' . $id . '/', $nameFile);
                $type->image = $nameFile;
            }

            $requestData['name'] = ucfirst(strtolower($requestData['name']));

            try{
                $type->update($requestData);
                $type->save();
            } catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The type you selected is not found'], 404);
            }

            return response()->json(['type' => $type], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }



}
