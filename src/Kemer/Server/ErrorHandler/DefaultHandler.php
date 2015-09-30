<?php
namespace Kemer\Server\ErrorHandler;

use \Bramus\Ansi\Ansi;
use \Bramus\Ansi\Writers\StreamWriter;
use \Bramus\Ansi\ControlSequences\EscapeSequences\Enums\SGR;

class DefaultHandler extends AbstractHandler
{
    /**
     * @var Ansi
     */
    private $ansi;
    /**
     * Display message
     *
     * @param string $message
     */
    public function ansi()
    {
        if (!$this->ansi) {
            $this->ansi = new Ansi(new StreamWriter('php://stdout'));
        }
        return $this->ansi;
    }

    /**
     * Display message
     *
     * @param string $message
     */
    public function display($message)
    {
        $this->ansi()->color(array(SGR::COLOR_FG_RED_BRIGHT))
             ->text($message);
    }

    /**
     * {@inheritdoc}
     */
    public function displayException(\Exception $exception, $code = null)
    {
        $file = $exception->getFile();
        if ($exception->getFile()) {
            $file = basename($exception->getFile());
        }
        $this->display(sprintf("%s:%s (%s) %s: %s\n",
            $file,
            $exception->getLine(),
            $code ?: $exception->getCode(),
            get_class($exception),
            $exception->getMessage()
        ));
    }
}
