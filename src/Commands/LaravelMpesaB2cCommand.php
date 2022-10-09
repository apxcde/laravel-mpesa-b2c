<?php

namespace Apxcde\LaravelMpesaB2c\Commands;

use Illuminate\Console\Command;

class LaravelMpesaB2cCommand extends Command
{
    public $signature = 'laravel-mpesa-b2c';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
