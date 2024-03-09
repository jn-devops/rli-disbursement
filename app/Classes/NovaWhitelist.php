<?php

namespace App\Classes;

class NovaWhitelist
{
    protected string $whitelist;

    public function __construct(string $whitelist)
    {
        $this->whitelist = strtolower($whitelist);
    }

    protected function toArray(): array
    {
        return explode(',', $this->whitelist);
    }

    public function allow(string $email): bool
    {
        $email = strtolower($email);

        return $this->whitelist == '*' || in_array($email, $this->toArray());
    }
}
