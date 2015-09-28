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

class RequestHandler
{
    protected $routing = [];
    private $buffer = '';
    private $request;

    public function __construct(array $routing, SocketClient $stream)
    {
        $this->routing = $routing;
        $this->stream = $stream;
    }

    public function onData($data, $stream)
    {
        $this->buffer .= $data;
        if (preg_match('/Content-Length:\s?(?P<length>\d+)/', $this->buffer, $matches)) {
            // Check it isn't chunked request
            if (strpos($this->buffer, "\r\n\r\n") != (strlen($this->buffer) - 4)) {
                $this->handleRequest($this->buffer, $this->stream);
            }
        } else {
            $this->handleRequest($this->buffer, $this->stream);
        }
    }

    protected function handleRequest($data)
    {
        $this->request = $request = Request::fromString($data);
        list(,$path, ) = explode("/", $request->getUri()->getPath(), 3);

        if (!isset($this->routing[$path])) {
            e($request->toString(), 'red');
            return;
        }
        e($request->toString(), 'green');

        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_200);
        //$response->getHeaders()->addHeaders([]);


        if (!($response = call_user_func($this->routing[$path], $request, $response, $this->stream))) {
            throw new \Exception(
                sprintf(
                    "Controler response should be a closure, string or instanceof Response, '%s' given",
                    gettype($response)
                )
            );
        }
        return $this->handleResponse($response, $this->stream);
    }

    protected function handleResponse($response)
    {
        if (is_callable($response)) {
            return $this->stream->addWriteStream($response);
        }
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
        $this->stream->write($response);
        $this->stream->end();
    }
}


