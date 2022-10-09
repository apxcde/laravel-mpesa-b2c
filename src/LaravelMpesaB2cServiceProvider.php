<?php

namespace Apxcde\LaravelMpesaB2c;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Apxcde\LaravelMpesaB2c\Commands\LaravelMpesaB2cCommand;

class LaravelMpesaB2cServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-mpesa-b2c')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-mpesa-b2c_table')
            ->hasCommand(LaravelMpesaB2cCommand::class);
    }
}
