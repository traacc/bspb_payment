<?php

namespace BspbSDK\Entity\Request;

abstract class AbstractRequestEntity
{
    protected static $getParametersList = [];

    protected function isInGetParameters(string $parameterName): bool
    {
        return !(array_search($parameterName, static::$getParametersList) === false);
    }

    public function getAllFields(): array
    {
        $properties = get_class_vars(get_class($this));
        $result = [];
        foreach ($properties as $propertyName => $propertyValue) {
            if ($this->isInGetParameters($propertyName)) continue;
            $result[$propertyName] = $this->getPropertyValue($propertyName);
            if ($result[$propertyName] === null) unset($result[$propertyName]);
        }
        return $result;
    }

    public function fillUrlParams(string $url): string
    {
        foreach (static::$getParametersList as $propertyName) {
            $propertyValue = $this->getPropertyValue($propertyName);
            if ($propertyValue === null) continue;
            $url = preg_replace("/{{$propertyName}}/", $propertyValue, $url);
        }
        return $url;
    }

    protected function getPropertyValue($propertyName)
    {
        $result = null;
        $method = 'get' . ucfirst($propertyName);
        //if method get boolean value...
        $methodBool = 'is' . ucfirst($propertyName);
        if (method_exists($this, $method) && is_callable([$this, $method])) {
            $value = $this->{$method}();
            if ($value instanceof self) $value = $value->getAllFields();
            if ($value instanceof AbstractRequestEntityCollection) {
                $value = $value->getAllFields();
            }
            $result = $value;
        }
        if (method_exists($this, $methodBool) && is_callable([$this, $methodBool])) {
            $result = $this->{$methodBool}();
        }
        return $result;
    }

}
