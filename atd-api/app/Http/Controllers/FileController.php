<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityFile;
use App\Models\User;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    public function createUserFile(Request $request, $id){
        try{
            $validateData = $request->validate([
                'name' => 'string|required|max:255',
                'link' => 'required|mimes:pdf,jpg,jpeg,png|max:20000'
            ]);
        } catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user = User::findOrFail($id);
        if($user->archive)
            return response()->json(['message' => 'The user you selected is archived.'], 405);

        try {
            if ($request->hasFile('link')) {
                $file = $request->file('link');
                $isFile = File::where('name', $validateData['name'])->where('id_user', $id)->where('archive', false)->first();
                if($isFile) {
                    if ($validateData['name'] == "Autre") {
                        $count = File::where('name', 'LIKE', 'Autre %')->where('id_user', $id)->count();
                        $validateData['name'] = $validateData['name'] . ' ' . $count+1;
                    }
                    else
                        return Response(['message' => 'This file already exists'], 409);
                }
                $name = $id . '-' . strtolower(str_replace(' ', '-', $validateData['name'])) . '.' . $file->getClientOriginalExtension();

                $file->move(public_path() . '/storage/users/' . $id . '/', $name);

                $newFile = File::create([
                    'name' => $validateData['name'],
                    'link' => 'storage/users/' . $id . '/' . $name,
                    'id_user' => $id
                ]);
            }
        } catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }

        return Response(['file' => $newFile], 201);
    }

    public function createActivityFile(Request $request, $id){
        try{
            $validateData = $request->validate([
                'activity_files' => "required",
                'activity_files.*' => 'mimes:pdf,jpg,png,jpeg|max:20000'
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
                    $newFile = $file->getClientOriginalName();
                    $count = File::where('name', 'LIKE', '%'. pathinfo($id . '-' . $newFile, PATHINFO_FILENAME) . '%')->where('archive',false)->count();
                    if($count>0)
                        $name = pathinfo($id . '-' . $newFile, PATHINFO_FILENAME) . $count+1 . '.' . $file->getClientOriginalExtension();
                    else
                        $name = $id . '-' . strtolower(str_replace(' ', '-', $newFile));
                    $file->move(public_path() . '/storage/activities/' . $id . '/', $name);
                    $newFile = File::create([
                        'name' => $id . '-' . ($count > 0 ? pathinfo($newFile, PATHINFO_FILENAME) . ($count + 1) . '.' . $file->getClientOriginalExtension() : pathinfo($newFile, PATHINFO_FILENAME) . '.' . $file->getClientOriginalExtension()),
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

    public function downloadFile($id){
        $file = File::findOrFail($id);
        return response()->download(public_path() . '/' . $file->link);
    }

    public function getUserFiles(Request $request, $id){
        $perPage = $request->input('pageSize', 10);
        $page = $request->input('page', 0);
        $field = $request->input('field', "id");
        $sort = $request->input('sort', "asc");

        $fieldFilter = $request->input('fieldFilter', '');
        $operator = $request->input('operator', '');
        $value = $request->input('value', '%');

        $field = "files." . $field;

        $files = File::select('id', 'name', 'link', 'archive')
            ->where('id_user', $id)
            ->where('archive', false)
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
        $page = $request->input('page', 0);
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
            ->where('files.archive', false)
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
            $activity = Activity::findOrFail($id);
            $file = File::findOrFail($idFile);
            if($file->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
            $file->archiveActivity($file->link);
            $file = File::findOrFail($idFile);
            return response()->json(['file' => $file], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }

    public function deleteUserFile($id, $idFile){
        try{
            $user = User::findOrFail($id);
            $file = File::findOrFail($idFile);
            if($file->archive)
                return response()->json(['message' => 'Element is already archived.'], 405);
            $file->archiveUser($file->link);
            $file = File::findOrFail($idFile);
            return response()->json(['file' => $file], 200);
        }catch(ValidationException $e){
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
    }
}
