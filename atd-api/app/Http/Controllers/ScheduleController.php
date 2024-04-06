<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Annexe;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function createSchedule(int $id, Request $request){
        try {
            $fields = $request->validate([
                'schedule.day' => 'required|int',
                'schedule.start_hour' => 'required|date_format:H:i|required',
                'schedule.end_hour' => 'required|date_format:H:i|required'
            ]);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $defaultDate = date('Y-m-d');
        $start_hour = $defaultDate . ' ' . $fields['schedule']['start_hour'];
        $end_hour = $defaultDate . ' ' . $fields['schedule']['end_hour'];

        if($fields['schedule']['day'] < 1 || $fields['schedule']['day'] > 7){
            return response()->json([
                'message' => 'chose between monday and sunday'
            ], 404);
        }

        if(!empty(Schedule::where('day', $fields['schedule']['day'])->where('user_id', $id)->first()))
            return  response()->json(['message' => 'you already created a schedule for this day'], 409);

        $schedule = Schedule::create([
            'day' => $fields['schedule']['day'],
            'start_hour' => $start_hour,
            'end_hour' => $end_hour,
            'user_id' => $id
        ]);

        $day = $this->getDayFromId($fields['schedule']['day']);

        return response()->json([
            'schedule' => [
                'day' => $day,
                'start_hour' => $fields['schedule']['start_hour'],
                'end_hour' => $fields['schedule']['end_hour'],
                'user' => $schedule->user
            ]
        ], 201);
    }

    public function updateSchedule(int $userId, Request $request, int $scheduleDay){
        try {
            $schedule = Schedule::where('user_id', $userId)->where('day', $scheduleDay)->first();

            if(empty($schedule))
                return response()->json(['message' => 'Not found'], 404);

            $fields = $request->validate([
                'schedule.start_hour' => 'required|date_format:H:i|required',
                'schedule.end_hour' => 'required|date_format:H:i|required',
                'schedule.checking' => 'required|boolean'
            ]);
        }catch (ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        $defaultDate = date('Y-m-d');
        $start_hour = $defaultDate . ' ' . $fields['schedule']['start_hour'];
        $end_hour = $defaultDate . ' ' . $fields['schedule']['end_hour'];

        $fields['schedule']['end_hour'] = $end_hour;
        $fields['schedule']['start_hour'] = $start_hour;

        try{
            $schedule->start_hour = $start_hour;
            $schedule->end_hour = $end_hour;
            $schedule->checking = $fields['schedule']['checking'];

            $schedule->save();
        }catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'The element you selected is not found'], 404);
        }

        $day = $this->getDayFromId($scheduleDay);

        return response()->json([
            'schedule' => [
                'day' =>  $day,
                'start_hour' => $fields['schedule']['start_hour'],
                'end_hour' => $fields['schedule']['end_hour'],
                'user' => $schedule->user
            ]], 200);
    }

    /*
     * 1 - créer une activitée en premier
     * 2 - créer le trajet avec googleAPI
     * 3 - annexe de départ : définie par l'admin en choisissant le véhicule, entrepôt d'arrivée faite selon la disponibilité
     * 4 - adresses récupérés sur les données du partenaires
     * */

    public function planJourneyFromSchedule(Request $request){
        //1 - création de l'activité
        $vehicle = Vehicle::findOrFail($request['vehicle']['id']);
        $annexe = Annexe::findOrFail($vehicle->id_annexe);
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

        //2 - création du trajet
        $steps = [$annexe['address'] . ', ' . $annexe['zipcode']];

        $warehouseAdress = $this->getMostAvailableWarehouse();
        $usersAddress = $this->getAllUsersAddressFromDayId($tomorrowsId);
        $steps = array_merge($steps, $usersAddress);

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
            'vehicle' => ["id" => $request['vehicle']['id']]
        ];

        $requestJourney = Request::create('/journey', 'POST', $journeyArray);
        $requestJourney->content = json_encode($journeyArray);

        $newJourney = app(JourneyController::class)->createJourney($requestJourney);

        return $newJourney;
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
        $schedules = Schedule::where('day', $dayId)->with('user:id,email,address,zipcode')->get();

        $addrressesArray = [];

        foreach($schedules as $usersAdress){
            $addrressesArray[] = $usersAdress['user']['address'] . ', ' . $usersAdress['user']['zipcode'];
        }

        return $addrressesArray;
    }
}
