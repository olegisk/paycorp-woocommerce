<?php

abstract class BaseFacade {

    protected $config;

    protected function __construct($config) {
        $this->config = $config;
    }

    protected function process($request, $operation, $jsonHelper) {
        $jsonRequest = $this->buildRequest($request, $operation, $jsonHelper);
        /*echo "<h5>Raw request : </h5>";
        echo '<code>' . $jsonRequest.'</code>';*/

        $headers = $this->buildHeaders($jsonRequest);
        
        $jsonResponse = RestClient::sendRequest($this->config, $jsonRequest, $headers);
        // echo "<h5>Raw response : </h5>";
        // echo '<code>' . $jsonResponse.'</code>';
        
        
        // return $this->buildResponse($jsonResponse, $jsonHelper); 
        $json_to_object = json_decode($jsonResponse);  
        return $json_to_object;
    }

    private function buildHeaders($request) {
        $header = new RequestHeader();
        $header->setAuthToken($this->config->getAuthToken());
        $header->setHmac(HmacUtils::genarateHmac($this->config->getHmacSecret(), $request));

        $headers = array();
        $headers[] = 'HMAC: ' . $header->getHmac() . '';
        $headers[] = 'AUTHTOKEN: ' . $header->getAuthToken() . '';
        $headers[] = 'Content-Type: application/json';

        return $headers;
    }

    private function buildRequest($requestData, $operation, $jsonHelper) {
        $paycorpRequest = new PaycorpRequest();
        $paycorpRequest->setOperation($operation);
	    //$paycorpRequest->setRequestDate(date('Y-m-d H:i:s'));
        $paycorpRequest->setRequestDate((new DateTime())->format(DateTime::ISO8601));
        $paycorpRequest->setValidateOnly($this->config->isValidateOnly());
        $paycorpRequest->setRequestData($requestData);

        $jsonRequest = $jsonHelper->toJson($paycorpRequest);
        return json_encode($jsonRequest);
    }

    private function buildResponse($response, $jsonHelper) {
        $paycorpResponse = $jsonHelper->fromJson(json_decode($response, TRUE));
        return $paycorpResponse;
    }

}
