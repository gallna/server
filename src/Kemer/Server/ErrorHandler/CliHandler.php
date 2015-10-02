<?php
namespace Kemer\Server\ErrorHandler;

use \Bramus\Ansi\Ansi;
use \Bramus\Ansi\Writers\StreamWriter;
use \Bramus\Ansi\ControlSequences\EscapeSequences\Enums\SGR;

class CliHandler extends DefaultHandler
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
     * {@inheritdoc}
     */
    public function displayException(\Exception $exception, $code = null)
    {
        $this->ansi()->color([SGR::COLOR_FG_RED_BRIGHT])->text($this->getHeader($exception));
        $this->ansi()->color([SGR::COLOR_FG_YELLOW])->text($exception->__toString());
    }
}
