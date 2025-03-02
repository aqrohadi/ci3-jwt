<?php

namespace Firebase\JWT;

use InvalidArgumentException;
use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use TypeError;

class Key
{
	private $keyMaterial;
    private $algorithm;

    public function __construct($keyMaterial, $algorithm) {
        $this->keyMaterial = $keyMaterial;
        $this->algorithm = $algorithm;
    }

    public function getKeyMaterial() {
        return $this->keyMaterial;
    }

    public function getAlgorithm() {
        return $this->algorithm;
    }
}
