<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha;

use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\Validators\ValidatorInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha\Validators\ValidatorList;

/**
 * Class VerifyVerifyReCaptcha
 */
class VerifyReCaptcha
{
    /**
     * URL for reCAPTCHA site verify API
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
     * Not a success, but no error codes received!
     *
     * @const string
     */
    const E_UNKNOWN_ERROR = 'unknown-error';

    /**
     * Bad validator
     *
     * @const string
     */
    const BAD_VALIDATOR = 'bad-validator';

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
     * @var ValidatorList
     */
    private $verifyValidatorList = [];

    /**
     * VerifyReCaptcha constructor.
     *
     * @param RequestMethodInterface $requestMethod
     * @param RequestParameters $requestParameters
     * @param ValidatorList $verifyValidatorList
     */
    public function __construct(
        RequestMethodInterface $requestMethod,
        RequestParameters $requestParameters,
        ValidatorList $verifyValidatorList
    ) {
        $this->requestMethod = $requestMethod;
        $this->requestParameters = $requestParameters;
        $this->verifyValidatorList = $verifyValidatorList;
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

        foreach ($this->verifyValidatorList as $item) {
            if (!$item instanceof ValidatorInterface) {
                $validationErrors[] = self::BAD_VALIDATOR;
                continue;
            }

            if ($error = $item->validate($this, $initialResponse)) {
                $validationErrors[] = $error;
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
     * @return string
     */
    public function getExpectedHostname(): ?string
    {
        return $this->hostname;
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
     * @return string
     */
    public function getExpectedAction(): ?string
    {
        return $this->action;
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
     * @return float|null
     */
    public function getScoreThreshold(): ?float
    {
        return $this->threshold;
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

    /**
     * @return int
     */
    public function getChallengeTimeout(): ?int
    {
        return $this->timeoutSeconds;
    }
}
