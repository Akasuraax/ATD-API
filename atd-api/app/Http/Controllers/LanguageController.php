<?php

namespace App\Http\Controllers;

use http\Env\Response;
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
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        try{
            $file = $request->language_file;
            print($file);

            $nameFile = 'translation' . '.' . $file->getClientOriginalExtension();

            $file->move(public_path() . '/storage/languages/' . $validateRequest['abbreviation'] . '/', $nameFile);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        Return response()->json(['message' => 'Created !'], 201);
    }

    public function deleteLanguage($abbreviation){
        unlink(public_path('/storage/languages/' . $abbreviation . '/translation.json'));
        rmdir(public_path('/storage/languages/' . $abbreviation));
        return response(['message' => 'Deleted !'], 201);
    }


    public function getLanguageJSON($abbreviation){
        $fileContents = file_get_contents(public_path('/storage/languages/' . $abbreviation . '/translation.json'));
        return response($fileContents)->header('Content-Type', 'application/json');
    }

    public function getLanguages()
    {
        $languages = [];
        foreach (glob(public_path() . '/storage/languages/*', GLOB_ONLYDIR) as $dir) {
            $languages[] = str_replace("/Users/linaphe/Documents/ATD-API/atd-api/public/storage/languages/", "", $dir);
        };

        return response($languages)->header('Content-Type', 'application/json');
    }

    public function getLanguagesList()
    {
        $languages = [];
        $count = 1;
        foreach (glob(public_path() . '/storage/languages/*', GLOB_ONLYDIR) as $dir) {
            $abbreviation = basename($dir);
            $languages[] = ["id" => $count, "abbreviation" => $abbreviation];
            $count++;
        }

        return response()->json(['data' => $languages]);
    }

    public function getLanguageDetails($abbreviation){
        $languages = [];
        foreach (glob(public_path() . '/storage/languages/' . $abbreviation, GLOB_ONLYDIR) as $dir) {
            $abbreviation = basename($dir);
            $languages[] = ["abbreviation" => $abbreviation];
        }

        if(empty($languages))
            return response()->json(['message'=>'Not Found'], 404);

        return response()->json($languages);
    }
}
