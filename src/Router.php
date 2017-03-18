<?php
namespace Gearh\Route;

use Closure;

Class Router
{
    public $rules;

    protected $_handleClosure;

    public function add(Rule $rule)
    {
        if (isset($rule->name)) {
            $this->rules[$rule->name] = $rule;
        }else{
            $this->rules[] = $rule;
        }
        
        return true;
    }

    public function url($name, array $param = [])
    {
        $rule = $this->rules[$name];

        return call_user_func_array($rule->restore, [$param]);
    }

    public function middleware($middleware)
    {
        if (!($middleware instanceof Closure)) {
            $handleClosure = $this->_handleClosure;
            $middleware = $handleClosure($middleware, 'middleware');
        }

        foreach ($this->rules as $rule) {
            $next = $rule->to;
            $rule->to(function () use ($next, $middleware){
                $param = func_get_args();
                array_unshift($param, $next); 
                return call_user_func_array($middleware, $param);
            });
        }

        return true;
    }

    public function group(Closure $group, $middleware)
    {
        $router = new Router;
        $router->handleClosure($this->_handleClosure);
        $group($router);
        $router->middleware($middleware);
        foreach ($router->rules as $rule) {
            $this->add($rule);
        }

        unset($router);

        return true;
    }


    public function run($uri, $method, Closure $closure)
    {
        $to = null;
        $match = null;
        foreach ($this->rules as $rule) {
            $match = $rule->match($uri, $method);
            if ($match !== null) {
                $to = $rule->to;
                break;
            }

        }

        return $closure($to, $match);
    }

    public function handleClosure(Closure $closure)
    {
        $this->_handleClosure = $closure;

        return $this;
    }

    public function addRoute($method, $from, $to, $name = null)
    {
        if (!($to instanceof Closure)) {
            $handleClosure = $this->_handleClosure;
            $to = $handleClosure($to, 'controller');
        }

        $rule = (new Rule)
            ->name($name)
            ->from($from)
            ->to($to);

        if (in_array(strtolower($method), ['post', 'get'])) {
            $rule->method($method);
        }

        if ($name){
            $rule->name($name);
        }

        return $this->add($rule);
    }

}