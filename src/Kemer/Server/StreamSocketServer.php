<?php
namespace Kemer\Server;

use Zend\Http\Request;
use Zend\Http\Response;
use GuzzleHttp\Psr7\Stream;
use FastRoute\RouteCollector;
use Kemer\Logger\Logger;

class StreamSocketServer
{

    protected $routing = [];

    public function __construct($routing)
    {
        $this->routing = $routing;
    }

    public function run($port, $host = "127.0.0.1")
    {
        $tcpSocket = sprintf("tcp://%s:%s", $host, $port);
        if (!($socket = stream_socket_server($tcpSocket, $errno, $errstr))) {
            throw new \Exception("($errno) $errstr");
        }
        new Logger();
        //stream_set_blocking($socket, 0);
        e("listening... [".$host.":".$port."]", 'cyan');
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this, "fatalErrorHandler"]);
        // Using for without statements causes it to loop forever. We need this,
        // because the server should run until we decide to kill it.
        for (;;) {
            // Error suppresssion is intentional here, because this function likes
            // to spit out unnecessary warnings.
            if ($client = @stream_socket_accept($socket, 3600)) {
                $this->handleConnection($client);
            }
        }
        fclose($socket);
    }

    public function handleConnection($client)
    {
        if ($this->routing instanceof Router) {
            return $this->routing->dispatch($client);
        }
        $connection = new Connection($client);
        $request = $connection->read(2048);
        e("New connection.", 'light_cyan');
        if (!empty($request)) {

            $connection->handle($request, $this->routing);
        }
    }

    public function errorHandler($code, $message, $file, $line)
    {
        $exception = new \ErrorException($message, $code, 1, $file, $line);
        switch ($code) {
            case E_USER_ERROR:
                $this->displayException($exception, "E_USER_ERROR");
                throw $exception;
                //exit(1);
                break;

            case E_USER_WARNING:
                $this->displayException($exception, "E_USER_WARNING");
                break;

            case E_USER_NOTICE:
                $this->displayException($exception, "E_USER_NOTICE");
                break;

            default:
                $this->displayException($exception, "E_USER");
                break;
        }
        /* Don't execute PHP internal error handler */
        return true;
    }

    public function fatalErrorHandler()
    {
        $error = error_get_last();
        if( $error !== NULL) {
            $code = $error["type"];
            $file = $error["file"];
            $line = $error["line"];
            $message = $error["message"];
            $exception = new \ErrorException($message, $code, 0, $file, $line);
            $this->exceptionHandler($exception);
            exit(1);
        }
    }

    public function exceptionHandler(\Exception $exception)
    {
        $this->displayException($exception);
        //exit(-1);
    }

    protected function displayException(\Exception $exception, $code = null)
    {
        e(sprintf("%s [%s]", $exception->getFile(), $exception->getLine()), 'cyan');
        e(sprintf("(%s) %s", $code ?: $exception->getCode(), $exception->getMessage()), 'light_red');
        $trace = $exception->getTrace();
        d(array_slice($trace, 0, 2));
    }

}


/*
// open a server on port 4444
$server = stream_socket_server("tcp://0.0.0.0:4444", $errno, $errorMessage);

if ($server === false)
{
    die("Could not bind to socket: $errorMessage");
}

$client_socks = array();
while(true)
{
    //prepare readable sockets
    $read_socks = $client_socks;
    $read_socks[] = $server;

    //start reading and use a large timeout
    if(!stream_select ( $read_socks, $write, $except, 300000 ))
    {
        die('something went wrong while selecting');
    }

    //new client
    if(in_array($server, $read_socks))
    {
        $new_client = stream_socket_accept($server);

        if ($new_client)
        {
            //print remote client information, ip and port number
            echo 'Connection accepted from ' . stream_socket_get_name($new_client, true) . "n";

            $client_socks[] = $new_client;
            echo "Now there are total ". count($client_socks) . " clients.n";
        }

        //delete the server socket from the read sockets
        unset($read_socks[ array_search($server, $read_socks) ]);
    }

    //message from existing client
    foreach($read_socks as $sock)
    {
        $data = fread($sock, 128);
        if(!$data)
        {
            unset($client_socks[ array_search($sock, $client_socks) ]);
            @fclose($sock);
            echo "A client disconnected. Now there are total ". count($client_socks) . " clients.n";
            continue;
        }
        //send the message back to client
        fwrite($sock, $data);
    }
}
 */


