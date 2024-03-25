<?php

namespace App\Http\Services;

use GuzzleHttp\Client;

class DistanceMatrixService
{
    protected Client $client;
    protected string $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('GOOGLE_MAPS_API_KEY');
    }

    public function getDistance($origin, $destination)
    {
        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?' . http_build_query([
                'origins' => $origin,
                'destinations' => $destination,
                'key' => $this->apiKey,
            ]);

        $options = [
            'verify' => false,
        ];

        $response = $this->client->request('GET', $url, $options);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }
}
