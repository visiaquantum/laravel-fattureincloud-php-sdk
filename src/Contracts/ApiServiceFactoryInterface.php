<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Contracts;

interface ApiServiceFactoryInterface
{
    public function make(string $serviceName): object;

    public function supports(string $serviceName): bool;
}
