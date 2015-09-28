<?php
namespace Kemer\Server;

use Zend\Http\Request;
use Zend\Http\Response;

class CallbackFork
{
    /**
     * @var string|array|callable PHP callback to invoke
     */
    protected $callback;

    public function __construct($callback)
    {
        if (!is_callable($callback)) {
            throw new \Exception("Only callable can be accepted");
        }
        $this->callback = $callback;
    }

    /**
     * Request handler
     *
     * @param string $request
     * @param array $routing
     * @return void
     */
    public function call()
    {
        $parameters = func_get_args();
        if (!$pid = pcntl_fork()) {
            exit(call_user_func_array($this->callback, $parameters));
        } else {
            return $pid;
        }

    }
}
