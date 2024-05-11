<?php

namespace App\Http\Controllers;


use App\Models\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\JourneyController;
use App\Models\Activity;
use App\Models\Annexe;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
            if (!empty($fields['schedule'])) {
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
                            'start_hour' => $startHour,
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
            }

            Schedule::where('user_id', $userId)
                ->whereNotIn('day', array_column($updatedSchedules, 'day'))
                ->delete();


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

    /*
    public function planJourneyFromSchedule()
    {
        //1 - création de l'activité
        $vehicle = Vehicle::where('partner', true)->first();
        if(!$vehicle) {
            Log::error('Aucun véhicule sélectionné.');
            return response()->json(['message' => 'Aucun véhicule séléctionné'], 404);
        }

        $annexe = Annexe::findOrFail($vehicle["id_annexe"]);

        $tomorrowsId = $this->getDayId(Carbon::now()->addDay()->format('l'));

        $activity = Activity::create([
            'title' => "Récolte des produits",
            'description' => "Création journalière de récolte des produits partenaires",
            'address' => $annexe["address"],
            'zipcode' => $annexe["zipcode"],
            'start_date' => Carbon::now()->addDay()->setTime(8,0,0),
            'end_date' => Carbon::now()->addDay()->setTime(20, 0, 0),
            'donation' => null,
            'id_type' => 10
        ]);

        $activity->roles()->attach(7, ['archive' => false, 'min' => 1, 'max' => 1, 'count' => 0]);

        //2 - création du trajet
        $steps = [$annexe['address'] . ', ' . $annexe['zipcode']];

        $warehouseAdress = $this->getMostAvailableWarehouse();
        $usersAddress = $this->getAllUsersAddressFromDayId($tomorrowsId);
        $steps = array_merge($steps, $usersAddress);

        if(empty($steps)){
            Log::error('Aucun horaire disponible.');
            return response()->json(['message' => 'Aucun horaire disponible'], 404);
        }

        //envoie des données à l'api google
        $jsonSteps = json_encode(['steps' => $steps]);
        $requestApi = new Request([], [], [], [], [], [], $jsonSteps);
        $sortedSteps = app(JourneyController::class)->callGoogleApi($requestApi);

        //je décode le json envoyé pour ajouter l'entrepot à la fin
        $array = json_decode($sortedSteps->getContent(), true);
        $json_string = trim($array["steps"], '"');
        $json_string = str_replace("'", '"', $json_string);
        $steps = json_decode($json_string);
        $steps[] = $warehouseAdress;

        //je le remet au bon format pour créer journey
        $stepsString = "['" . implode("', '", $steps) . "']";
        $journeyArray = [
            'journey' => ["name" => "Recolte des produits partenaires"],
            'activity' => ["id" => $activity->id],
            'steps' => $stepsString,
            'vehicle' => ["id" => $vehicle->id]
        ];

        $requestJourney = Request::create('/journey', 'POST', $journeyArray);
        $requestJourney->content = json_encode($journeyArray);

        $newJourney = app(JourneyController::class)->createJourney($requestJourney);
    }

    public function getDayId($day){
        switch(strtolower($day)){
            case 'lundi':case 'monday':
            return 1;

            case 'mardi':case 'tuesday':
            return 2;

            case 'mercredi':case 'wednesday':
            return 3;

            case 'jeudi':case 'thursday':
            return 4;

            case 'vendredi':case 'friday':
            return 5;

            case 'samedi':case 'saturday':
            return 6;

            case 'dimanche':case 'sunday':
            return 7;
        }
    }

    //permet de récupérer l'entrepot avec le + de place disponible
    public function getMostAvailableWarehouse(){
        $warehouses = Warehouse::all();
        $highestAvailability = 0;
        $highestAvailabilityId = 1;
        foreach($warehouses as $warehouse){
            $capacity = $warehouse->capacity;
            $pieces = $warehouse->pieces;
            foreach($pieces as $piece){
                $capacity -= $piece->count;
            }

            if($highestAvailability < $capacity) {
                $highestAvailability = $capacity;
                $highestAvailabilityId = $warehouse->id;
            }
        }
        $warehouse = Warehouse::findOrFail($highestAvailabilityId);

        return $warehouse['address'] . ', ' . $warehouse['zipcode'];
    }

    public function getAllUsersAddressFromDayId($dayId){
        $schedules = Schedule::where('day', $dayId)->where('checking', true)->with('user:id,email,address,zipcode')->get();

        $addrressesArray = [];

        foreach($schedules as $usersAdress){
            $addrressesArray[] = $usersAdress['user']['address'] . ', ' . $usersAdress['user']['zipcode'];
        }

        return $addrressesArray;
    }*/

}
