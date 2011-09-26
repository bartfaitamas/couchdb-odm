<?php

namespace Doctrine\Tests\Models\Embedded;

/** MappedSuperclass */
/** @EmbeddedDocument */
abstract class Account
{
    /** @String */
    public $accountName;
}