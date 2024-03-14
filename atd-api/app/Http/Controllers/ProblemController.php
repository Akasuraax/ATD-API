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

