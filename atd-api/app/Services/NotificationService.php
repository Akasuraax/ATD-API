<?php

namespace App\Services;

use GuzzleHttp\Client;
use onesignal\client\api\DefaultApi;
use onesignal\client\Configuration;
use onesignal\client\model\Notification;
use onesignal\client\model\StringMap;

class NotificationService
{
    private $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()
            ->setAppKeyToken(getenv('ONESIGNAL_REST_API_KEY')); // Remplacez par votre véritable App Key Token

        $guzzleConfig = [
            'verify' => false, // Désactive la vérification SSL
        ];

        $this->apiInstance = new DefaultApi(
            new Client($guzzleConfig),
            $config
        );
    }

    public function sendNotification($enContent)
    {
        $notification = $this->createNotification($enContent);

        try {
            $result = $this->apiInstance->createNotification($notification);
            return $result;
        } catch (\Exception $e) {
            // Gérez les exceptions ici
            throw $e; // Retournez l'exception pour une gestion ultérieure
        }
    }

    function createNotification($enContent): Notification {
        $content = new StringMap();
        $content->setEn($enContent);

        $notification = new Notification();
        $notification->setAppId(getenv('ONESIGNAL_APP_ID'));
        $notification->setContents($content);
        $notification->setIncludedSegments(['Total Subscriptions']);

        return $notification;
    }
}
