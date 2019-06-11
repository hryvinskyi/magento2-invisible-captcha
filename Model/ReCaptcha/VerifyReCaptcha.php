<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha;

use Exception;
use Hryvinskyi\Base\Helper\Json;

/**
 * Class VerifyVerifyReCaptcha
 */
class VerifyReCaptcha
{
    /**
     * URL for reCAPTCHA sitevrerify API
     *
     * @const string
     */
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Invalid JSON received
     *
     * @const string
     */
    const E_INVALID_JSON = 'invalid-json';

    /**
     * Could not connect to service
     *
     * @const string
     */
    const E_CONNECTION_FAILED = 'connection-failed';

    /**
     * Did not receive a 200 from the service
     *
     * @const string
     */
    const E_BAD_RESPONSE = 'bad-response';

    /**
     * Not a success, but no error codes received!
     *
     * @const string
     */
    const E_UNKNOWN_ERROR = 'unknown-error';

    /**
     * ReCAPTCHA response not provided
     *
     * @const string
     */
    const E_MISSING_INPUT_RESPONSE = 'missing-input-response';

    /**
     * Expected hostname did not match
     *
     * @const string
     */
    const E_HOSTNAME_MISMATCH = 'hostname-mismatch';

    /**
     * Expected action did not match
     *
     * @const string
     */
    const E_ACTION_MISMATCH = 'action-mismatch';

    /**
     * Score threshold not met
     *
     * @const string
     */
    const E_SCORE_THRESHOLD_NOT_MET = 'score-threshold-not-met';

    /**
     * Challenge timeout
     *
     * @const string
     */
    const E_CHALLENGE_TIMEOUT = 'challenge-timeout';

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var string
     */
    private $action;

    /**
     * @var float
     */
    private $threshold;

    /**
     * @var int
     */
    private $timeoutSeconds;

    /**
     * Shared secret for the site.
     * @var string
     */
    private $secret;

    /**
     * Method used to communicate with service. Defaults to POST request.
     *
     * @var RequestMethodInterface
     */
    private $requestMethod;

    /**
     * @var RequestParameters
     */
    private $requestParameters;

    /**
     * VerifyReCaptcha constructor.
     *
     * @param RequestMethodInterface $requestMethod
     * @param RequestParameters $requestParameters
     */
    public function __construct(
        RequestMethodInterface $requestMethod,
        RequestParameters $requestParameters
    ) {
        $this->requestMethod = $requestMethod;
        $this->requestParameters = $requestParameters;
    }

    /**
     * Calls the reCAPTCHA siteverify API to verify whether the user passes
     * CAPTCHA test and additionally runs any specified additional checks
     *
     * @param string $response The user response token provided by reCAPTCHA, verifying the user on your site.
     * @param string $remoteIp The end user's IP address.
     *
     * @return Response
     */
    public function verify($response, $remoteIp = null): Response
    {
        $params = $this->requestParameters
            ->setSecret($this->getSecret())
            ->setResponse((string)$response)
            ->setRemoteIp($remoteIp);

        $answer = $this->requestMethod->submit(self::SITE_VERIFY_URL, $params);
        $initialResponse = Response::fromJson($answer);
        $validationErrors = [];

        if (
            isset($this->hostname)
            && $initialResponse->getHostname()
            && strcasecmp($this->hostname, $initialResponse->getHostname()) !== 0
        ) {
            $validationErrors[] = self::E_HOSTNAME_MISMATCH;
        }

        if (
            isset($this->action)
            && $initialResponse->getAction()
            && strcasecmp($this->action, $initialResponse->getAction()) !== 0
        ) {
            $validationErrors[] = self::E_ACTION_MISMATCH;
        }

        if (
            isset($this->threshold)
            && $initialResponse->getScore()
            && $this->threshold > $initialResponse->getScore()
        ) {
            $validationErrors[] = self::E_SCORE_THRESHOLD_NOT_MET;
        }

        if (isset($this->timeoutSeconds) && $initialResponse->getChallengeTs()) {
            $challengeTs = strtotime($initialResponse->getChallengeTs());

            if ($challengeTs > 0 && time() - $challengeTs > $this->timeoutSeconds) {
                $validationErrors[] = self::E_CHALLENGE_TIMEOUT;
            }
        }

        if (empty($validationErrors)) {
            return $initialResponse;
        }

        return new Response(
            false,
            array_merge($initialResponse->getErrorCodes(), $validationErrors),
            $initialResponse->getHostname(),
            $initialResponse->getChallengeTs(),
            $initialResponse->getScore(),
            $initialResponse->getAction()
        );
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     *
     * @return VerifyReCaptcha
     */
    public function setSecret(string $secret): VerifyReCaptcha
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Provide a hostname to match against in verify()
     * This should be without a protocol or trailing slash, e.g. www.google.com
     *
     * @param string $hostname Expected hostname
     *
     * @return VerifyReCaptcha
     */
    public function setExpectedHostname($hostname): VerifyReCaptcha
    {
        $this->hostname = $hostname;

        return $this;
    }

    /**
     * Provide an action to match against in verify()
     * This should be set per page.
     *
     * @param string $action Expected action
     *
     * @return VerifyReCaptcha
     */
    public function setExpectedAction($action): VerifyReCaptcha
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Provide a threshold to meet or exceed in verify()
     * Threshold should be a float between 0 and 1 which will be tested as response >= threshold.
     *
     * @param float $threshold Expected threshold
     *
     * @return VerifyReCaptcha
     */
    public function setScoreThreshold($threshold): VerifyReCaptcha
    {
        $this->threshold = floatval($threshold);

        return $this;
    }

    /**
     * Provide a timeout in seconds to test against the challenge timestamp in verify()
     *
     * @param int $timeoutSeconds Expected hostname
     *
     * @return VerifyReCaptcha
     */
    public function setChallengeTimeout(int $timeoutSeconds): VerifyReCaptcha
    {
        $this->timeoutSeconds = $timeoutSeconds;

        return $this;
    }
}
