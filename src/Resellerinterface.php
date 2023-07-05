<?php

namespace Tda\LaravelResellerinterface;


use Illuminate\Support\Collection;
use ResellerInterface\Api\Client;
use Tda\LaravelResellerinterface\Trait\Helper;


class Resellerinterface
{
    use Helper;

    protected static Client $client;
    public static int $resellerId;
    protected static string $username;
    protected static string $password;
    protected static bool $isStaging = false;
    const BASE_STAGING_URL = 'https://core-staging.resellerinterface.de/';
    const CONFIG_LOCATION = 'services.resellerinterface';

    public array $errors = [];


    public static function init()
    {
        self::configLocation();
        try {
            if(self::$isStaging) {
                self::$client = new Client(self::BASE_STAGING_URL);
            } else {
                self::$client = new Client();
            }
            self::$client->login( self::$username, self::$password, self::$resellerId );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function staging()
    {
        self::$isStaging = true;
    }

    public static function config(string $username, string $password, int $resellerId)
    {
        self::$username = $username;
        self::$password = $password;
        self::$resellerId = $resellerId;
    }

    public static function configLocation(?string $configLocation = null)
    {
        if(!$configLocation) {
            $configLocation = self::CONFIG_LOCATION;
        }
        self::config(
            config($configLocation . '.username'),
            config($configLocation . '.password'),
            config($configLocation . '.resellerId')
        );
    }

    public static function getClient()
    {
        return self::$client;
    }

}
