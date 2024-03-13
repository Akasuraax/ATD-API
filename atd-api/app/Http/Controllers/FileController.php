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
                'names' => 'array|required',
                'links' => 'required',
                'links.*' => 'mimes:pdf,jpg,jpeg,png'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user = User::findOrFail($id);
        if($user->archive)
            return response()->json(['message' => 'The user you selected is archived.'], 405);

        foreach($validateData['names'] as $name){
            if(!is_string($name) || strlen($name) > 255)
                return Response(['message'=>'The name ' . $name  . ' should be a string and have less than 255 characters.'], 422);
        }

        $index = 0;
        try {
            if ($request->links) {
                foreach ($request->links as $file) {
                    $name = $id . '-' . strtolower(str_replace(' ', '-', $validateData['names'][$index])) . '.' . $file->extension();
                    $file->move(public_path() . '/storage/users/' . $id . '/', $name);

                    File::create([
                        'name' => $validateData['names'][$index],
                        'link' => '/storage/users/' . $id . '/' . $name,
                        'id_user' => $id
                    ]);

                    $index++;
                }
            }
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        return Response(['message' => 'Created !'], 201);
    }

    public function createActivityFile(Request $request, $id){
        try{
            $validateData = $request->validate([
                'activity_files' => "required",
                'activity_files.*' => 'mimes:pdf,jpg,png,jpeg'
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $activity = Activity::findOrFail($id);
        if($activity->archive)
           return response()->json(['message' => 'The activity you selected is archived.'], 405);

        try {
            if ($request->activity_files) {
                foreach ($request->activity_files as $file) {
                    $name = $id . '-' . time() . rand(1, 99) . '.' . $file->extension();
                    $file->move(public_path() . '/storage/activities/' . $id . '/', $name);

                    $newFile = File::create([
                        'name' => $name,
                        'link' => '/storage/activities/' . $id . '/' . $name,
                    ]);

                    $newFile->activities()->attach($id, ['archive' => false]);
                }
            }
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        return Response(['message' => "Added !"], 201);
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

        $activities = File::select('files.id', 'files.name', 'files.link', 'files.archive')
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

        return response()->json($activities);
    }

    public function getActivityFile($id, $idFile){
        return Activity::find($id) ? File::join('activity_files', 'activity_files.id_file', '=', 'files.id')->where('activity_files.id_activity', '=', $id)->where('files.id', $idFile)->first() ? File::select('files.id', 'files.name', 'files.link', 'files.archive')->join('activity_files', 'activity_files.id_file', '=', 'files.id')->where('activity_files.id_activity', '=', $id)->where('files.id', $idFile)->get() : response()->json(['message' => 'Element doesn\'t exist'], 404) : response()->json(['message' => 'Activity doesn\'t exist'], 404);
    }

    public function deleteActivityFile($id, $idFile){
        try{
            $file = File::findOrFail($idFile);
            if($file->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
            $file->archive($idFile, $file->name);
            $file = File::findOrFail($idFile);
            return response()->json(['file' => $file], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteUserFile($id, $idFile){
        User::findOrFail($id);

        $service = new DeleteService();
        return $service->deleteService($idFile, 'App\Models\File');
    }
}
