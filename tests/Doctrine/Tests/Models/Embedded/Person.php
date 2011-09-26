<?php
namespace Doctrine\Tests\Models\Embedded;

/** @Document */
class Person
{
    /** @Id(strategy="ASSIGNED") */
    public $id;

    /** @Version */
    public $version;

    /** @String */
    public $name;

    /** @EmbedOne(targetDocument="Doctrine\Tests\Models\Embedded\Address") */
    public $address;

    /** @EmbedMany(targetDocument="Doctrine\Tests\Models\Embedded\Account") */
    public $accounts;
    
}