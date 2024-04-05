<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HaveRole;
use App\Models\Role;
use App\Models\User;
use App\Services\DeleteService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use PhpParser\Node\Expr\List_;

class RoleController extends Controller
{
    public $restrictedArray = [1,2,3,4,5,6];

    public function createRole(Request $request){
        try{
            $validateData = $request->validate([
                'name' => 'string|required|max:255',
                'role_id' => 'nullable|int'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $exist = Role::where('name', strtolower($validateData['name']))->first();
        if($exist)
            return response()->json(['message' => 'This role already exist !'], 409);

        if($validateData['role_id'] && !in_array($validateData['role_id'], $this->restrictedArray) && Role::find($validateData['role_id']))
            return response()->json(['message' => 'You didn\'t reference a valid role.'], 401);

        $role = Role::create([
            'name' => strtolower($validateData['name']),
            'role_id' => $validateData['role_id'] ?? null
        ]);

        return Response(['role' => $role], 201);
    }

    public function getRoles(Request $request)
    {
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 0);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "roles." . $field;

        $roles = Role::select('id', 'name', 'role_id','archive')
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

        return response()->json($roles);
    }

    public function getRole($id){
        return Role::find($id) ? Role::select('id', 'name', 'role_id', 'archive')->where('id', $id)->first() : response(["Not found!"], 404);
    }

    public function getAllRoles(Request $request): Collection
    {
        $roles = Role::select('id','name', 'role_id')
            ->where('archive', false)
            ->get();

        return $roles;
    }

    public function deleteRole($id){
        try{
            $role = Role::findOrFail($id);
            if($role->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);

            if(in_array($role->id, $this->restrictedArray))
                return response()->json(['message' => 'You can\'t delete this role.'], 401);

            $role->archive();
            $role = Role::findOrFail($id);

            return response()->json(['role' => $role,  'message' => "Deleted !"], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function updateRole($id, Request $request){
        try{
            $role = Role::findOrFail($id);
            try{
                $validateData = $request->validate([
                    'name' => 'required|string|max:255',
                    'role_id' => 'nullable|int',
                    'archive' => 'required|boolean'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            if(in_array($role->id, $this->restrictedArray))
                return response()->json(['message' => 'You can\'t modify this role.'], 401);

            if($validateData['role_id'] && !in_array($validateData['role_id'], $this->restrictedArray))
                return response()->json(['message' => 'You didn\'t reference a valid role.'], 401);

            $exist = Role::where('name', strtolower($validateData['name']))->whereNotIn('id', [$id])->first();
            if($exist)
                return response()->json(['message' => 'This role already exist !'], 409);

            $validateData['name'] = strtolower($validateData['name']);

            try{
                $role->update($validateData);
                $role->save();
            }catch (ModelNotFoundException $e) {
                return response()->json(['error' => 'The annexe you selected is not found'], 404);
            }

            return response()->json(['role' => $role], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
