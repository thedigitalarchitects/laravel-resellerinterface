<?php

namespace Tda\LaravelResellerinterface;


use Illuminate\Support\Collection;
use ReflectionObject;
use ReflectionProperty;
use Tda\LaravelResellerinterface\Resellerinterface;
use Tda\LaravelResellerinterface\Handle;
use Tda\LaravelResellerinterface\Trait\Helper;

class Domain
{
    use Helper;

    protected bool $isAvailable = false;
    public string $domain;
    public int $domainID;
    public string $domainAce;
    public string $tld;
    public string $tldAce;
    public string $state;
    public string $subState;
    public string $exoticState;
    public string $tag;
    public string $redirectMode;
    public string $redirectTarget;
    public string $mailMode;
    public string $mailTarget;
    public string $cancellationDate;
    public array $nameserver;
    public array $dnssec;
    public array $handles;
    public array $tldExotic;
    public array $tldInfo;
    protected array $additionalParams = [];

    protected array $required = ['domain', 'handles'];
    protected array $updatable = ['handles', 'tradeOK', 'nameserver', 'ensID', 'dnssec'];


    public function __construct(
        protected Resellerinterface $client = new Resellerinterface()
    )
    {
    }


    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function list()
    {
        $response = $this->request( "list");
        $domains = new Collection();
        if($this->isSuccess($response['state'])) {
            foreach($response['list'] as $domain) {
                $domains[] = (new Domain($this->client))->setData($domain);
            }
        }
        return $domains;
    }

    public function create(Handle $handle): self
    {
        if($this->isAvailable == false) {
            throw new \Exception('Domain not available');
        }
        if(!$handle->alias) {
            throw new \Exception('Handle not valid');
        }

        $data = [
            'domain' => $this->domain,
            'handles' => $handle->getOwnershipData(),
          ];
        if($this->additionalParams) {
            $data = array_merge($data, $this->additionalParams);
        }

        $response = $this->request('create', $data);
        if($this->isSuccess($response['state'])) {
            $this->isAvailable = false;
            return $this->setData($response['domain']);
        } else {
            throw new \Exception("Error creating domain");
        }

    }

    public function update(array $data = []): self
    {
        $this->validate($data, 'update');
        if($this->additionalParams) {
            $data = array_merge($data, $this->additionalParams);
        }
        if(empty($data)) {
            throw new \Exception('No data to update');
        }
        $data['domain'] = $this->domainID;
        $response = $this->request('update', $data);

        if($this->isSuccess($response['state'])) {
            return $this->details($this->domainID);
        } else {
            throw new \Exception("Error updating domain");
        }
    }

    public function updateNameservers()
    {
        $this->request('setNameserver', ['domain' => $this->domain, 'nameserver' => $this->additionalParams['nameserver']]);
        return $this->details($this->domainID);
    }

    public function updateHandles(Handle $handle)
    {
        $this->request('setHandles', ['domain' => $this->domain, 'handles' => $handle->getOwnershipData()]);
        return $this->details($this->domainID);
    }

    public function check(string $domain): self
    {
        $this->domain = $domain;
        $response = $this->request('check', ['domain' => $domain]);
        $status = $response['results'][0]['result'];
        switch($status) {
            case 'free':
                $this->isAvailable = true;
                break;
            case 'connect':
                $this->isAvailable = false;
                return $this->details($domain);
            default:
                throw new \Exception('Domain not valid');
        }

        return $this;
    }

    public function details(mixed $domain = null): self
    {
        $domain = $domain ?? $this->domain;
        if(empty($domain)) {
            throw new \Exception('Domain ID not valid');
        }
        $response = $this->request('details', ['domain' => $domain]);

        if($this->isSuccess($response['state'])) {
            $this->isAvailable = false;
            return $this->setData($response['domain']);
        } else {
            throw new \Exception("Error creating domain");
        }
    }

    public function additionalParams(array $additionalParams)
    {
        $this->additionalParams = $additionalParams;
    }

    public function addNameserver(string $nameserver, string $ipv4, ?string $ipv6 = null)
    {
        $this->additionalParams['nameserver'] = array_merge(
            $this->additionalParams['nameserver'] ?? array(),
                array([
                    'nameserver' => $nameserver,
                    'glueRecordIpv4' => $ipv4,
                    'glueRecordIpv6' => $ipv6
                ])
            );
    }

    public function addRecords(string $name, string $type, string $content)
    {
        $this->additionalParams['records'] = array_merge(
            $this->additionalParams['records'] ?? array(),
                array([
                    'name' => $name,
                    'type' => $type,
                    'content' => $content
                ])
            );
    }

    public function addDnssec(string $type, string $key, string $dnskeyFlag, int $dnskeyProtocol, int $dnskeyAlgorithm, string $dsTag, int $dsAlgorithm, string $dsHash)
    {
        $this->additionalParams['records'] = array_merge(
            $this->additionalParams['records'] ?? array(),
                array([
                    'type' => $type,
                    'key' => $key,
                    'dnskeyFlag' => $dnskeyFlag,
                    'dnskeyProtocol' => $dnskeyProtocol,
                    'dnskeyAlgorithm' => $dnskeyAlgorithm,
                    'dsTag' => $dsTag,
                    'dsAlgorithm' => $dsAlgorithm,
                    'dsHash' => $dsHash
                ])
            );
    }

    public function records()
    {
        return new Records($this->client, $this);
    }

    protected function request(string $type, array $params = [])
    {
        try {
            return $this->client->request( "domain/" . $type, $params);
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }
}


