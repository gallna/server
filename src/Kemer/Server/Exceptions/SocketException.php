<?php
namespace Kemer\Server\Exceptions;

/**
 * Socket exception
 */
class SocketException extends \RuntimeException
{
    /**
     * Constructor
     *
     * @param int $code
     */
    public function __construct($code = null)
    {
        if ($code !== null) {
            $code = socket_last_error();
        }
        parent::__construct(socket_strerror($code), $code);
    }

    private function write(array $clients, $chunk)
    {
        foreach($clients as $sock) {
            try {
                fwrite($sock, $chunk);
            } catch (\ErrorException $e) {
                if (preg_match("/^fwrite\(\): send of (\d+) bytes failed with errno=([0-9]+) ([A-Za-z \/]+)$/",$e->getMessage(), $matches)) {
                    if ($matches[2] == 32) {
                        e($e->getMessage(), 'red');
                        return;
                    }
                }
                throw $e;
            }
        }
    }
}
