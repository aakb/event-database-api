<?php

namespace AdminBundle\Service;

use AdminBundle\Entity\Feed;
use AdminBundle\Service\FeedReader\Controller;
use AdminBundle\Service\FeedReader\EventImporter;
use AdminBundle\Service\FeedReader\ValueConverter;
use AppBundle\Entity\User;
use Gedmo\Blameable\BlameableListener;
use GuzzleHttp\Client;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class FeedReader implements Controller {
  protected $eventImporter;
  protected $valueConverter;
  protected $configuration;
  protected $logger;
  protected $tokenStorage;
  protected $blameableListener;

  /**
   * @var AdminBundle\Entity\Feed
   */
  protected $feed;
  protected $output;

  public function __construct(ValueConverter $valueConverter, EventImporter $eventImporter, array $configuration, Logger $logger, TokenStorage $tokenStorage, BlameableListener $blameableListener) {
    $this->eventImporter = $eventImporter;
    $this->valueConverter = $valueConverter;
    $this->configuration = $configuration;
    $this->logger = $logger;
    $this->tokenStorage = $tokenStorage;
    $this->blameableListener = $blameableListener;
  }

  public function setOutput(OutputInterface $output) {
    $this->output = $output;
  }

  public function read(Feed $feed, User $user = null) {
    $this->feed = $feed;
    if (!$user) {
      $user = $this->feed->getCreatedBy();
    }
    $this->authenticate($user);
    $this->eventImporter
      ->setFeed($feed)
      ->setUser($user)
      ->setLogger($this->logger);
    $this->valueConverter->setFeed($feed);

    if ($user) {
      // Tell Blameable which user is creating entities.
      if ($this->blameableListener) {
        $this->blameableListener->setUserValue($user);
      }
    }

    $reader = $this->getReader();
    $content = $this->getContent();
    $reader->read($content);
    $feed->setLastRead(new \DateTime());
  }

  private function authenticate(User $user) {
    $token = new UsernamePasswordToken($user, null, 'main');
    $this->tokenStorage->setToken($token);
  }

  private function getReader() {
    $readers = isset($this->configuration['readers']) ? $this->configuration['readers'] : [];
    $type = $this->feed->getType();

    if (!isset($readers[$type])) {
      throw new \Exception('Unknown feed type: ' . $type);
    }

    $reader = $readers[$type];
    $reader
      ->setController($this)
      ->setFeed($this->feed);

    return $reader;
  }

  private function getContent() {
    $client = new Client();
    $feedUrl = $this->processUrl($this->feed->getUrl());

    $configuration = $this->feed->getConfiguration();
    $method = isset($configuration['method']) ? $configuration['method'] : 'GET';
    $options = isset($configuration['options']) ? $configuration['options'] : [];

    $res = $client->request($method, $feedUrl, $options);
    if ($res->getStatusCode() === 200) {
      $content = $res->getBody();
      // http://stackoverflow.com/questions/10290849/how-to-remove-multiple-utf-8-bom-sequences-before-doctype
      $bom = pack('H*','EFBBBF');
      $content = preg_replace("/^$bom/", '', $content);
    }

    $type = $this->feed->getType();
    switch ($type) {
      case 'json':
        $content = json_decode($content, true);
        break;

      case 'xml':
        $content = new \SimpleXmlElement($content);
        break;
    }

    return $content;
  }

  private function processUrl($url) {
    return $url;
  }

  public function createEvent(array $data) {
    $data['feed'] = $this->feed;
    $data['feed_event_id'] = $data['id'];
    $event = $this->eventImporter->import($data);

    $status = ($event->getUpdatedAt() > $event->getCreatedAt()) ? 'updated' : 'created';
    $this->writeln(sprintf('% 8d %s: Event %s: %s (%s)', $this->feed->getId(), $this->feed->getName(), $status, $event->getName(), $event->getFeedEventId()));
  }

  public function convertValue($value, $name) {
    return $this->valueConverter->convert($value, $name);
  }

  public function writeln($messages, $options = 0) {
    if ($this->output) {
      $this->output->writeln($messages, $options);
    }
  }
}
