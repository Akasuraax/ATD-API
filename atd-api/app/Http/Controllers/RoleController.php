<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpParser\Node\Expr\List_;

class RoleController extends Controller
{
    public function getRoles(Request $request): Collection
    {
        $roles = Role::select('id','name')
            ->where('archive', false)
            ->get();

        return $roles;
    }
}
