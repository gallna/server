<?php
namespace Kemer\Server;

use Zend\Http\Request;
use Zend\Http\Response;
use GuzzleHttp\Psr7\Stream;
use React;

class StreamSocketServer
{
    protected $routing = [];

    public function __construct(array $routing)
    {
        $this->routing = $routing;
    }

    public function run($port, $host = "127.0.0.1")
    {
        $tcpSocket = sprintf("tcp://%s:%s", $host, $port);
        if (!($socket = stream_socket_server($tcpSocket, $errno, $errstr))) {
            throw new \Exception("($errno) $errstr");
        }
        e("listening.", 'cyan');
        // Using for without statements causes it to loop forever. We need this,
        // because the server should run until we decide to kill it.
        for (;;) {
            // Error suppresssion is intentional here, because this function likes
            // to spit out unnecessary warnings.
            $conn = @stream_socket_accept($socket, 3600);
            if ($conn) {
                $this->handleConnection($conn);
            }
        }
        fclose($socket);
    }

    public function handleConnection($conn)
    {
        $conn = new Stream($conn);
        $data = $conn->read(2048);
        e("New connection.", 'light_cyan');
        if (!empty($data)) {
            $this->handleData($data, $conn);
        }
        $conn->close();
    }

    public function handleData($rawRequest, $conn)
    {
        $request = Request::fromString($rawRequest);
        list(,$path, ) = explode("/", $request->getUri()->getPath(), 3);

        if (isset($this->routing[$path])) {
            e($request->toString(), 'green');
            call_user_func($this->routing[$path], $request, $conn);
            e("Connection end.", 'light_cyan');
        } else {
            e($request->toString(), 'red');
        }
    }
}


