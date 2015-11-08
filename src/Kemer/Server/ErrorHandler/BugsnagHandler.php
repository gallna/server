<?php
namespace Kemer\Server\ErrorHandler;

use Bugsnag_Client;

class BugsnagHandler extends AbstractHandler
{
    const SEVERITY_FATAL = "fatal";
    const SEVERITY_ERROR = "error";
    const SEVERITY_WARNING = "warning";
    const SEVERITY_INFO = "info";

    /**
     * @var Bugsnag_Client
     */
    private $bugsnag;

    /**
     * Initialize Bugsnag and create handler
     *
     * @param string $apiKey Bugsnag API key
     * @param string $stage Release stage, eg "production" or "development"
     * @return BugsnagHandler
     */
    public static function create($apiKey, $stage = "development")
    {
        if (!is_string($apiKey)) {
            throw new \InvalidArgumentException(
                "Expected string, '".gettype($apiKey)."' provided"
            );
        }
        $bugsnag = new Bugsnag_Client($apiKey);
        $bugsnag->setReleaseStage($stage);
        return new static($bugsnag);
    }

    /**
     * @param Bugsnag_Client $bugsnag
     */
    public function __construct(Bugsnag_Client $bugsnag)
    {
        $this->bugsnag = $bugsnag;
    }

    /**
     * Returns Bugsnag client
     *
     * @return Bugsnag_Client
     */
    public function getBugsnag()
    {
        return $this->bugsnag;
    }

    /**
     * {@inheritdoc}
     */
    public function displayException(\Exception $exception, $code = null)
    {
        $extra = [];
        if ($exception instanceof \ErrorException) {
            switch ($exception->getCode()) {
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                case E_PARSE:
                    $severity = static::SEVERITY_ERROR;
                    break;

                case E_WARNING:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                case E_USER_WARNING:
                    $severity = static::SEVERITY_WARNING;
                    break;

                case E_NOTICE:
                case E_USER_NOTICE:
                    $severity = static::SEVERITY_INFO;
                    break;

                case E_STRICT:
                case E_RECOVERABLE_ERROR:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                    $severity = static::SEVERITY_INFO;
                    break;

                default:
                    $severity = static::SEVERITY_ERROR;
                    break;
            }
            return $this->getBugsnag()->notifyException($exception, $extra, $severity);
        }
        $this->getBugsnag()->notifyException($exception, $extra, static::SEVERITY_ERROR);
    }
}
