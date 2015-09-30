<?php
namespace Kemer\Server;

use Zend\Http\Request;
use Zend\Http\Response;
use GuzzleHttp\Psr7\Stream;
use FastRoute\RouteCollector;
use Kemer\Logger\Logger;

class HtmlServer
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var StreamSocketServer
     */
    protected $server;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
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
        $this->server = new StreamSocketServer([$this, "handleConnection"]);
        $this->server->run($port, $host);
    }

    public function handleConnection($client)
    {
        $connection = new Connection($client);
        try {
            if (!$this->router->dispatch($client)) {
                $connection->write($this->notFound());
            }
        } catch (\Exception $e) {
            $this->server->getErrorHandler()->displayException($e);
            $connection->write($this->serverError());
        } finally {
            $connection->close();
        }
    }

    private function notFound()
    {
        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_404);
        $response->getHeaders()->addHeaders([
            "Access-Control-Allow-Origin" => "*",
            'Server' => "Linux/3.x, UPnP/1.0, Kemer/0.1",
        ]);
        return $response;
    }

    private function serverError()
    {
        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_500);
        $response->getHeaders()->addHeaders([

        ]);
        return $response;
    }
}
