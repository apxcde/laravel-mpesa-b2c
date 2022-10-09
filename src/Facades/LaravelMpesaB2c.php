<?php

namespace Apxcde\LaravelMpesaB2c\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Apxcde\LaravelMpesaB2c\LaravelMpesaB2c
 */
class LaravelMpesaB2c extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Apxcde\LaravelMpesaB2c\LaravelMpesaB2c::class;
    }
}
