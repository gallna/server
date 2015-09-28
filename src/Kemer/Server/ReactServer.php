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

class ReactServer
{
    protected $routing = [];

    public function __construct(array $routing)
    {
        $this->routing = $routing;
    }

    public function run($port, $host = null)
    {
        $loop = React\EventLoop\Factory::create();
        $socket = new React\Socket\Server($loop);
        $socket->on('connection', [$this, "handleConnection"]);
        $socket->on('error', function () {
            var_dump(func_get_args());
        });
        $socket->on('end', function ($data) {
            var_dump($data, "blue");
        });
        $socket->listen($port, $host);
        $loop->run();
    }

    public function handleConnection($conn)
    {
        $buffer = null;
        $conn->on('data', function ($data) use (&$buffer, $conn) {
            $buffer .= $data;
            if (preg_match('/Content-Length:\s?(?P<length>\d+)/', $buffer, $matches)) {
                // Check it isn't chunked request
                if (strpos($buffer, "\r\n\r\n") != (strlen($buffer) - 4)) {
                    $conn->write($this->handleData($buffer));
                    $conn->end();
                }
            } else {
                $conn->write($this->handleData($buffer));
                $conn->end();
                return;
            }
        });
    }

    public function handleData($rawRequest)
    {
        $request = Request::fromString($rawRequest);
        list(,$path, ) = explode("/", $request->getUri()->getPath(), 3);

        e($path, 'yellow');
        if (isset($this->routing[$path])) {
            e($request->toString(), 'green');
            $response = call_user_func($this->routing[$path], $request);
            if ($response instanceof Response) {
                $response = $response->toString();
            }
            if (!is_string($response)) {
                throw new \Exception(
                    sprintf(
                        "Controler response should be a string or instanceof Response, '%s' given",
                        gettype($response)
                    )
                );
            }
            list($headers, $content) = explode("\r\n\r\n", $response, 2);
            e(sprintf("%s\n\n%s[...]%s", $headers, substr($content, 0, 30), substr($content, -30)), 'blue');
            return $response;
        } else {
            e($request->toString(), 'red');
        }

    }
}


