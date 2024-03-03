<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HaveRole;
use App\Models\Role;
use App\Models\User;
use App\Services\DeleteService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use PhpParser\Node\Expr\List_;

class RoleController extends Controller
{
    public function createRole(Request $request){
        try{
            $validateData = $request->validate([
                'name' => 'string|required|max:255'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $exist = Role::where('name', strtolower($validateData['name']))->first();
        if($exist)
            return response()->json(['message' => 'This role already exist !'], 409);

        $role = Role::create([
            'name' => strtolower($validateData['name'])
        ]);

        return Response(['role' => $role], 201);
    }

    public function getRoles(Request $request)
    {
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "roles." . $field;

        $roles = Role::select('id', 'name', 'archive')
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

    public function getAllRoles(Request $request): Collection
    {
        $roles = Role::select('id','name')
            ->where('archive', false)
            ->get();

        return $roles;
    }

    public function deleteRole($id){
        $role = Role::findOrFail($id);
        $have_role = HaveRole::where('id_role', $id)->get();
        if(!$have_role->isEmpty())
            return response()->json(['message' => 'You can\'t archive this role ! It\'s used'], 405);

        $service = new DeleteService();
        return $service->deleteService($id, 'App\Models\Role');
    }

    public function updateRole($id, Request $request){
        try{
            $role = Role::findOrFail($id);
            try{
                $validateData = $request->validate([
                    'name' => 'string|max:255',
                    'archive' => 'boolean'
                ]);
            }catch(ValidationException $e){
                return response()->json(['errors' => $e->errors()], 422);
            }

            foreach($validateData as $key => $value){
                if(in_array($key, $role->getFillable()))
                    $role->$key = $value;
            }
            $role->save();

            return response()->json(['role' => $role], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
