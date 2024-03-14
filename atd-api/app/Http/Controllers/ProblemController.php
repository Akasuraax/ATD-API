<?php

namespace App\Http\Controllers;

use App\Models\Problem;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\CreatesApplication;

class ProblemController extends Controller
{
    public function createProblem(Request $request){
        try {
            $validatedData = $request->validate([
                'problem.name' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $problem = Problem::create([
            'name' => $validatedData['problem']['name']
        ]);

        return response()->json([
            'problem' => $problem
        ]);
    }

    public function getProblems(){
        $problem = Problem::where('archive', false)->get();
        return response()->json([
            'problem' => $problem
        ]);
    }

    public function getAdminProblems(Request $request){
        $perPage = $request->input('pageSize', 10);
        if($perPage > 50){
            $perPage = 50;
        }
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $problems = Problem::select('*')
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

        return response()->json([
            'problem' => $problems
        ]);
    }

    public function patchProblem(int $problem_id, Request $request){
        $problem = Problem::findOrFail($problem_id);

        try {
            $validatedData = $request->validate([
                'problem.name' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $problem->name = $validatedData['problem']['name'];
        $problem->save();
        $problem->touch();

        return response()->json([
            'problem' => $problem
        ]);
    }

    public function deleteProblem(int $problem_id){
        $problem = Problem::findOrFail($problem_id);
        $problem->archive = true;
        $problem->save();
        $problem->touch();

        return response()->json([
            'problem' => $problem
        ]);
    }
}

