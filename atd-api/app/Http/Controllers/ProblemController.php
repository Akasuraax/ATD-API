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
}
