<?php

namespace Doctrine\Tests\ODM\CouchDB\Functional;

class PreUpdateSubscriber implements \Doctrine\Common\EventSubscriber
{
    public $eventArgs = array();
    public function getSubscribedEvents()
    {
        return array(\Doctrine\ODM\CouchDB\Event::preUpdate);
    }

    public function preUpdate(\Doctrine\ODM\CouchDB\Event\LifecycleEventArgs $args)
    {
        $this->eventArgs[] = $args;
    }

}
