<?php

declare(strict_types=1);

namespace League\OAuth2\Client\Exception;

class TokenRefreshException extends \Exception
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
