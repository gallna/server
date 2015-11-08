<?php
namespace Kemer\Server\ErrorHandler;

class AggregateHandler extends AbstractHandler
{
    private $handlers = [];

    /**
     * Push header
     *
     * @param HandlerInterface $handler
     * @return $this
     */
    public function pushHandler(HandlerInterface $handler)
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function displayException(\Exception $exception, $code = null)
    {
        $return = false;
        foreach($this->handlers as $handler) {
            $handler->displayException($exception, $code) and $return = true;
        }
        return $return;
    }
}
