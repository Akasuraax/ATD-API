<?php

namespace App\Http\Controllers;

use App\Http\Services\AddressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AddressController extends Controller
{

    protected AddressService $addressService;

    public function __construct()
    {
        $this->addressService = new AddressService();
    }
    public function address(Request $request) {
        $input = $request->input('input');

            $res = $this->addressService->address($input);
            return response()->json(['data' => $res]);

    }

}
