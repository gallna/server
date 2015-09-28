<?php
namespace Kemer\Server;
error_reporting(-1);
ini_set("display_errors", true);
ini_set("soap.wsdl_cache_enabled", "0");
use_soap_error_handler(true);
use Kemer\UPnP\Description\Device\DeviceDescription;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Http\Exception as ZendException;
use React;
use React\EventLoop\LoopInterface;

class ReactSocketServer extends AbstractReactServer
{
    protected $routing = [];

    public function __construct(array $routing, LoopInterface $loop = null)
    {
        $this->routing = $routing;
        $loop = $loop ?: React\EventLoop\Factory::create();
        parent::__construct($loop);
    }

    public function run($port, $host = null)
    {
        parent::listen($port, $host);
        $this->on('connection', [$this, "handleConnection"]);
        $this->on('error', [$this, "onError"]);
        $this->on('end', [$this, "onEnd"]);
        $this->loop->run();
    }

    public function onError($exception = null)
    {
        var_dump(func_get_args());
    }

    public function onEnd($port, $host = null)
    {
        var_dump($data, "blue");
    }

    public function handleConnection(ConnectionStream $stream)
    {
        $handler = new RequestHandler($this->routing, $stream);
        $stream->on('data', [$handler, "onData"]);
    }
}


