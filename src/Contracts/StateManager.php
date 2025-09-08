<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Contracts;

interface StateManager
{
    public function store(string $state): void;

    public function validate(string $state): bool;

    public function clear(): void;
}
