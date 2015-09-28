<?php
namespace Kemer\Server;

use Zend\Http\PhpEnvironment\Request as PhpRequest;
use Zend\Http\Request;
use Zend\Http\Response;
use Kemer\Stream\Stream\ApacheStream;
use React;

class ApacheServer
{
    protected $routing = [];

    public function __construct(array $routing)
    {
        $this->routing = $routing;
    }

    public function run()
    {
        $this->handleConnection();
    }

    public function handleConnection()
    {
        $conn = new ApacheStream();
        $this->handleData($conn);
    }

    public function handleData($conn)
    {
        $request = new PhpRequest();
        list(,$path, ) = explode("/", $request->getUri()->getPath(), 3);
        if (isset($this->routing[$path])) {
            file_put_contents(__DIR__."/requests.txt", $request->toString(), FILE_APPEND);
            //e($request->toString(), 'green');
            call_user_func($this->routing[$path], $request, $conn);
            //e("Connection end.", 'light_cyan');
        } else {
            //e($request->toString(), 'red');
        }
    }
}


