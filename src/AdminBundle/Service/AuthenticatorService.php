<?php

/*
 * This file is part of Eventbase API.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AdminBundle\Service;

use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Service to set a user as authenticated.
 */
class AuthenticatorService
{
    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var array
     */
    private $configuration;

    /**
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
     * @param array                                                                               $configuration
     */
    public function __construct(TokenStorageInterface $tokenStorage, array $configuration)
    {
        $this->tokenStorage = $tokenStorage;
        $this->configuration = $configuration;
    }

    /**
     * @param \AppBundle\Entity\User $user
     */
    public function authenticate(User $user)
    {
        $firewall = isset($this->configuration['firewall']) ? $this->configuration['firewall'] : 'main';
        $token = new UsernamePasswordToken($user, null, $firewall);
        $this->tokenStorage->setToken($token);
    }
}
