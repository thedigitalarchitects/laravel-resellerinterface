<?php

 namespace Tda\LaravelResellerinterface\Trait;
 use ReflectionObject;
 use ReflectionProperty;
 use ReflectionMethod;

 Trait Helper
 {

    function __call($func, $params){
        $attributes = (new ReflectionObject(new Static()))->getProperties(ReflectionProperty::IS_PUBLIC);
        if( in_array($func, array_column($attributes, 'name')) ){
            return self::$$func;
        }
    }

    protected static function setData(array $data): self
    {
        $attributes = (new ReflectionObject(new Static()))->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach($attributes as $attribute) {
            if(isset($data[$attribute->name])) {
                $attribute->setValue(new Static(), $data[$attribute->name]);
            }
        }

        return new self();
    }

    protected static function validate(array $data, string $method = 'create')
    {
        if($method == 'create') {
            self::checkRequired($data);
        }
        if($method == 'update') {
            self::checkUpdatable($data);
        }
    }

    protected static function checkRequired(array $data)
    {
        $required = [];
        foreach($data as $key=>$value) {
            if(in_array($key, self::$required) && empty($value)) {
                $required[] = $key;
            }
        }
        if($required) {
            throw new \Exception("Missing required parameter: " . json_encode($required));
        }
    }

    protected static function checkUpdatable(array $data)
    {
        $updatable = [];
        foreach($data as $key=>$value) {
            if(!in_array($key, self::$updatable)) {
                $updatable[] = $key;
            }
        }
        if($updatable) {
            throw new \Exception("Parameter cannot be modified: " . json_encode($updatable));
        }
    }

    public static function toArray(): array
    {
        $attributes = (new ReflectionObject(new Static()))->getProperties(ReflectionProperty::IS_PUBLIC);
        $data = [];
        foreach($attributes as $attribute) {
            if($attribute->isInitialized(new Static())) {
                $data[$attribute->name] = $attribute->getValue(new Static());
            }
        }
        return $data;
    }

    public static function toJson(): string
    {
        return json_encode(self::toArray());
    }

    public static function toObject(): object
    {
        return (object) self::toArray();
    }

    protected static function isSuccess(int $status): bool
    {
        if ($status >= 1000 && $status <= 1999) {
            return true;
        }
        return false;
    }

    protected static function isFail(int $status): bool
    {
        if ($status >= 2000) {
            return true;
        }
        return false;
    }

 }
