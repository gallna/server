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

class SoapHandler
{
    public function handle(Request $request)
    {
        list($serviceName) = (explode("/", $path, 1));

        $service = $this->getService($serviceName);

        ob_start();
        $service->handle($request->getContent());
        $xml = ob_get_clean();

        return $this->createResponse($xml);
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
        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_200);
        $response->getHeaders()->addHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'Server' => "Linux/3.x, UPnP/1.0, Kemer/0.1",
            'Content-Length' => strlen($xml),
        ]);
        $response->setContent($xml);
        return $response;
    }
}


