<?php

namespace Codeman\LaravelFattureInCloudPhpSdk\Services;

use Codeman\LaravelFattureInCloudPhpSdk\Contracts\StateManagerInterface;
use Illuminate\Contracts\Session\Session;

class StateManager implements StateManagerInterface
{
    private const STATE_KEY = 'fatture_in_cloud_oauth_state';

    public function __construct(
        private Session $session
    ) {}

    public function store(string $state): void
    {
        $this->session->put(self::STATE_KEY, $state);
    }

    public function validate(string $state): bool
    {
        $storedState = $this->session->get(self::STATE_KEY);

        return $storedState && hash_equals($storedState, $state);
    }

    public function clear(): void
    {
        $this->session->forget(self::STATE_KEY);
    }
}
