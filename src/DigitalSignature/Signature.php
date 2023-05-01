<?php

namespace Ebay\DigitalSignature;

use Mapper\ModelMapper;

class Signature {

    private $signatureConfig;
    private $signatureService;

    public function __construct($jsonConfig) {
        $this->loadSignatureConfig($jsonConfig);
        $this->signatureService = new SignatureService();
    }

    /**
     * Returns an array with all required headers. Add this to your HTTP request
     *
     * @param array $headers request headers
     * @param string $endpoint URI of the request
     * @param string $method POST, GET, PUT, etc.
     * @param string|null $body body
     * @return array All headers including the initially transmitted
     */
    public function generateSignatureHeaders(array $headers, string $endpoint, string $method, string $body = null): array {
        $contains_body = !is_null($body);
        if ($contains_body === true) {
            $headers["Content-Digest"] = $this->signatureService->generateContentDigest($body, $this->signatureConfig);
        }
        $timestamp = time();
        $headers["x-ebay-signature-key"] = $this->signatureService->generateSignatureKey($this->signatureConfig);
        $headers["Signature-Input"] = $this->signatureService->generateSignatureInput($contains_body, $timestamp, $this->signatureConfig);
        $headers["Signature"] = $this->signatureService->generateSignature($contains_body, $headers, $method, $endpoint, $timestamp, $this->signatureConfig);

        return $headers;
    }

    /**
     * Load config value into SignatureConfig Object
     *
     * @param string $configPath config path
     */
    private function loadSignatureConfig($jsonConfig): void {
        $mapper = new ModelMapper();
        $this->signatureConfig = new SignatureConfig();
        $mapper->map($jsonConfig, $this->signatureConfig);
        
        $this->signatureConfig->signingKeyCipher = "sha-256";
        if(empty($this->signatureConfig->signatureParams)){
            $this->signatureConfig->signatureParams = [
              "content-digest",
              "x-ebay-signature-key",
              "@method",
              "@path",
               "@authority"    
            ];
        }
    }
}
