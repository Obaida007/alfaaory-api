<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * A domain rule was violated (insufficient balance, used code, ...). Rendered as a 422 JSON error.
 */
class BusinessException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422, public readonly ?string $errorCode = null)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
        ], $this->status);
    }
}
