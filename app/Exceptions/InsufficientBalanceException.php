<?php

namespace App\Exceptions;

class InsufficientBalanceException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(__('store.insufficient_balance'), 422, 'insufficient_balance');
    }
}
