<?php

namespace App\Http\Controllers;
require 'vendor/autoload.php';

use Illuminate\Http\Request;
use FireBase\JWT\JWT;
use FireBase\JWT\Key;
use Spatie\Backtrace\Arguments\Reducers\StdClassArgumentReducer;

class TokenController extends Controller
{
    public function encodeToken(int $id) : string
    {
        $secKey = 'chipichipichapachapadubidubidabadaba';
        $payload = [
            'id' => $id,
            'time' => time() + 432000
        ];

        return JWT::encode($payload, $secKey, 'HS256');
    }

    public function decodeToken(string $token): \stdClass
    {
      return JWT::decode($token, new Key('chipichipichapachapadubidubidabadaba', 'HS256'));
    }
}
