<?php

namespace Tda\LaravelResellerinterface;
use Illuminate\Support\Collection;
use Tda\LaravelResellerinterface\Trait\Helper;

class Records
{
    use Helper;

    public int $id;
    public string $name;
    public int $ttl;
    public string $type;
    public bool $priority;
    public string $content;


    public function __construct(
        protected Resellerinterface $client = new Resellerinterface(),
        protected Domain $domain = new Domain()
    )
    {
    }

    public function list()
    {
        $response = $this->request( "listRecords", [
            'domain' => $this->domain->domain,
          ] );
        $records = new Collection();
        if($this->isSuccess($response['state'])) {
            foreach($response['records'] as $key=>$domain) {
                $records[$key] = (new Records($this->client, $this->domain))->setData($domain);
            }
        }
        return $records;
    }

    public function create(string $name, string $type, string $content, int $ttl = 86400): self
    {
        $response = $this->request( "createRecord", [
            'domain' => $this->domain->domain,
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => $ttl,
          ] );
        if($this->isSuccess($response['state'])) {
            return (new Records($this->client, $this->domain))->setData($response['record']);
        } else {
            throw new \Exception("Error creating record");
        }
    }

    public function update(string $name, string $type, string $content, int $ttl = 86400): self
    {
        $response = $this->request( "updateRecord", [
            'domain' => $this->domain->domain,
            'id' => $this->id,
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

    public function delete(): bool
    {
        $response = $this->request( "deleteRecord", [
            'domain' => $this->domain->domain,
            'id' => $this->id,
          ] );
          if($this->isSuccess($response['state'])) {
            return true;
        } else {
            throw new \Exception("Record not deleted");
        }
    }

    protected function request(string $type, array $params = [])
    {
        try {
            return $this->client->request( "dns/" . $type, $params);
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }
}
