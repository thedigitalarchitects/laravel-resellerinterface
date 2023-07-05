<?php

namespace Tda\LaravelResellerinterface;

use Illuminate\Support\Collection;
use Tda\LaravelResellerinterface\Resellerinterface;
use Tda\LaravelResellerinterface\Trait\Helper;


class Handle
{
    use Helper;

    public static string $id;
    public static string $alias;
    public static string $state;
    public static string $type;
    public static string $company;
    public static string $firstname;
    public static string $lastname;
    public static string $street;
    public static string $city;
    public static string $postcode;
    public static string $country;
    public static string $telephone;
    public static string $fax;
    public static string $email;
    public static string $tag;
    public static int $createdAt;
    public static int $lastChanged;
    public static int $usedBy;
    public static bool $mainHandle = false;
    public static array $additionalParams = [];
    private static array $required = ['type', 'firstname', 'lastname', 'street', 'city', 'postcode', 'country', 'telephone', 'email', 'tag', 'additionalParams'];
    private static array $updatable = ['postecode', 'city', 'street', 'telephone', 'fax', 'email', 'additionalParams'];



    public static function list(string $search = null, int $offset = 0, int $limit = 10): Collection
    {
        $response = self::request('list');
        $handles = new Collection();
        if(self::isSuccess($response['state'])) {
            foreach($response['list'] as $handle) {
                print $handle['alias'] . "<br>";
                $handles->push((new Handle())->setData($handle));
            }
        }

        return $handles;
    }

    public static function create(array $data): object
    {
        self::validate($data, 'create');
        $data = self::setData($data);

        $response = self::request('create', $data->toArray());
        if(self::isSuccess($response['state'])) {
            $data->alias = $response['handleName'];
            return new Static();
        } else {
            throw new \Exception("Error creating handle");
        }
    }

    public static function find(string $alias): object
    {
        $response = self::request('details', ['alias' => $alias]);
        if(self::isSuccess($response['state'])) {
            self::setData($response['handle']);
            return new Static();
        } else {
            throw new \Exception("Error finding handle");
        }
    }

    public static function update(array $data): object
    {
        self::validate($data, 'update');
        $data['alias'] = self::$alias;
        $response = self::request('update', $data);
        if(self::isSuccess($response['state'])) {
            self::setData($data);
            return new Static();
        } else {
            throw new \Exception("Error updating handle");
        }
    }

    public static function getOwnershipData(): array
    {
        return [
            'owner' => self::$alias,
            'admin' => self::$alias,
            'tech' => self::$alias,
            'zone' => self::$alias,
        ];
    }

    protected static function request(string $type, array $params = [])
    {
        try {
            return Resellerinterface::request( "handle/" . $type, $params);
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }
}
