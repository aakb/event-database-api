<?php

/*
 * This file is part of Eventbase API.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AdminBundle\Command\FeedCommand;

use AdminBundle\Entity\Feed;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class FeedCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

    protected function configure()
    {
        $this
        ->addOption('list', null, InputOption::VALUE_NONE, 'List all feeds');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if ($input->getOption('list')) {
            $this->listFeeds();
            exit;
        }
    }

    protected function listFeeds()
    {
        $feeds = $this->getFeeds(null, null, true);
        foreach ($feeds as $feed) {
            $this->writeln(str_repeat('-', 80));
            $this->writeFeedInfo($feed);
            $this->writeln(str_repeat('-', 80));
        }
    }

    protected function writeFeedInfo(Feed $feed)
    {
        $this->writeln([
        'name:    '.$feed->getName(),
        'id:      '.$feed->getId(),
        'enabled: '.($feed->getEnabled() ? 'yes' : 'no'),
        'url:     '.$feed->getUrl(),
        'user:    '.$feed->getUser(),
        ]);
    }

    protected function writeln($messages)
    {
        $this->write($messages, true);
    }

    protected function write($messages, $newline = false)
    {
        if ($this->output) {
            $this->output->write($messages, $newline);
        }
    }

    /**
     * Get feeds.
     *
     * @param mixed $ids
     * @param mixed $names
     * @param mixed $getAll
     */
    protected function getFeeds($ids, $names, $getAll = false)
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository('AdminBundle:Feed');
        $query = [];
        if ($ids) {
            $query['id'] = $ids;
        }
        if ($names) {
            $query['name'] = $names;
        }
        if (!$getAll) {
            $query['enabled'] = true;
        }

        return $repository->findBy($query);
    }
}
