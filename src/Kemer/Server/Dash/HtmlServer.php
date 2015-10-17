<?php
namespace Kemer\Server\Dash;

use Kemer\Logger\Logger;
use Kemer\Server;
use Kemer\Server\ErrorHandler;

class HtmlServer
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
     * @param Router $router
     */
    public function __construct(Server\Router $router, Logger $logger)
    {
        $this->router = $router;
        $this->logger = $logger;
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
        $this->router->setLogger($this->logger);
        $htmlServer = new Server\HtmlServer($this->router);

        $server = new Server\StreamSocketServer([$htmlServer, "handleConnection"]);
        $server->setErrorHandler(new ErrorHandler\CliHandler($this->logger));

        $server->run($port, $host);
    }
}
