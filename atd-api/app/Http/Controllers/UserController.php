<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends Controller
{
    public function getUsers(Request $request): LengthAwarePaginator
{

            $perPage = $request->input('pageSize', 10);
            $page = $request->input('page', 1);
            $field = $request->input('field', "id");
            $sort = $request->input('sort', "asc");
            $field = "users." . $field;

            $users = User::Select('*')
                            ->join('have_roles', 'users.id', '=', 'have_roles.id_user')
                            ->join('roles', 'have_roles.id_role', '=', 'roles.id')
                            ->orderBy($field,$sort)
                            ->paginate($perPage, ['*'], 'page', $page+1);

            return $users;

    /*>join('drives', 'journeys.id', '=', 'drives.id_journey')
    ->join('vehicles', 'drives.id_vehicle', '=', 'vehicles.id')
    ->where('journeys.archive', false)
    ->get(); */

        }
}
