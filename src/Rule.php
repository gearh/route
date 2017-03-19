<?php
namespace Gearh\Route;

use Closure;

Class Rule
{
    public $regular;

    public $to;

    public $name;

    public $method;

    public $restore;
 
    public function regular($regular)
    {
        $this->regular = $regular;

        return $this;
    }

    public function to(Closure $to)
    {
        $this->to = $to;

        return $this;
    }

    public function method($method)
    {
        $this->method = $method;

        return $this;
    }

    public function restore(Closure $restore)
    {
        $this->restore = $restore;

        return $this;
    }

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    public function match($path, $method)
    {
        if ($this->method AND strtolower($this->method) !== strtolower($method)) {
            return null;
        }

        $matches = [];
        $return = preg_match_all($this->regular, $path, $matches);
        if ($return) {
            return $this->_getStrKeyItem($matches);
        }

        return null;
    }

    # easy way declare regular and regular
    public function from($pattern, $paramForm = [])
    {
        $matches = [];
        preg_match_all('/:(?<name>\w+)/', $pattern, $matches);
        $newParamForm = [];
        foreach ($matches['name'] as $key) {

            if (isset($paramForm[$key])) {
                $value = $paramForm[$key];
                $newParamForm[':' . $key] = "(?<{$key}>{$value}+)";
            }else{
                $newParamForm[':' . $key] = "(?<{$key}>\w+)";
            }

        }

        $paramForm = $newParamForm;
        $this->restore = function ($param) use ($pattern, $paramForm){ 
            $newParam = [];
            foreach ($param as $key => $value) {
                $newKey = ':' . $key;
                $newParam[$newKey] = $value;
            }

            $url = str_replace(array_keys($newParam), $newParam, $pattern);
            $url = preg_replace('/\(.*:.*\)/', '', $url);
            $url = str_replace('(', '', $url);
            $url = str_replace(')', '', $url);
            $url = "/" . $url;

            return $url;
        };
        
        $pattern = '/\/' . str_replace('/', '\/', $pattern) . '$/';
        $pattern = str_replace('(', '(|', $pattern);
        $this->regular = str_replace(array_keys($paramForm), $paramForm, $pattern);

        return $this;
    }

    # get all item in array key is string
    protected function _getStrKeyItem($array)
    {
        $param = [];
        foreach ($array as $key => $value) {
            if (!is_numeric($key) AND $value[0]) $param[$key] = $value[0];
        }

        return $param;
    }
}