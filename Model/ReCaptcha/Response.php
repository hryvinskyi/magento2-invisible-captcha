<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha;

use Hryvinskyi\Base\Helper\ArrayHelper;
use Hryvinskyi\Base\Helper\Json;

/**
 * The response returned from the service.
 */
class Response
{
    /**
     * Success or failure.
     * @var boolean
     */
    private $success = false;

    /**
     * Error code strings.
     *
     * @var array
     */
    private $errorCodes = [];

    /**
     * The hostname of the site where the reCAPTCHA was solved.
     *
     * @var string
     */
    private $hostname;

    /**
     * Timestamp of the challenge load (ISO format yyyy-MM-dd'T'HH:mm:ssZZ)
     *
     * @var string
     */
    private $challengeTs;

    /**
     * Score assigned to the request
     *
     * @var float
     */
    private $score;

    /**
     * Action as specified by the page
     *
     * @var string
     */
    private $action;

    /**
     * Build the response from the expected JSON returned by the service.
     *
     * @param string $json
     *
     * @return Response
     */
    public static function fromJson($json): Response
    {
        $responseData = Json::decode($json);

        if (!$responseData) {
            return new Response(false, [VerifyReCaptcha::E_INVALID_JSON]);
        }

        $hostname = ArrayHelper::getValue($responseData, 'hostname');
        $challengeTs = ArrayHelper::getValue($responseData, 'challenge_ts');
        $score = floatval(ArrayHelper::getValue($responseData, 'score'));
        $action = ArrayHelper::getValue($responseData, 'action');
        $success = ArrayHelper::getValue($responseData, 'success');
        $errorCodes = ArrayHelper::getValue($responseData, 'error-codes');

        if ($success == true) {
            return new Response(true, [], $hostname, $challengeTs, $score, $action);
        }

        if ($errorCodes && is_array($errorCodes)) {
            return new Response(
                false,
                $responseData['error-codes'],
                $hostname,
                $challengeTs,
                $score,
                $action
            );
        }

        return new Response(
            false,
            [VerifyReCaptcha::E_UNKNOWN_ERROR],
            $hostname,
            $challengeTs,
            $score,
            $action
        );
    }

    /**
     * Response constructor.
     *
     * @param bool $success
     * @param array $errorCodes
     * @param string|null $hostname
     * @param string|null $challengeTs
     * @param float|null $score
     * @param string|null $action
     */
    public function __construct(
        bool $success,
        array $errorCodes = [],
        ?string $hostname = null,
        ?string $challengeTs = null,
        ?float $score = null,
        ?string $action = null
    ) {
        $this->success = $success;
        $this->hostname = $hostname;
        $this->challengeTs = $challengeTs;
        $this->score = $score;
        $this->action = $action;
        $this->errorCodes = $errorCodes;
    }

    /**
     * Is success?
     *
     * @return boolean
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Get error codes.
     *
     * @return array
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    /**
     * Get hostname.
     *
     * @return string
     */
    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    /**
     * Get challenge timestamp
     *
     * @return string
     */
    public function getChallengeTs(): ?string
    {
        return $this->challengeTs;
    }

    /**
     * Get score
     *
     * @return float
     */
    public function getScore(): ?float
    {
        return $this->score;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success'      => $this->isSuccess(),
            'hostname'     => $this->getHostname(),
            'challenge_ts' => $this->getChallengeTs(),
            'score'        => $this->getScore(),
            'action'       => $this->getAction(),
            'error-codes'  => $this->getErrorCodes(),
        ];
    }
}
