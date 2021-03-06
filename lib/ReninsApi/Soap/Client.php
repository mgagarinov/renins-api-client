<?php

namespace ReninsApi\Soap;

/**
 * Soap client
 */
class Client
{
    /**
     * Url to WSDL
     * @var string
     */
    protected $wsdl;

    /**
     * Last request with headers
     * @var string
     */
    protected $lastRequest;

    /**
     * Last header tag
     * For example:
     * [
     *   "XsdValidation" => stdClass Object
     *   (
     *     [In] => "The required attribute 'uid' is missing.
     *       The 'MultiDrive' attribute is not declared.
     *       The required attribute 'Multidrive' is missing."
     *   )
     *   "MessageId" => "77e95d78-a3d9-47fc-b21d-8f2aca29d274"
     *   "ExecutionTime" => "00:00:14.0338612"
     * ]
     * @var array
     */
    protected $lastHeader = [];

    /**
     * Last response with headers
     * @var string
     */
    protected $lastResponse;

    /**
     * Will xsd validation fail throw an exception?
     * @var bool
     */
    protected $isFatalXsdFail = false;

    public function __construct(string $wsdl)
    {
        $this->wsdl = $wsdl;
    }

    /**
     * @return string
     */
    public function getLastRequest(): string
    {
        return $this->lastRequest;
    }

    /**
     * @return string
     */
    public function getLastResponse(): string
    {
        return $this->lastResponse;
    }

    /**
     * @return array
     */
    public function getLastHeader(): array
    {
        return $this->lastHeader;
    }

    /**
     * @return bool
     */
    public function isFatalXsdFail(): bool
    {
        return $this->isFatalXsdFail;
    }

    /**
     * @param bool $isFatalXsdFail
     * @return $this
     */
    public function setIsFatalXsdFail(bool $isFatalXsdFail)
    {
        $this->isFatalXsdFail = $isFatalXsdFail;
        return $this;
    }

    /**
     * Make request
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function makeRequest(string $method, array $arguments = []) {
        $soap = new \SoapClient($this->wsdl, [
            'exceptions' => true,
            'connection_timeout' => 30,
            //'soap_version' => SOAP_1_2,
            //'cache_wsdl' => WSDL_CACHE_NONE,
            'trace' => true,
        ]);

        $this->lastHeader = [];
        try {
            $res = $soap->__soapCall($method, $arguments, null, null, $this->lastHeader);
        } finally {
            $this->lastRequest = $soap->__getLastRequestHeaders() . $soap->__getLastRequest();
            $this->lastResponse = $soap->__getLastResponseHeaders() . $soap->__getLastResponse();
        }

        //XsdValidation
        if ($this->isFatalXsdFail) {
            if ($this->lastHeader && !empty($this->lastHeader['XsdValidation'])) {
                $xsdValidation = $this->lastHeader['XsdValidation'];
                if (!empty($xsdValidation->In)) {
                    throw new ClientException("Xsd validation failed: " . str_replace("\n", ' ', $xsdValidation->In));
                }
            }
        }

        return $res;
    }
}