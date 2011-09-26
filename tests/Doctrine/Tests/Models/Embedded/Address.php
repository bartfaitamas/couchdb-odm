<?php

namespace Doctrine\Tests\Models\Embedded;

/** @EmbeddedDocument */
class Address
{
    /** @String */
    public $country;
    /** @String */
    public $zip;
    /** @String */
    public $city;
    /** @String */
    public $street;

    /** @EmbedMany(targetDocument="Doctrine\Tests\Models\Embedded\PhoneNumber") */
    public $phoneNumbers;
}
