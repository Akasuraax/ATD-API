<?php

namespace App\Http\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;

class AddressService
{

    protected Client $client;
    protected string $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('GOOGLE_MAPS_API_KEY');
    }

    /**
     * @throws GuzzleException
     */
    public function address($value)
    {

            $url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?' . http_build_query([
                'input' => $value,
                'key' => $this->apiKey,
                'types' => '(cities)',
            ]);

        $options = [
            'verify' => false,
        ];
        $response = $this->client->request('GET', $url, $options);
        $data = json_decode($response->getBody()->getContents(), true);
        return $data;
    }

}
