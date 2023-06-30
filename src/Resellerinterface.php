<?php

namespace Tda\LaravelResellerinterface;


use ResellerInterface\Api\Client;


class Resellerinterface
{

    protected Client $client;
    protected int $resellerId;
    const BASE_URL = 'https://core-staging.resellerinterface.de/';


    public function __construct(?string $username = null, ?string $password = null, ?int $resellerId = null)
    {
        if (!$username) {
            $username = $username ?? config('resellerinterface.username');
        }
        if(!$password){
            $password = $password ?? config('resellerinterface.password');
        }
        if(!$resellerId){
            $this->resellerId = $resellerId ?? config('resellerinterface.resellerId');
        }

        dd($username, $password, $this->resellerId);

        $this->client = new Client(self::BASE_URL);
        try {
            $this->client->login( $username, $password, $this->resellerId );
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    public function reseller() {
        $response = $this->client->request( "reseller/details", ['resellerID' => $this->resellerId]);
        return ($response->getData());
    }





}
