<?php
namespace Kemer\Server\ErrorHandler;

class DefaultHandler extends AbstractHandler
{
    /**
     * Create exception header
     *
     * @param Exception $exception
     * @return string
     */
    protected function getHeader(\Exception $exception)
    {
        $file = $exception->getFile();
        if ($exception->getFile()) {
            $file = basename($exception->getFile());
        }
        return sprintf("%s:%s (%s) %s: %s\n",
            $file,
            $exception->getLine(),
            ($exception instanceof \ErrorException)
                ? $this->errorType($exception->getCode())
                : $exception->getCode(),
            get_class($exception),
            $exception->getMessage()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function displayException(\Exception $exception, $code = null)
    {
        echo $this->getHeader($exception);
        echo $exception->__toString();
    }
}
