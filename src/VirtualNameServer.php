<?php

namespace Tda\LaravelResellerinterface;

use Illuminate\Support\Collection;
use Tda\LaravelResellerinterface\Trait\Helper;

class VirtualNameServer
{
    use Helper;

    public int $vnsID;
    public string $soaMail;
    public array $hostname;
    public array $records;
    public array $validIPs;

    public static function __callStatic($method, $parameters)
    {
        switch($method) {
            case 'all':
            case 'create':
            case 'check':
                return (new Static)->$method(...$parameters);
        }
    }

    protected function all(): Collection
    {
        $response = $this->request( "vns/list", [], false);
        $virtualnameservers = new Collection();
        if($this->isSuccess($response['state'])) {
            foreach($response['list'] as $key=>$vns) {
                $virtualnameservers[$key] = (new VirtualNameServer())->setData($vns);
            }
        }
        return $virtualnameservers;
    }

    protected function create(string $soaMail, array $nameserver): self
    {
        $this->hostname = $nameserver;
        $response = $this->request( "vns/create", [
            'soaMail' => $soaMail,
            'nameserver' => $nameserver
          ] );
        if($this->isSuccess($response['state'])) {
            return $this->setData($response['vns']);
        } else {
            throw new \Exception("Error creating VNS");
        }
    }

    protected function check(array $nameserver): self
    {
        $this->hostname = $nameserver;
        $response = $this->request( "vns/check", [
            'nameserver' => $nameserver
          ] );
        if($this->isSuccess($response['state'])) {
            return $this->setData($response);
        } else {
            throw new \Exception("Error creating VNS");
        }
    }

    public function delete(): bool
    {
        $response = $this->request( "vns/delete", [
            'vnsID' => $this->vnsID,
          ] );
          if($this->isSuccess($response['state'])) {
            return true;
        } else {
            throw new \Exception("VNS not deleted");
        }
    }
}
