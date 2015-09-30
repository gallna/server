<?php
namespace Kemer\Server\ErrorHandler;

use Kemer\Logger\Logger;

class LogHandler extends AbstractHandler
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function displayException(\Exception $exception, $code = null)
    {
        $this->logger->error(
            sprintf("(%s) %s: %s",
                $code ?: $exception->getCode(),
                get_class($exception),
                $exception->getMessage()
            ),
            [
                "file" => sprintf("%s [%s]", $exception->getFile(), $exception->getLine()),
                "trace" => $exception->getTraceAsString()
            ]
        );
    }
}
