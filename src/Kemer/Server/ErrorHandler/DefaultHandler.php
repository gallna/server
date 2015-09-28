<?php
namespace Kemer\Server\ErrorHandler;

use Zend\Http\Request;
use Zend\Http\Response;
use GuzzleHttp\Psr7\Stream;
use FastRoute\RouteCollector;
use Kemer\Logger\Logger;

class DefaultHandler
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
     * Run server
     *
     * @param integer $port
     * @param string $host
     * @return [type]
     */
    public function register()
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this, "fatalErrorHandler"]);
        return $this;
    }

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

    /**
     * Sets the default exception handler if an exception is not caught within a try/catch block.
     * Execution will stop after the exception_handler is called.
     *
     * @param \Exception $exception
     * @return void
     */
    public function exceptionHandler(\Exception $exception)
    {
        $this->displayException($exception);
        //exit(-1);
    }

    public function errorHandler($code, $message, $file, $line)
    {
        $exception = new \ErrorException($message, $code, 1, $file, $line);
        // $this->displayException($exception, $this->errorType($code));
        // throw $exception;
        switch ($code) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $this->displayException($exception, $this->errorType($code));
                return false;

            default:
                throw $exception;
                /* Don't execute PHP internal error handler */
                return true;
        }
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
            $this->displayException($exception, "FATAL_ERROR");
            exit(1);
        }
    }

    public function errorType($code)
    {
        switch($code)
        {
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }
        return "";
    }
}
