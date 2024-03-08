<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LanguageController extends Controller
{
    public function createLanguage(Request $request){
        try{
            $validateRequest = $request->validate([
                'abbreviation' => 'string|max:2|required',
                'language_file' => 'required|file',
                'language_file.*' => 'mimes:application/json',
                'icon' => 'required',
                'icon.*' => 'mimes:png'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        try{
            $file = $request->language_file;
            $icon = $request->icon;

            $nameFile = 'translation' . '.' . $file->getClientOriginalExtension();
            $nameIcon = $validateRequest['abbreviation'] . '.' . $icon->getClientOriginalExtension();

            $file->move(public_path() . '/storage/languages/' . $validateRequest['abbreviation'] . '/', $nameFile);
            $icon->move(public_path() . '/storage/languages/' . $validateRequest['abbreviation'] . '/', $nameIcon);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        Return response()->json(['message' => 'Created !'], 201);
    }

    public function getLanguageJSON($abbreviation){
        $fileContents = file_get_contents(public_path('/storage/languages/' . $abbreviation . '/translation.json'));
        return response($fileContents)->header('Content-Type', 'application/json');
    }

    public function getLanguageIcon($abbreviation){
        $fileContents = file_get_contents(public_path('/storage/languages/' . $abbreviation . '/' . $abbreviation . '.png'));
        return response($fileContents)->header('Content-Type', 'application/json');
    }
}
