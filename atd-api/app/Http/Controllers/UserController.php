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

            $users = User::orderBy($field,$sort)
                            ->paginate($perPage, ['*'], 'page', $page+1);

            return $users;
        }
}
