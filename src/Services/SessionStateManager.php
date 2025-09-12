<?php

namespace Codeman\FattureInCloud\Services;

use Codeman\FattureInCloud\Contracts\StateManager as StateManagerContract;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

class SessionStateManager implements StateManagerContract
{
    private const STATE_KEY = 'fatture_in_cloud_oauth_state';

    public function __construct(
        private Session $session
    ) {}

    public function generateState(): string
    {
        return Str::random(40);
    }

    public function store(string $state): void
    {
        $this->session->put(self::STATE_KEY, $state);
    }

    public function validate(string $state): bool
    {
        $storedState = $this->session->get(self::STATE_KEY);

        return $storedState && hash_equals($storedState, $state);
    }

    public function validateState(string $state): bool
    {
        return $this->validate($state);
    }

    public function clear(): void
    {
        $this->session->forget(self::STATE_KEY);
    }
}
