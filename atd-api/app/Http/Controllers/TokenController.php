<?php

namespace App\Http\Controllers;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
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
