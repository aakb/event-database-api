<?php

/*
 * This file is part of Eventbase API.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\EventListener;

use AdminBundle\Service\TagManager;
use AppBundle\Entity\Event;
use AppBundle\Entity\Thing;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use DoctrineExtensions\Taggable\Taggable;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventListener extends EditListener
{
    /**
     * @var TagManager
     */
    protected $tagManager;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->tagManager = $this->container->get('tag_manager');
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->normalize($args);
        $this->downloadFiles($args);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        parent::preUpdate($args);
        $this->normalize($args);
        $this->downloadFiles($args);
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof Taggable) {
            $this->tagManager->saveTagging($object);
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->postPersist($args);
    }

    public function postLoad(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof Taggable) {
            $this->tagManager->loadTagging($object);
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        parent::preRemove($args);
        $object = $args->getObject();
        if ($object instanceof Event) {
            $object->getOccurrences()->clear();
        }
    }

    /**
     * Normalize event description and excerpt.
     */
    private function normalize(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof Thing) {
            if ($this->container->has('description_normalizer')) {
                $description = $object->getDescription();
                $description = $this->container->get('description_normalizer')
                    ->normalize($description);
                $object->setDescription($description);
            }
        }
        if ($object instanceof Event) {
            if ($this->container->has('excerpt_normalizer')) {
                $excerpt = $object->getExcerpt() ?: $object->getDescription();
                $excerpt = $this->container->get('excerpt_normalizer')
                    ->normalize($excerpt);
                $object->setExcerpt($excerpt);
            }
        }
    }

    private function downloadFiles(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        if ($object instanceof Thing) {
            $this->container->get('download_files')
                ->downloadFiles($object, ['image']);
        }
    }
}
