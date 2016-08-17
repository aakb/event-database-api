<?php

namespace AppBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use DoctrineExtensions\Taggable\Taggable;
use FPN\TagBundle\Entity\TagManager;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\MappedSuperclass
 * @ ApiResource(iri="http://schema.org/Thing")
 */
abstract class Thing extends Entity
{
  /**
   * @var string A short description of the item.
   *
   * @Groups({"event_read", "event_write"})
   * @ORM\Column(nullable=true)
   * @Assert\Type(type="string")
   * @ApiProperty(iri="https://schema.org/description")
   */
  private $description;

  /**
   * @var string The name of the item.
   *
   * @Groups({"event_read", "event_write"})
   * @ORM\Column(nullable=true)
   * @Assert\Type(type="string")
   * @ApiProperty(iri="http://schema.org/image")
   */
  private $image;

  /**
   * @var string The name of the item.
   *
   * @Groups({"event_read", "event_write"})
   * @ORM\Column(nullable=true)
   * @Assert\Type(type="string")
   * @ApiProperty(iri="https://schema.org/name")
   */
  private $name;

  /**
   * @var string The name of the item.
   *
   * @Groups({"event_read", "event_write"})
   * @ORM\Column(nullable=true)
   * @Assert\Type(type="string")
   * @ApiProperty(iri="http://schema.org/url")
   */
  private $url;

  /**
   * Sets description.
   *
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description)
  {
    $this->description = $description;

    return $this;
  }

  /**
   * Gets description.
   *
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }

  /**
   * Sets image.
   *
   * @param string $image
   *
   * @return $this
   */
  public function setImage($image)
  {
    $this->image = $image;

    return $this;
  }

  /**
   * Gets image.
   *
   * @return string
   */
  public function getImage()
  {
    return $this->image;
  }

  /**
   * Sets name.
   *
   * @param string $name
   *
   * @return $this
   */
  public function setName($name)
  {
    $this->name = $name;

    return $this;
  }

  /**
   * Gets name.
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * Sets url.
   *
   * @param string $url
   *
   * @return $this
   */
  public function setUrl($url)
  {
    $this->url = $url;

    return $this;
  }

  /**
   * Gets url.
   *
   * @return string
   */
  public function getUrl()
  {
    return $this->url;
  }

}
