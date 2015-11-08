<?php
namespace Kemer\Server\ErrorHandler;

use Bugsnag_Client;

class CallbackHandler extends AbstractHandler
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Returns Bugsnag client
     *
     * @return Bugsnag_Client
     */
    public function getCallback()
    {
        return $this->bugsnag;
    }

    /**
     * {@inheritdoc}
     */
    public function displayException(\Exception $exception, $code = null)
    {
        return call_user_func($this->getCallback(), $exception);
    }
}
