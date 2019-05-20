<?php

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ReCaptcha;

/**
 * Class RequestParameters
 */
class RequestParameters
{
    /**
     * The shared key between your site and reCAPTCHA.
     * @var string
     */
    private $secret;

    /**
     * The user response token provided by reCAPTCHA, verifying the user on your site.
     * @var string
     */
    private $response;

    /**
     * Remote user's IP address.
     * @var string|null
     */
    private $remoteIp = null;

    /**
     * Client version.
     * @var string|null
     */
    private $version = null;

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
     * @return RequestParameters
     */
    public function setSecret(string $secret): RequestParameters
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * @param string $response
     *
     * @return RequestParameters
     */
    public function setResponse(string $response): RequestParameters
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteIp(): ?string
    {
        return $this->remoteIp;
    }

    /**
     * @param string|bool $remoteIp
     *
     * @return RequestParameters
     */
    public function setRemoteIp(?string $remoteIp): RequestParameters
    {
        $this->remoteIp = $remoteIp;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param string $version
     *
     * @return RequestParameters
     */
    public function setVersion(string $version): RequestParameters
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Array representation.
     *
     * @return array Array formatted parameters.
     */
    public function toArray(): array
    {
        $params = [
            'secret' => $this->getSecret(),
            'response' => $this->getResponse(),
            'remoteip' => $this->getRemoteIp(),
            'version' => $this->getVersion()
        ];

        return array_filter($params);
    }

    /**
     * Query string representation for HTTP request.
     *
     * @return string Query string formatted parameters.
     */
    public function toQueryString(): string
    {
        return http_build_query($this->toArray(), '', '&');
    }
}