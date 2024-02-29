<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityFile;
use App\Models\User;
use App\Models\File;
use App\Services\DeleteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    public function createUserFile(Request $request, $id){
        try{
            $validateData = $request->validate([
                'name' => 'max:255|required|string',
                'link' => 'string|required',
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        if(!User::find($id) || User::find($id)->archive)
            return Response(['message'=>'User doesn\'t exist!'], 404);


        $file = File::create([
            'name' => $validateData['name'],
            'link' => $validateData['link'],
            'id_user' => $id,
        ]);

        return Response(['file' => $file], 201);
    }

    public function createActivityFile(Request $request, $id){
        try{
            $validateData = $request->validate([
                'name' => 'max:255|required|string',
                'link' => 'string|required',
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

       if(!Activity::find($id) ||Activity::find($id)->archive)
           return response()->json(['message' => 'Element doesn\'t exist'], 404);

        $file = File::create([
            'name' => $validateData['name'],
            'link' => $validateData['link'],
        ]);

        $file->activities()->attach($id,  ['archive' => false]);

        return Response(['file' => $file], 201);
    }



    public function getUserFiles(Request $request, $id){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "files." . $field;

        if(!User::find($id) || User::find($id)->archive)
            return Response(['message'=>'User doesn\'t exist!'], 404);

        $files = File::select('id', 'name', 'link', 'archive')
            ->where('id_user', $id)
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
            })
            ->orderBy($field, $sort)
            ->paginate($perPage, ['*'], 'page', $page + 1);

        return response()->json($files);
    }

    public function getUserFile($id, $idFile){
        return File::find($idFile) ? File::where('id_user', $id)->where('id',$idFile)->first() ? File::select('id', 'name', 'link', 'archive')->where('id_user', $id)->where('id', $idFile)->get() : response()->json(['message' => 'User doesn\'t exist'], 404) : response()->json(['message' => 'Element doesn\'t exist'], 404);
    }

    public function getActivityFiles(Request $request, $id){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 1);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "files." . $field;

        if(!Activity::find($id) ||Activity::find($id)->archive)
            return response()->json(['message' => 'Element doesn\'t exist'], 404);

        $activites = File::select('files.id', 'files.name', 'files.link', 'files.archive')
            ->join('activity_files', 'activity_files.id_file', '=', 'files.id')
            ->where('activity_files.id_activity', '=', $id)
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
            })
            ->orderBy($field, $sort)
            ->paginate($perPage, ['*'], 'page', $page + 1);

        return response()->json($activites);
    }

    public function getActivityFile($id, $idFile){
        return Activity::find($id) ? File::join('activity_files', 'activity_files.id_file', '=', 'files.id')->where('activity_files.id_activity', '=', $id)->where('files.id', $idFile)->first() ? File::select('files.id', 'files.name', 'files.link', 'files.archive')->join('activity_files', 'activity_files.id_file', '=', 'files.id')->where('activity_files.id_activity', '=', $id)->where('files.id', $idFile)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404) : response()->json(['message' => 'Activity doesn\'t exist'], 404);
    }

    public function deleteActivityFile($id, $idFile){
        try{
            $file = File::find($idFile);
            if(!$file || $file->archive)
                return response()->json(['message' => 'Element doesn\'t exist'], 404);
            $file->archive = true;
            $activity_files= ActivityFile::where('id_activity', $id)->get();

            if(!$activity_files->isEmpty())
                ActivityFile::where('id_activity', $id)->update(['archive' => true]);

            $file->save();
            return response()->json(['message' => 'Deleted successfully.'], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteUserFile($id, $idFile){
        $user = User::find($id);
        if(!$user || $user->archive)
            return response()->json(['message' => 'User doesn\'t exist'], 404);

        $service = new DeleteService();
        return $service->deleteService($idFile, 'App\Models\File');
    }



}
