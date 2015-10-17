<?php
namespace Kemer\Server;

use Zend\Http\Request;
use Zend\Http\Response;
use GuzzleHttp\Psr7\Stream;
use FastRoute\RouteCollector;
use Kemer\Logger\Logger;

class StreamSocketServer
{
    /**
     * @var handler
     */
    protected $handler;

    /**
     * @var ErrorHandler
     */
    protected $errorHandler;

    /**
     * @param callable $handler
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Set server error handler
     *
     * @param ErrorHandler\AbstractHandler $errorHandler
     */
    public function setErrorHandler(ErrorHandler\AbstractHandler $errorHandler)
    {
        $this->errorHandler = $errorHandler;
        return $this;
    }

    /**
     * Returns server error handler
     *
     * @return ErrorHandler\AbstractHandler
     */
    public function getErrorHandler()
    {
        if (!$this->errorHandler) {
            $this->setErrorHandler(new ErrorHandler\CliHandler());
        }
        return $this->errorHandler;
    }

    /**
     * Run server
     *
     * @param integer $port
     * @param string $host
     * @return void
     */
    public function run($port, $host = null)
    {
        $this->getErrorHandler()->register();
        $socket = $this->createSocket($port, $host);
        //stream_set_blocking($socket, 0);
        echo "listening... [".$host.":".$port."]";
        // Using for without statements causes it to loop forever. We need this,
        // because the server should run until we decide to kill it.
        for (;;) {
            $read[] = $socket;
            if (stream_select($read, $write, $except, 0, 200000) > 0) {
                //new client
                if (!empty($read) && ($client = stream_socket_accept($socket))) {
                    $this->handleConnection($client);
                    is_resource($client) and fclose($client);
                }
            }
        }
        fclose($socket);
    }

    /**
     * Create a TCP socket
     *
     * @param integer $port
     * @param string $host
     * @return resource
     */
    private function createSocket($port, $host)
    {
        $host = $host ?: exec(
            "/sbin/ifconfig eth0 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'"
        );
        $tcpSocket = sprintf("tcp://%s:%s", $host, $port);
        if (!($socket = stream_socket_server($tcpSocket, $errno, $errstr))) {
            throw new \Exception("($errno) $errstr");
        }
        return $socket;
    }

    private function handleConnection($client)
    {
        //$connection = new Connection($client);
        try {
            return call_user_func($this->handler, $client);
        } catch (\Exception $e) {
            $this->getErrorHandler()->displayException($e);
        } finally {
            if (is_resource($client)) {
                fclose($client);
            }
        }
    }
}
