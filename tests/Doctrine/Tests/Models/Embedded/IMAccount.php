<?php

namespace Doctrine\Tests\Models\Embedded;

/** @EmbeddedDocument */
class IMAccount extends Account
{
    /** @String */
    public $identifier;
}