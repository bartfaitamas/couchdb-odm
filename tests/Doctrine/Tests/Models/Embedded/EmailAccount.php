<?php

namespace Doctrine\Tests\Models\Embedded;

/** @EmbeddedDocument */
class EmailAccount extends Account
{
    /** @String */
    public $emailAddress;
}