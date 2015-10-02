<?php
namespace Kemer\Server\ErrorHandler;

use Kemer\Logger\Logger;

class LogHandler extends DefaultHandler
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
        $context = [$exception->__toString()];
        if ($exception instanceof \ErrorException) {
            switch ($exception->getCode()) {
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                case E_PARSE:
                    $this->logger->error($this->getHeader($exception), $context);
                    break;

                case E_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                case E_USER_WARNING:
                    $this->logger->warn($this->getHeader($exception), $context);
                    break;

                case E_NOTICE:
                case E_USER_NOTICE:
                    $this->logger->notice($this->getHeader($exception), $context);
                    break;

                case E_STRICT:
                case E_RECOVERABLE_ERROR:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    $this->logger->notice($this->getHeader($exception), $context);
                    break;

                default:
                    $this->logger->error($this->getHeader($exception), $context);
                    break;
            }
        } else {
            $this->logger->error($this->getHeader($exception), $context);
        }
    }
}
