<?php

declare(strict_types=1);

namespace SamuelOlavo\XenOrchestra\Exceptions;

/**
 * HTTP 401/403 — the token is missing, expired, or lacks the required ACL.
 *
 * Worth checking XO_TOKEN, and remembering that non-admin users are limited by
 * ACL v2 / RBAC and may legitimately not see part of the API.
 */
class AuthenticationException extends RequestException
{
}
