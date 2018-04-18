<?php

/*
 * This file is part of Eventbase API.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AdminBundle\Service;

use AdminBundle\Entity\Feed;
use AdminBundle\Service\FeedPreviewer\EventImporter;
use AdminBundle\Service\FeedReader\ValueConverter;
use AppBundle\Entity\User;
use Gedmo\Blameable\BlameableListener;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class FeedPreviewer extends FeedReader
{
    /**
     * @var \AdminBundle\Service\FeedPreviewer\EventImporter
     */
    protected $eventImporter;

    protected $events = [];

    /**
     * @param \AdminBundle\Service\FeedReader\ValueConverter $valueConverter
     * @param \AdminBundle\Service\FeedReader\EventImporter  $eventImporter
     * @param array                                          $configuration
     * @param \Psr\Log\LoggerInterface                       $logger
     * @param \AdminBundle\Service\AuthenticatorService      $authenticator
     * @param \Gedmo\Blameable\BlameableListener             $blameableListener
     */
    public function __construct(ValueConverter $valueConverter, array $configuration, LoggerInterface $logger, AuthenticatorService $authenticator, BlameableListener $blameableListener, ManagerRegistry $managerRegistry)
    {
        $this->eventImporter = new EventImporter();
        parent::__construct($valueConverter, $this->eventImporter, $configuration, $logger, $authenticator, $blameableListener, $managerRegistry);
        $this->authenticator = null;
    }

    /**
     * @param \AdminBundle\Entity\Feed $feed
     */
    public function read(Feed $feed, User $user = null, bool $cleanUpEvents = false)
    {
        $this->events = [];
        parent::read($feed, null, false);
    }

    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param array $data
     */
    public function createEvent(array $data)
    {
        $data['feed'] = $this->feed;
        $data['feed_event_id'] = $data['id'];
        $event = $this->eventImporter->import($data);

        unset($event['feed'], $event['feed_event_id']);

        $this->events[] = $event;
    }
}
