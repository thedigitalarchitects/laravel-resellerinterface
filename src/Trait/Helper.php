<?php

 namespace Tda\LaravelResellerinterface\Trait;

 use Tda\LaravelResellerinterface\Resellerinterface;
 use ReflectionObject;
 use ReflectionProperty;

 Trait Helper
 {

    function __call($func, $params){
        $attributes = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);
        if( in_array($func, array_column($attributes, 'name')) ){
            return $this->$func;
        }
    }

    protected function fields()
    {
        $attributes = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);
        return array_column($attributes, 'name');
    }

    protected function setData(array $data): self
    {
        $attributes = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach($attributes as $attribute) {
            if(isset($data[$attribute->name])) {
                $attribute->setValue($this, $data[$attribute->name]);
            }
        }

        return $this;
    }

    protected function validate(array $data, string $method = 'create')
    {
        if($method == 'create') {
            $this->checkRequired($data);
        }
        if($method == 'update') {
            $this->checkUpdatable($data);
        }
    }

    protected function checkRequired(array $data)
    {
        $required = [];
        foreach($data as $key=>$value) {
            if(in_array($key, $this->required) && empty($value)) {
                $required[] = $key;
            }
        }
        if($required) {
            throw new \Exception("Missing required parameter: " . json_encode($required));
        }
    }

    protected function checkUpdatable(array $data)
    {
        $updatable = [];
        foreach($data as $key=>$value) {
            if(!in_array($key, $this->updatable)) {
                $updatable[] = $key;
            }
        }
        if($updatable) {
            throw new \Exception("Parameter cannot be modified: " . json_encode($updatable));
        }
    }

    public function toArray(): array
    {
        $attributes = (new ReflectionObject($this))->getProperties(ReflectionProperty::IS_PUBLIC);
        $data = [];
        foreach($attributes as $attribute) {
            if($attribute->isInitialized($this)) {
                $data[$attribute->name] = $attribute->getValue($this);
            }
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function toObject(): object
    {
        return (object) $this->toArray();
    }

    protected function isSuccess(int $status): bool
    {
        if ($status >= 1000 && $status <= 1999) {
            return true;
        }
        return false;
    }

    protected function isFail(int $status): bool
    {
        if ($status >= 2000) {
            return true;
        }
        return false;
    }

    protected function request(string $endpoint, array $data = [], bool $withResellerID = true)
    {
        Resellerinterface::init();
        if($withResellerID) {
            $data['resellerID'] = $data['resellerID'] ?? Resellerinterface::getResellerId();
        }
        $response = Resellerinterface::getClient()->request($endpoint, $data);
        if(!$response->isError()) {
            return ($response->getData());
        } else {
            throw new \Exception('Bad request: parameters not valid: ' . json_encode($response->getErrors()));
        }
    }

 }
