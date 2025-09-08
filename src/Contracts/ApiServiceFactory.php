<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Contracts;

interface ApiServiceFactory
{
    public function make(string $serviceName): object;

    public function supports(string $serviceName): bool;
}
