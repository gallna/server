<?php
namespace Kemer\Server\ErrorHandler;

abstract class AbstractHandler implements HandlerInterface
{
    /**
     * Display exception
     *
     * @param \Exception $exception
     * @param string $code Optional name of error
     * @return void
     */
    abstract public function displayException(\Exception $exception, $code = null);

    /**
     * Register handler
     *
     * @return $this
     */
    public function register()
    {
        error_reporting(-1);
        ini_set("display_errors", true);

        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this, "fatalErrorHandler"]);
        return $this;
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
    }

    /**
     * Sets the default error handler
     *
     * @param integer $code
     * @param string $message
     * @param string $file
     * @param integer $line
     * @return bool
     */
    public function errorHandler($code, $message, $file, $line)
    {
        $exception = new \ErrorException($message, $code, 1, $file, $line);
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

    /**
     * Set fatal error handler
     *
     * @return void
     */
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

    /**
     * Returns string representation of error
     *
     * @param integer $code
     * @return string
     */
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
