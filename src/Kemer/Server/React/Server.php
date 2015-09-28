<?php
namespace Kemer\Server\React;
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
use React\EventLoop\Factory;

class Server
{
    protected $routing = [];
    private $loop;
    public function __construct(array $routing, LoopInterface $loop = null)
    {
        $this->routing = $routing;
        $this->loop = $loop ?: Factory::create();
    }

    public function run($port, $host = null)
    {
        $server = new SocketServer($this->loop);
        $server->listen($port, $host);
        $server->on('connection', [$this, "handleConnection"]);
        $server->on('error', [$this, "onError"]);
        $server->on('end', [$this, "onEnd"]);
        e("listening... [".$host.":".$port."]", 'cyan');
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

    public function handleConnection(SocketClient $stream)
    {
        $handler = new RequestHandler($this->routing, $stream);
        $stream->on('data', [$handler, "onData"]);
    }
}


