<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;

class DonationController extends Controller
{
    public string $public_key = "sk_test_51Oxs6xCJaEDmVxZfJP1rqSq68ClR2WkKbiZJiiM56nGx7WmsdMnGUtOot9Kpe1yq3hRLqPWQNaHjUfMP9HyEL6p900k5sErOeT";

    public function savePayment(Request $request){
        $stripe = new StripeClient($this->public_key);

        try{
            $validateData = $request->validate([
                'cs_id' => 'required','string', Rule::unique('donations', 'checkout_session')
            ]);
        }catch(ValidationException $e){
            return response()->json(['errors' => $e->errors()], 422);
        }

        if(!$request->header('Authorization'))
            $idDonator = 1;
        else
            $idDonator = TokenController::decodeToken($request->header('Authorization'))->id;

        $amount = $stripe->checkout->sessions->retrieve(
            $validateData['cs_id'],
            []
        );

        $donation = Donation::create([
            'amount' => $amount['amount_total']/100,
            'user_id' => $idDonator,
            'checkout_session' => $validateData['cs_id']
        ]);

        return Response(['donation' => $donation], 200);
    }

    public function retrieveData($session){
        $stripe = new StripeClient($this->public_key);

        $paymentDetails = $stripe->checkout->sessions->retrieve(
            $session,
            []
        );

        return $paymentDetails['amount_total']/100;
    }
}
