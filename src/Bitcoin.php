<?php

namespace CoinsFuse\Coins;

class Bitcoin extends CoinBackend
{
    public function __construct(string $username, string $password, string $host, bool $useTestNet = false) {
        parent::__construct($username, $password, $host, 8332, 18332, $useTestNet);
    }
}