<?php
namespace Gearh\Route;

use Closure;

Class Router
{
    public $rules;

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

    public function middleware(Closure $middleware)
    {
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

    public function group(Closure $group, Closure $middleware)
    {
        $router = new Router;
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
            if ($match == null) continue;

            $to = $rule->to;
        }

        return $closure($to, $match);
    }

}