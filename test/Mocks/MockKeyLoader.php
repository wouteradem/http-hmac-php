<?php

namespace WouterAdem\Hmac\Test\Mocks;

use WouterAdem\Hmac\Key;
use WouterAdem\Hmac\KeyLoaderInterface;

class MockKeyLoader implements KeyLoaderInterface
{
    protected $keys = [];

    public function __construct(array $keys = [])
    {
        $this->keys = $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function load($id)
    {
        if (!isset($this->keys[$id])) {
            return false;
        }

        return new Key($id, $this->keys[$id]);
    }
}
