<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\StripeClient;

class StripeController extends Controller
{
    public function retrieveData($session){
        $stripe = new StripeClient('sk_test_51Oxs6xCJaEDmVxZfJP1rqSq68ClR2WkKbiZJiiM56nGx7WmsdMnGUtOot9Kpe1yq3hRLqPWQNaHjUfMP9HyEL6p900k5sErOeT');

        $paymentDetails = $stripe->checkout->sessions->retrieve(
            $session,
            []
        );

        return $paymentDetails['amount_total']/100;
    }
}
