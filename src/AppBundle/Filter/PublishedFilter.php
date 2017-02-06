<?php

namespace AppBundle\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use AppBundle\Entity\Event;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 */
class PublishedFilter extends AbstractFilter {
  protected function extractProperties(Request $request): array {
    $properties = $request->query->all();

    if (!isset($properties['published'])) {
      $properties['published'] = true;
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = NULL) {
    if (NULL === ($request = $this->requestStack->getCurrentRequest())) {
      return;
    }

    $resource = new $resourceClass();
    if (!$resource instanceof Event) {
      return;
    }

    foreach ($this->extractProperties($request) as $property => $values) {
      if ($property == 'published') {
        $value = strcasecmp($value, 'false') !== 0 && boolval($value);
        $alias = 'o';
        $valueParameter = $queryNameGenerator->generateParameterName($property);
        $queryBuilder
          ->andWhere(sprintf('%s.isPublished = :%s', $alias, $valueParameter))
          ->setParameter($valueParameter, $value ? 1 : 0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(string $resourceClass) : array {
    return [
      'published' => [
        'property' => 'published',
        'type' => 'boolean',
        'required' => FALSE,
      ]
    ];
  }

}
