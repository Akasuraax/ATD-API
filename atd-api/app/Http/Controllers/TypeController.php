<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    public function createType(Request $request){
        $validateData = $request->validate([
            'name' => 'required|string|max:128',
            'description' => 'nullable|string',
            'access_to_warehouse' => 'boolean',
            'access_to_journey' => 'boolean',
        ]);

        $type = Type::create([
            'name' => $validateData['name'],
            'description' => $validateData['description'],
            'access_to_warehouse' => $validateData['access_to_warehouse'],
            'access_to_journey' => $validateData['access_to_journey'],
            'archive' => false
        ]);

        $response = [
            'type' => $type
        ];

        return Response($response, 201);

    }
}
