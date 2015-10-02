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
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function __invoke($client)
    {
        return $this->handleConnection($client);
    }

    public function handleConnection($client)
    {
        $connection = new Connection($client);
        try {
            $this->router->dispatch($connection);
        } catch (\Exception $e) {
            $connection->isWritable() and $connection->write($this->serverError());
            throw $e;
        } finally {
            $connection->close();
        }
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
