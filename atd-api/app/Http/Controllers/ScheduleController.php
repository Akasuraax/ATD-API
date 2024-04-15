<?php

namespace App\Http\Controllers;


use App\Models\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function createSchedule(int $id, Request $request){
        try {
            $fields = $request->validate([
                'schedules.*.day' => 'required|int|min:1|max:7',
                'schedules.*.start_hour' => 'required|date_format:H:i|required',
                'schedules.*.end_hour' => 'required|date_format:H:i|required'
            ]);
        } catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $schedules = [];
        foreach ($fields['schedules'] as $scheduleData) {
            $day = $scheduleData['day'];
            $start_hour = date('Y-m-d') . ' ' . $scheduleData['start_hour'];
            $end_hour = date('Y-m-d') . ' ' . $scheduleData['end_hour'];

            if($day < 1 || $day > 7){
                return response()->json([
                    'message' => 'Choose a day between Monday and Sunday.'
                ], 404);
            }

            if(!empty(Schedule::where('day', $day)->where('user_id', $id)->first())) {
                return response()->json(['message' => 'You already created a schedule for this day.'], 409);
            }

            $schedule = Schedule::create([
                'day' => $day,
                'start_hour' => $start_hour,
                'end_hour' => $end_hour,
                'user_id' => $id
            ]);

            $dayName = $this->getDayFromId($day);
            $schedules[] = [
                'day' => $dayName,
                'start_hour' => $scheduleData['start_hour'],
                'end_hour' => $scheduleData['end_hour'],
                'user' => $schedule->user
            ];
        }

        return response()->json(['schedules' => $schedules], 201);
    }

    public function updateSchedule(int $userId, Request $request)
    {
        try {
            $fields = $request->validate([
                'schedule.*.day' => 'required|integer',
                'schedule.*.start_hour' => 'required|date_format:H:i',
                'schedule.*.end_hour' => 'required|date_format:H:i'
            ]);

            $updatedSchedules = [];
            foreach ($fields['schedule'] as $scheduleData) {
                $scheduleDay = $scheduleData['day'];
                $startHour = $scheduleData['start_hour'];
                $endHour = $scheduleData['end_hour'];

                $schedule = Schedule::where('user_id', $userId)
                    ->where('day', $scheduleDay)
                    ->first();

                if ($schedule) {
                    $schedule->start_hour = $startHour;
                    $schedule->end_hour = $endHour;
                    $schedule->save();
                } else {
                    $schedule = new Schedule([
                        'user_id' => $userId,
                        'day' => $scheduleDay,
                        'start_hour' => $startHour ,
                        'end_hour' => $endHour
                    ]);
                    $schedule->save();
                }

                $updatedSchedules[] = [
                    'day' => $scheduleDay,
                    'start_hour' => $startHour,
                    'end_hour' => $endHour,
                    'user' => $schedule->user
                ];
            }

            return response()->json(['schedules' => $updatedSchedules], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'The element you selected is not found'], 404);
        }
    }
    public function getDayFromId($dayId){
        switch ($dayId){
            case 1:
                $day = "lundi";
                break;
            case 2:
                $day = "mardi";
                break;
            case 3:
                $day = "mercredi";
                break;
            case 4:
                $day = "jeudi";
                break;
            case 5:
                $day = "vendredi";
                break;
            case 6:
                $day = "samedi";
                break;
            case 7:
                $day= "dimanche";
                break;
            default:
                break;
        }

        return $day;
    }


}
