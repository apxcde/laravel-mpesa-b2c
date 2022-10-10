<?php

namespace Apxcde\LaravelMpesaB2c;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMpesaB2cServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mpesa-b2c')
            ->hasConfigFile()
            ->hasMigration('create_mpesa_b2c_table');
    }
}
