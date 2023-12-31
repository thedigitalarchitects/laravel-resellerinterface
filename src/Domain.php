<?php

namespace Tda\LaravelResellerinterface;

use Illuminate\Support\Collection;
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
    public array $prices;
    public string $authcode = '';
    public float $oneTimePrice = 0;
    protected array $additionalParams = [];

    protected array $required = ['domain', 'handles'];
    protected array $updatable = ['handles', 'tradeOK', 'nameserver', 'ensID', 'dnssec'];


    public function __construct(?string $domainame = null)
    {
        if($domainame) {
            $this->domain = $domainame;
        }
    }


    public static function __callStatic($method, $parameters)
    {
        switch($method) {
            case 'list':
            case 'check':
            case 'findIds':
            case 'info':
            case 'fields':
            case 'listTransfer':
                return (new Static)->$method(...$parameters);
        }
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    protected function list(array $data = []): Collection
    {
        $response = $this->request("domain/list", $data);
        $domains = new Collection();
        if($this->isSuccess($response['state'])) {
            foreach($response['list'] as $domain) {
                $domains[] = (new Domain())->setData($domain);
            }
        }
        return $domains;
    }

    protected function findIds(array $domainIDs): Collection
    {
        $data['search']['domainID'] = $domainIDs;

        return $this->list($data);
    }

    protected function listTransfer(array $data = [])
    {
        $response = $this->request('domain/listTransferRequests', $data);

        if($this->isSuccess($response['state'])) {
            $this->isAvailable = false;
            return ($response['list']);
        } else {
            throw new \Exception("Error transfering domain");
        }
    }

    public function transfer(Handle $handle, string $authCode): self
    {
        if(!$this->domain) {
            throw new \Exception('Must have a domain name');
        }
        $data = [
            'domain' => $this->domain,
            'authcode' => $authCode,
            'handles' => $handle->getOwnershipData(),
          ];

          if($this->additionalParams) {
            $data = array_merge($data, $this->additionalParams);
        }

        $response = $this->request('domain/transfer', $data);
        if($this->isSuccess($response['state'])) {
            return $this->setData($response['domain']);
        } else {
            throw new \Exception("Error transfering domain");
        }
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

        $response = $this->request('domain/create', $data);
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
        $response = $this->request('domain/update', $data);

        if($this->isSuccess($response['state'])) {
            return $this->details($this->domainID);
        } else {
            throw new \Exception("Error updating domain");
        }
    }

    public function delete(array $data = []): self
    {
        $data['domain'] = $this->domain;
        $response = $this->request('domain/delete', $data);

        if($this->isSuccess($response['state'])) {
            return $this->setData($response['domain']);
        } else {
            throw new \Exception("Error updating domain");
        }
    }

    public function undelete(): self
    {
        $data['domain'] = $this->domain;
        $response = $this->request('domain/undelete', $data);

        if($this->isSuccess($response['state'])) {
            return $this->setData($response['domain']);
        } else {
            throw new \Exception("Error updating domain");
        }
    }

    public function restore(): self
    {
        $data['domain'] = $this->domain;
        $response = $this->request('domain/restore', $data);

        if($this->isSuccess($response['state'])) {
            return $this;
        } else {
            throw new \Exception("Error updating domain");
        }
    }

    public function setNameserver(array $nameserver)
    {
        foreach($nameserver as $vns) {
            list($name, $ipv4, $ipv6) = $vns;
            $this->addNameserver($name, $ipv4, $ipv6);
        }
        $this->request('domain/setNameserver', ['domain' => $this->domain, 'nameserver' => $this->additionalParams['nameserver']]);
        return $this->details();
    }

    public function setHandles(Handle $handle)
    {
        $this->request('domain/setHandles', ['domain' => $this->domain, 'handles' => $handle->getOwnershipData()]);
        return $this->details();
    }

    protected function check(string $domain): self
    {
        $this->domain = $domain;
        $response = $this->request('domain/check', [ 'domain' => $domain], false);
        $status = $response['results'][0]['result'];
        switch($status) {
            case 'free':
                $this->isAvailable = true;
                $this->getPrice();
                break;
            case 'connect':
                $this->isAvailable = false;
                break;
                //return $this->details($domain);
            default:
                throw new \Exception('Domain not valid');
        }

        return $this;
    }

    protected function info(mixed $domain): self
    {
        if(empty($domain)) {
            throw new \Exception('Domain ID not valid');
        }
        $response = $this->request('domain/details', ['domain' => $domain]);
        if($this->isSuccess($response['state'])) {
            $this->isAvailable = false;
            return $this->setData($response['domain']);
        } else {
            throw new \Exception("Error creating domain");
        }
    }

    public function authCode(): string
    {
        $response = $this->request('domain/showAuthcode', [ 'domain' => $this->domain], false);
        if($this->isSuccess($response['state'])) {
            return $this->authcode = $response['authcode'];
        }

        return $this->authcode;
    }

    public function details(mixed $domain = null): self
    {
        $domain = $domain ?? $this->domain;
        return $this->info($domain);
    }

    public function getTld(): string
    {
        $aux = (explode('.', $this->domain));
        return end($aux);
    }

    public function getPrice()
    {
        $response = $this->request('prices/domains', ['search' => ['tld' => $this->getTld()]], false);
        if($this->isSuccess($response['state'])) {
            $this->prices = $response['list'][0]['products'];
            $this->oneTimePrice = $this->prices['authinfo2']['prices']['total'];
        } else {
            throw new \Exception("Error getting price");
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
        return new DnsRecord($this);
    }

}


