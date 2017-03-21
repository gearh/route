<?php
namespace Gearh\Route;

use Closure;

/**
 * Router
 */
Class Router
{
    /**
     * all rule
     * @var array
     */
    public $rules;

    /**
     * Closure to proc rule::to
     * @var Closure
     */
    protected $_procClosure;

    /**
     * add a rule
     * 
     * @param Rule $rule
     */
    public function add(Rule $rule)
    {
        if (isset($rule->name)) {
            $this->rules[$rule->name] = $rule;
        }else{
            $this->rules[] = $rule;
        }
        
        return true;
    }

    /**
     * restore a url by route name and param
     * 
     * @param  string $name
     * @param  array  $param
     * @return string
     */
    public function url($name, array $param = [])
    {
        $rule = $this->rules[$name];

        return call_user_func_array($rule->restore, [$param]);
    }

    /**
     * add middleware for all rule
     * 
     * @param  string|Closure $middleware [description]
     * @return bool
     */
    public function middleware($middleware)
    {
        $middleware = $this->_procTo($middleware);

        foreach ($this->rules as $rule) {
            $next = $this->_procTo($rule->to);
            $rule->to(function () use ($next, $middleware){
                $param = func_get_args();
                array_unshift($param, $next); 
                return call_user_func_array($middleware, $param);
            });
        }

        return true;
    }

    /**
     * add a group
     * @param  Closure $group
     * @param  string|Closure  $middleware add middleware for the rule registered in $group
     * @return bool
     */
    public function group(Closure $group, $middleware)
    {
        $router = new Router;
        $router->handleClosure($this->_procClosure);
        $group($router);
        $router->middleware($middleware);
        foreach ($router->rules as $rule) {
            $this->add($rule);
        }

        unset($router);

        return true;
    }

    /**
     * math a rule and proc
     * 
     * @param  string  $uri
     * @param  string  $method
     * @param  Closure $closure proc math result
     * @return miexd
     */
    public function run($uri, $method, Closure $closure)
    {
        $to = null;
        $match = null;
        foreach ($this->rules as $rule) {
            $match = $rule->match($uri, $method);
            if ($match !== null) {
                $to = $to = $this->_procTo($rule->to);
                break;
            }

        }

        return $closure($to, $match);
    }

    /**
     * set a Closure to proc rule::to when rule::to is not Closure。
     * @param Closure $closure
     * @return  bool
     */
    public function setProcClosure(Closure $closure)
    {
        $this->_procClosure = $closure;

        return $this;
    }

    /**
     * proc rule::to
     * 
     * @param  miexd $to
     * @return Closure
     */
    protected function _procTo($to)
    {
        if (!($to instanceof Closure)) {
            $procClosure = $this->_procClosure;
            $to = $procClosure($to);
        }

        return $to;
    }

    /**
     * fast way to new rule
     * @param string $method
     * @param string $from
     * @param miexd $to
     * @param string $name
     * @return  bool
     */
    public function addRoute($method, $from, $to, $name = null)
    {
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