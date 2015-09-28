<?php
namespace Kemer\Server;

use Zend\Http\Request;
use Zend\Http\Response;
use Kemer\Stream\Stream;
use Kemer\Logger\Logger;

class Connection extends Stream
{

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct($stream, array $options = [])
    {
        parent::__construct($stream, $options);
    }

    /**
     * Request handler
     *
     * @param string $request
     * @param array $routing
     * @return void
     */
    public function handle($request, array $routing)
    {
        $request = Request::fromString($request);
        list(,$path,) = explode("/", $request->getUri()->getPath(), 3);

        if (isset($routing[$path])) {
            e($request->toString(), 'green');
            if ($response = call_user_func($routing[$path], $request, $this)) {
                $this->handleResponse($response);
            }
            $this->close();
            e("Connection end.", 'light_cyan');
        } else {
            e($request->toString(), 'red');
            $this->close();
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
        list($headers, $content) = explode("\r\n\r\n", $response, 2);
        e(sprintf("%s\n\n%s[...]%s", $headers, substr($content, 0, 30), substr($content, -30)), 'blue');
        $this->write($response);
    }

    public function getRemoteAddress()
    {
        $address = stream_socket_get_name($this->stream, true);
        return trim(substr($address, 0, strrpos($address, ':')), '[]');
    }

    public function write($string)
    {
        for ($written = 0; $written < strlen($string); $written += $fwrite) {
            $fwrite = parent::write(substr($string, $written));
            if ($fwrite === false || $fwrite === 0) {
                throw new \RuntimeException('Unable to write to stream');
            }
        }
        return $written;
    }
}
