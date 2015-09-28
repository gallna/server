<?php
namespace Kemer\UPnP\Server;
error_reporting(-1);
ini_set("display_errors", true);
ini_set("soap.wsdl_cache_enabled", "0");
use_soap_error_handler(true);
use Kemer\UPnP\Description\Device\DeviceDescription;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Http\Exception as ZendException;
use React;

class JsonHandler
{
    /**
     * ContentDirectory server constructor
     *
     * @param LibraryInterface $library
     */
    public function __construct(LibraryInterface $library)
    {
        $this->library = $library;
    }

    public function handle(Request $request)
    {
        $path = $request->getUri()->getPath();
        list($serviceName, $action) = (explode("/", $path, 2));

        $service = $this->getService($serviceName);

        $content = json_decode($request->getContent());
        $response = call_user_func_array([$service, $action], $content);

        return $this->createResponse($response);
    }

    protected function getService($serviceName)
    {
        switch($serviceName) {
            case "contentDirectory":
                $service = $this->mediaServer->getContentDirectory();
                break;
            case "connectionManager":
                $service = $this->mediaServer->getConnectionManager();
                break;
            case "avTransport":
                $service = $this->mediaServer->getAvTransport();
                break;
            default:
                throw new \Exception("Controller not found");
        }
        return $service;
    }

    private function createResponse($xml)
    {

    }
}


