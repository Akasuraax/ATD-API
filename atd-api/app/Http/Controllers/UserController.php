<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\RoleController;
use function Webmozart\Assert\Tests\StaticAnalysis\length;

class UserController extends Controller
{
    public function getUsers(Request $request): LengthAwarePaginator
    {

        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "users." . $field;

        $users = User::select('*')
            ->with('roles')
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
            } )
            ->orderBy($field, $sort)
            ->paginate($perPage, ['*'], 'page', $page + 1);

        return $users;
    }

    public function getUser(int $userId)
    {

        $user = User::where('id', $userId)
            ->with('roles')
            ->first();

        if ($user) {
            $response = $user;
            $status = 200;
        } else {
            $response = [
                'message' => 'Your element doesn\'t exists'
            ];
            $status = 404;
        }

        return Response($response, $status);
    }

    public function patchUser(int $userId, Request $request)
    {
        try {

            $fields = $request->validate([
                'name' => 'required|string|max:255',
                'forname' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($userId),
                ],
                'phone_number' => 'nullable|string|max:15',
                'gender' => 'required|int|max:1',
                'birth_date' => 'required|date',
                'address' => 'required|string',
                'zipcode' => 'required|string|max:5',
                'siret_number' => 'nullable|string|max:14',
                'compagny' => 'nullable|string',
                'roles' => 'required|array'
            ]);


            $roles = $request['roles'];
            $rolesRequired = [1,2,3,4,5];
            $roleIds = array_column($roles, 'id');
            if(count(array_intersect($roleIds, $rolesRequired)) != 1){
                return response()->json(['errors' => "The list of roles is incorrect"], 400);
            }

            $roleController = app(RoleController::class);
            $validRoles = $roleController->getRoles($request);
            $validIds = $validRoles->pluck('id')->all();

            if(count(array_intersect($roleIds, $validIds)) != 1){
                return response()->json(['errors' => "The list of roles is incorrect"], 400);
            }

           if(count(array_diff($roleIds, $validIds)) != 0){
               return response()->json(['errors' => "The list of roles is incorrect"], 400);

           }


            $user = User::findOrFail($userId);
            $user->update($fields);
            $user->roles()->sync($roleIds);
            $user->load('roles');

            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user
            ], 200);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function deleteUser(int $userId, Request $request)
    {

        $ban = $request->input('ban', 0);
        $user = User::where('id', $userId)
            ->with('roles')
            ->first();
        if (!$user) {
            $response = [
                'message' => 'Your element doesn\'t exists'
            ];
            $status = 404;
            return Response($response, $status);
        }

        if($ban == "true") {
            $user->update(['ban' => true, 'archive' => true]);
        } else {
            $user->update(['archive' => true]);
        }
            $response = [
                'message' => 'Deleted !',
                'user' => $user
            ];
            $status = 200;
        return Response($response, $status);
    }
}

