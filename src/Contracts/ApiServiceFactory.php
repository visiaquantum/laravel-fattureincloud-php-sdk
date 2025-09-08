<?php

namespace Codeman\FattureInCloud\Contracts;

interface ApiServiceFactory
{
    public function make(string $serviceName): object;

    public function supports(string $serviceName): bool;
}
