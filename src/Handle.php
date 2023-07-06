<?php

namespace Tda\LaravelResellerinterface;

use Illuminate\Support\Collection;
use Tda\LaravelResellerinterface\Trait\Helper;

class Handle
{
    use Helper;

    public string $id;
    public string $alias;
    public string $state;
    public string $type;
    public string $company;
    public string $firstname;
    public string $lastname;
    public string $street;
    public string $city;
    public string $postcode;
    public string $country;
    public string $telephone;
    public string $fax;
    public string $email;
    public string $tag;
    public int $createdAt;
    public int $lastChanged;
    public int $usedBy;
    public bool $mainHandle = false;
    public array $additionalParams = [];
    private array $required = ['type', 'firstname', 'lastname', 'street', 'city', 'postcode', 'country', 'telephone', 'email', 'tag', 'additionalParams'];
    private array $updatable = ['postecode', 'city', 'street', 'telephone', 'fax', 'email', 'additionalParams'];


    public static function __callStatic($method, $parameters)
    {
        switch($method) {
            case 'list':
            case 'create':
            case 'find':
            case 'fields':
                return (new Static)->$method(...$parameters);
        }

    }

    protected function list(string $search = null, int $offset = 0, int $limit = 10): Collection
    {
        $params = array();
        if($search) {
            $params['search'] = $search;
        }
        if($offset) {
            $params['offset'] = $offset;
        }
        if($limit) {
            $params['limit'] = $limit;
        }
        $response = $this->request('handle/list', $params);
        $handles = new Collection();
        if($this->isSuccess($response['state'])) {
            foreach($response['list'] as $handle) {
                $handles->push((new Handle())->setData($handle));
            }
        }

        return $handles;
    }

    protected function create(array $data): object
    {
        $this->validate($data, 'create');
        $data = $this->setData($data);

        $response = $this->request('handle/create', $data->toArray());
        if($this->isSuccess($response['state'])) {
            $data->alias = $response['handleName'];
            return $this;
        } else {
            throw new \Exception("Error creating handle");
        }
    }

    protected function find(string $alias): object
    {
        $response = $this->request('handle/details', ['alias' => $alias]);
        if($this->isSuccess($response['state'])) {
            $this->setData($response['handle']);
            return $this;
        } else {
            throw new \Exception("Error finding handle");
        }
    }

    public function update(array $data): object
    {
        $this->validate($data, 'update');
        $data['alias'] = $this->alias;
        $response = $this->request('handle/update', $data);
        if($this->isSuccess($response['state'])) {
            $this->setData($data);
            return $this;
        } else {
            throw new \Exception("Error updating handle");
        }
    }

    public function deactivate(): object
    {
        $data['alias'] = $this->alias;
        $response = $this->request('handle/deactivate', $data);
        if($this->isSuccess($response['state'])) {
            $this->setData($data);
            return $this;
        } else {
            throw new \Exception("Error deleting handle");
        }
    }

    public function getOwnershipData(): array
    {
        return [
            'owner' => $this->alias,
            'admin' => $this->alias,
            'tech' => $this->alias,
            'zone' => $this->alias,
        ];
    }
}
