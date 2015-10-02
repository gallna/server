<?php
namespace Kemer\Server;

use Kemer\Logger\Logger;
use Zend\Http\Request;
use Zend\Http\Response;
use FastRoute\Dispatcher;
use FastRoute;

class Router
{
    /**
     * @var array
     */
    private $routing = [];

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Dispatcher|null $dispatcher
     */
    public function __construct(Dispatcher $dispatcher = null, Logger $logger = null)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    /**
     * Set Logger instance
     *
     * @return Logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Return and create FastRoute dispatcher
     *
     * @return Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher
            ?: $this->dispatcher = FastRoute\simpleDispatcher([$this, "collect"]);
    }

    /**
     * FastRoute\simpleDispatcher callback
     *
     * @param RouteCollector $r
     * @return void
     */
    public function collect(FastRoute\RouteCollector $r)
    {
        foreach ($this->routing as $routing) {
            list($httpMethod, $route, $handler) = $routing;
            $r->addRoute($httpMethod, $route, $handler);
        }
    }

    /**
     * Adds a route to the collection.
     *
     * @param string|string[] $httpMethod
     * @param string $route
     * @param mixed  $handler
     */
    public function addRoute($httpMethod, $route, $handler)
    {
        $this->routing[] = [$httpMethod, $route, $handler];
        return $this;
    }

    /**
     * Dispatch client Request
     *
     * @param resource|Connection $client
     * @return void
     */
    public function dispatch($connection)
    {
        if (!($connection instanceof Connection)) {
            $connection = new Connection($connection);
        }

        $message = $connection->read(2048);
        if (empty($message)) {
            return;
        }
        $request = Request::fromString($message);
        $routeInfo = $this->getDispatcher()->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $this->logger->warn(sprintf("Not found: %s", $request->getUri()->getPath()));
                $connection->write($this->notFound());
            case Dispatcher::METHOD_NOT_ALLOWED:
                $this->logger->warn(sprintf("Not allowed: %s", $request->getUri()->getPath()));
                $connection->write($this->notAllowed());
            case Dispatcher::FOUND:
                $this->logger->info(sprintf("New connection: %s", $request->getUri()->getPath()));
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                array_unshift($vars, $request);
                array_push($vars, $connection);
                if ($response = call_user_func_array($handler, $vars)) {
                    $connection->write($this->handleResponse($response));
                }
        }
    }

    protected function handleResponse($response)
    {
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
        $segments = explode("\r\n\r\n", $response);
        if (isset($segments[1])) {
            list($headers, $content) = $segments;
            $this->logger->info(
                "response",
                [sprintf("%s\n\n%s[...]%s", $headers, substr($content, 0, 30), substr($content, -30))]
            );
        }

        return $response;
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

    private function notAllowed()
    {
        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_405);
        $response->getHeaders()->addHeaders([
            "Access-Control-Allow-Origin" => "*",
            'Server' => "Linux/3.x, UPnP/1.0, Kemer/0.1",
        ]);
        return $response;
    }
}
