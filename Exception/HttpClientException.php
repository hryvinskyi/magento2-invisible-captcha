<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Exception;

/**
 * Thrown by the bounded HTTP transport on any connection, timeout or non-2xx
 * failure during an outbound siteverify / assessment call.
 */
class HttpClientException extends \RuntimeException
{
}
