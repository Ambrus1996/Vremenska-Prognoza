<?php

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Naš prvi, glupi test koji uvek prolazi.
     * Samo proverava da li je 'true' zaista 'true'.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}