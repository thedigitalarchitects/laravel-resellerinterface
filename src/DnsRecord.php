<?php

namespace Tda\LaravelResellerinterface;

use Illuminate\Support\Collection;
use Tda\LaravelResellerinterface\Trait\Helper;

class DnsRecord
{
    use Helper;

    public int $id;
    public string $name;
    public int $ttl;
    public string $type;
    public bool $priority;
    public string $content;


    public function __construct(
        protected Domain $domain = new Domain()
    )
    {
    }

    public function list(?string $search = null): Collection
    {
        $data = ['domain' => $this->domain->domain];
        if($search) {
            $data['search'] = $search;
        }
        $response = $this->request( "dns/listRecords", $data );
        $records = new Collection();
        if($this->isSuccess($response['state'])) {
            foreach($response['records'] as $key=>$record) {
                $records[$key] = (new DnsRecord( $this->domain))->setData($record);
            }
        }
        return $records;
    }

    public function create(string $name, string $type, string $content, int $ttl = 86400): self
    {
        $response = $this->request( "dns/createRecord", [
            'domain' => $this->domain->domain,
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => $ttl,
          ] );
        if($this->isSuccess($response['state'])) {
            return $this->setData($response['record']);
        } else {
            throw new \Exception("Error creating record");
        }
    }

    public function update(int $id, string $name, string $type, string $content, int $ttl = 86400): self
    {
        $response = $this->request( "dns/updateRecord", [
            'domain' => $this->domain->domain,
            'id' => $id,
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => $ttl,
          ] );
          if($this->isSuccess($response['state'])) {
            return $this->setData($response['record']);
        } else {
            throw new \Exception("Error creating record");
        }
    }

    public function delete(int $id): bool
    {
        $response = $this->request( "dns/deleteRecord", [
            'domain' => $this->domain->domain,
            'id' => $id,
          ] );
          if($this->isSuccess($response['state'])) {
            return true;
        } else {
            throw new \Exception("Record not deleted");
        }
    }
}
