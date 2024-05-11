<?php

namespace App\Console\Commands;

use App\Http\Controllers\JourneyController;
use App\Models\Activity;
use App\Models\Annexe;
use App\Models\Schedule;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanJourneyFromScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:plan-journey-from-schedule-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /*
    * 1 - créer une activitée en premier
    * 2 - créer le trajet avec googleAPI
    * 3 - annexe de départ : définie par l'admin en choisissant le véhicule, entrepôt d'arrivée faite selon la disponibilité
    * 4 - adresses récupérés sur les données du partenaires
    * */

    public function handle()
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
            'id_type' => 11
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
        $requestJourney= json_encode($journeyArray);
        $requestApi = new Request($journeyArray, $journeyArray, [], [], [], [], $requestJourney);

        $newJourney = app(JourneyController::class)->createJourney($requestApi);
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
    }
}
