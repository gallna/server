<?php
namespace Kemer\Server\ErrorHandler;

interface HandlerInterface
{
    /**
     * Display exception
     *
     * @param \Exception $exception
     * @param string $code Optional name of error
     * @return void
     */
    public function displayException(\Exception $exception, $code = null);
}
