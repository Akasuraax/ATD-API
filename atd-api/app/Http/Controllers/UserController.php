<?php

namespace App\Http\Controllers;

use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\RoleController;
use function Webmozart\Assert\Tests\StaticAnalysis\length;

class UserController extends Controller
{
    public function register(Request $request, int $role) : JsonResponse
    {
        $verifBan = User::where('email', $request->email)->where('ban', true)->get()->first();
        if(isset($verifBan))
            return response()->json(['message' => "This email is banned"], 403);

        try {
            $fields = $request->validate([
                'name' => 'required|string|max:255',
                'forname' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'phone_number' => 'nullable|string|max:15',
                'gender' => 'nullable|integer',
                'birth_date' => 'nullable|date',
                'address' => 'required|string',
                'zipcode' => 'required|string|max:5',
                'siret_number' => 'nullable|int|max:14',
                'compagny' => 'nullable|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }


        if ($role == 2 || $role == 3) {
            $user = User::create([
                'name' => $fields['name'],
                'forname' => $fields['forname'],
                'email' => strtolower($fields['email']),
                'password' => $fields['password'],
                'phone_number' => $fields['phone_number'],
                'gender' => $fields['gender'],
                'birth_date' => $fields['birth_date'],
                'address' => $fields['address'],
                'zipcode' => $fields['zipcode'],
            ]);
        } else {
            $user = User::create([
                'name' => $fields['name'],
                'forname' => $fields['forname'],
                'email' => strtolower($fields['email']),
                'password' => $fields['password'],
                'phone_number' => $fields['phone_number'],
                'gender' => 2,
                'birth_date' => Carbon::now(),
                'address' => $fields['address'],
                'zipcode' =>$fields['zipcode'],
                'siret_number' => $fields['siret_number'],
                'compagny' => $fields['compagny'],
            ]);
        }
        //add MtM in have_roles
        $user->roles()->attach($role);

        $response = [
            'user' => $user,
        ];

        return response()->json($response, 201);
    }

    public function getUsers(Request $request): LengthAwarePaginator
    {

        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 0);
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

    public function getUsersVisit(Request $request)
    {

        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 0);

        $users = User::where('archive', false)

            ->paginate($perPage, ['forname','name','address'], 'page', $page + 1);

        return $users;
    }

    public function getUser(int $userId)
    {
        $user = User::where('id', $userId)
            ->with('roles')
            ->with('schedules')
            ->first();


        if ($user) {
            foreach ($user->schedules as $schedule) {
                $schedule->start_hour = substr($schedule->start_hour, 0, 5); // Formater "HH:MM"
                $schedule->end_hour = substr($schedule->end_hour, 0, 5); // Formater "HH:MM"
            }

            $response = $user->toArray();
            $status = 200;
        } else {
            $response = [
                'message' => 'Your element doesn\'t exists'
            ];
            $status = 404;
        }

        return response($response, $status);
    }

    public function patchUserAdmin(int $userId, Request $request)
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
                'roles' => 'required|array',
                'status' => 'required|int'
            ]);

            $roles = $request['roles'];
            $rolesRequired = [1,2,3,4,5,6];
            $roleIds = array_column($roles, 'id');
            if(count(array_intersect($roleIds, $rolesRequired)) != 1){
                return response()->json(['errors' => "The list of roles is incorrect"], 400);
            }

            $roleController = app(RoleController::class);
            $validRoles = $roleController->getAllRoles($request);
            $validIds = $validRoles->pluck('id')->all();

            if(count(array_intersect($roleIds, $validIds)) < 1)
                return response()->json(['errors' => "The list of roles is incorrect"], 400);


           if(count(array_diff($roleIds, $validIds)) != 0)
               return response()->json(['errors' => "The list of roles is incorrect"], 400);

            $user = User::findOrFail($userId);
            if($user->status != $fields['status'] || count(array_diff($user->roles()->get()->pluck('id')->toArray(),$roleIds)) != 0)
                User::where('id', $userId)->update(['remember_token' => NULL]);

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

    public function patchUser(int $userId, Request $request)
    {
        try {
            if($userId == 1)
                return response()->json(['message'=>'You can\'t modify this user !'], 401);

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
                'gender' => 'nullable|int|max:1',
                'birth_date' => 'nullable|date',
                'address' => 'required|string',
                'zipcode' => 'required|string|max:5',
                'siret_number' => 'nullable|string|max:14',
                'compagny' => 'nullable|string',
            ]);

            $user = User::findOrFail($userId);
            $user->update($fields);
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

        if($userId == 1)
            return response()->json(['message'=>'You can\'t delete this user !'], 401);

        if (!$user) {
            $response = [
                'message' => 'Your element doesn\'t exists'
            ];
            $status = 404;
            return Response($response, $status);
        }

        User::where('id', $userId)->update(['remember_token' => NULL]);

        if($ban == "true") {
            $user->update(['ban' => true]);
            $user->archive();
        } else {
            $user->archive();
        }
            $response = [
                'message' => 'Deleted !',
                'user' => $user
            ];
            $status = 200;
        return Response($response, $status);
    }

    public function getSupport(Request $request)
    {
        $users = User::where('archive', false)
            ->whereHas('roles', function ($query) {
                $query->where('id', 5);
            })
            ->select('id', 'name', 'forname')
            ->get();

        return response()->json([
            'supports' => $users
        ]);
    }
}

