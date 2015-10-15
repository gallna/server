<?php
namespace Kemer\Server;

use Zend\Http\Request;
use Zend\Http\Response;
use GuzzleHttp\Psr7\Stream;
use FastRoute\RouteCollector;
use Kemer\Logger\Logger;

class SocketServer
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ErrorHandler
     */
    protected $errorHandler;

    /**
     * @param Router $router
     */
    public function __construct(Router $router, Logger $logger = null)
    {
        $this->router = $router;
        $this->logger = $logger ?: new Logger();
        $this->router->setLogger($this->logger);
    }

    /**
     * Returns local ip address
     *
     * @return string
     */
    public function getIp()
    {
        return $localIP = exec(
            "/sbin/ifconfig eth0 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'"
        );
    }

    /**
     * Run server
     *
     * @param integer $port
     * @param string $host
     * @return [type]
     */
    public function run($port, $host = null)
    {
        error_reporting(-1);
        ini_set("display_errors", true);
        $this->errorHandler = (new ErrorHandler\DefaultHandler($this->logger))->register();
        $socket = $this->createSocket($port, $host);
        //stream_set_blocking($socket, 0);
        $this->logger->info("listening... [".$host.":".$port."]");
        // Using for without statements causes it to loop forever. We need this,
        // because the server should run until we decide to kill it.
        for (;;) {
            $read[] = $socket;
            if (stream_select($read, $write, $except, 0) > 0) {
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
        $connection = new Connection($client);
        try {
            return $this->router->dispatch($client);
        } catch (\Exception $e) {
            $this->errorHandler->displayException($e);
        } finally {
            $connection->close();
            is_resource($client) and fclose($client);
        }
    }
}
