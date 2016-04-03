<?php
/**
 * Created by PhpStorm.
 * User: Joe Alamo
 * Date: 03/04/2016
 * Time: 15:48
 */

namespace BiometricSite\Service;

use BiometricSite\Repository\BioAuthSessionRepositoryInterface;
use BiometricSite\Repository\BioClientRepositoryInterface;
use BiometricSite\Repository\BioSessionRepositoryInterface;
use BiometricSite\Repository\PrevClientRandomRepositoryInterface;

abstract class AbstractBioAuthService {
    const BIO_AUTH_EXPIRY_TIME = 30;
    const SERVER_ID = "BFYZ5cGtO9SsqEzuUrWu7g~~";

    protected $bioClientRepository;
    protected $bioSessionRepository;
    protected $bioAuthSessionRepository;

    public function __construct(
        BioClientRepositoryInterface $bioClientRepository,
        BioSessionRepositoryInterface $bioSessionRepository,
        BioAuthSessionRepositoryInterface $bioAuthSessionRepository
    ) {
        $this->bioClientRepository = $bioClientRepository;
        $this->bioSessionRepository = $bioSessionRepository;
        $this->bioAuthSessionRepository = $bioAuthSessionRepository;
    }

    /**
     * @param $client_id
     * @return bool
     */
    protected function verifyClientIdNotMalformed($client_id) {
        if (!$client_id) {
            return false;
        }

        if (strlen($this->base64_url_decode($client_id)) !== 16) {
            return false;
        }

        return true;
    }

    /**
     * @param $client_id
     * @return \BiometricSite\Model\BiometricClient|false
     */
    protected function verifyClientIdBelongsToValidClient($client_id) {
        return $this->bioClientRepository->findByClientId($client_id);
    }

    /**
     * @param $session_id
     * @return bool
     */
    protected function verifySessionIdNotMalformed($session_id) {
        if (!$session_id) {
            return false;
        }

        if (strlen($this->base64_url_decode($session_id)) !== 16) {
            return false;
        }

        return true;
    }

    /**
     * @param $session_id
     * @return \BiometricSite\Model\BiometricSession|false
     */
    protected function verifySessionIdBelongsToValidSession($session_id) {
        return $this->bioSessionRepository->findBySessionId($session_id);
    }

    /**
     * @return string
     */
    protected function generateUnusedSessionId() {
        do {
            // Generate session_id securely
            $cryptographicallySecure = false;
            $session_id = $this->base64_url_encode(openssl_random_pseudo_bytes(16, $cryptographicallySecure));
            if ($cryptographicallySecure === false) {
                throw new \UnexpectedValueException("System is not cryptographically secure", 500);
            }
            // See if session_id has already been used for a session
            $session_idAlreadyExists = $this->bioSessionRepository->findBySessionId($session_id);
        } while ($session_idAlreadyExists !== false);

        return $session_id;
    }

    /**
     * @desc URL safe b64 encode
     * @param $input
     * @return string
     */
    protected function base64_url_encode($input) {
        return strtr(base64_encode($input), '+/=', '-_~');
    }

    /**
     * @desc URL safe b64 decode
     * @param $input
     * @return string
     */
    protected function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_~', '+/='));
    }

    protected function cryptoSecureCompare($stringA, $stringB) {
        if (!is_string($stringA) || !is_string($stringB)) {
            return false;
        }

        $stringLen = strlen($stringA);
        if ($stringLen !== strlen($stringB)) {
            return false;
        }

        $differences = 0;
        for ($i = 0; $i < $stringLen; $i++) {
            // XOR byte by byte, if different increment $differences
            $differences |= ord($stringA[$i]) ^ ord($stringB[$i]);
        }

        return $differences === 0;
    }
} 