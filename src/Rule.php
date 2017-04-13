<?php
namespace Gearh\Route;

use Closure;

/**
 * Rule for Ruter
 */
Class Rule
{
    /**
     * match regular
     * 
     * @var string
     */
    public $regular;

    /**
     * after a successful match the target
     * 
     * @var String|Closure
     */
    public $to;

    /**
     * the name needed to restore the url
     * 
     * @var string
     */
    public $name;

    /**
     * access method
     * 
     * @var stirng
     */
    public $method;

    /**
     * the Closure to restore the url
     * 
     * @var Closure
     */
    public $restore;
 
    /**
     * set regular
     * 
     * @param  string $regular
     * @return Rule
     */
    public function regular($regular)
    {
        $this->regular = $regular;

        return $this;
    }

    /**
     * set to
     * 
     * @param  miexd $to
     * @return Rule
     */
    public function to($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * set method
     * 
     * @param  stirng $method
     * @return Rule
     */
    public function method($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * set restore
     * 
     * @param  Closure $restore
     * @return Rule
     */
    public function restore(Closure $restore)
    {
        $this->restore = $restore;

        return $this;
    }

    /**
     * set name
     * 
     * @param  stirng $name
     * @return Rule
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * match a url & method
     * 
     * @param  stirng $path
     * @param  stirng $method
     * @return bool
     */
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

    /**
     * easy way set regular & regular
     * 
     * @param  string $pattern
     * @param  array  $paramForm
     * @return Rule
     */
    public function from($pattern, $paramForm = [])
    {
        # generate restore
        $this->restore = function ($param) use ($pattern){ 

            # Converts the passed arguments into the form ': key' => 'value'
            $newParam = [];
            foreach ($param as $key => $value) {
                $newKey = ':' . $key;
                $newParam[$newKey] = $value;
            }

            # Replace placeholders with parameters
            $url = str_replace(array_keys($newParam), $newParam, $pattern);
            $url = preg_replace('/\([^()]+:[^()]+\)/', '', $url); # remove empty placeholders in url
            $url = str_replace('(', '', $url); # remove (
            $url = str_replace(')', '', $url); # remove )
            $url = "/" . $url;

            return $url;
        };

        # Replace placeholders with regular 
        $matches = [];
        preg_match_all('/:(?<name>\w+)/', $pattern, $matches);
        $newParamForm = [];
        foreach ($matches['name'] as $key) {
            if (isset($paramForm[$key])) {
                $value = $paramForm[$key];
                $newParamForm[':' . $key] = "(?<{$key}>{$value}+)";
            }else{
                # default use \w+
                $newParamForm[':' . $key] = "(?<{$key}>\w+)";
            }

        }

        $paramForm = $newParamForm;
        
        # Replace '/' with '\/', and add head,tail
        $pattern = '/^\/' . str_replace('/', '\/', $pattern) . '$/';
        $pattern = str_replace('(', '(|', $pattern); # Replace "( )" with choose grammar
        $this->regular = str_replace(array_keys($paramForm), $paramForm, $pattern);

        return $this;
    }

    /**
     * get all item in array key is string
     * 
     * @param  array $array
     * @return array
     */
    protected function _getStrKeyItem($array)
    {
        $param = [];
        foreach ($array as $key => $value) {
            if (!is_numeric($key) AND $value[0]) $param[$key] = $value[0];
        }

        return $param;
    }
}