<?php

/*
 * This file is part of Eventbase API.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AdminBundle\Controller;

use AppBundle\Entity\Event;
use AppBundle\Entity\Group;
use AppBundle\Entity\Occurrence;
use AppBundle\Entity\Place;
use Doctrine\Common\Collections\ArrayCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Gedmo\Blameable\Blameable;

class AdminController extends BaseAdminController
{
    public function cloneEventAction()
    {
        $id = $this->request->query->get('id');
        $event = $this->em->getRepository('AppBundle:Event')->find($id);
        if ($event) {
            $clone = clone $event;
            $clone->setMaster($event);
            $this->em->persist($clone);
            $this->em->flush();

            $tranlator = $this->container->get('translator');
            $message = $tranlator->trans('Event %event_name% cloned', ['%event_name%' => $event->getName()]);
            $this->addFlash('info', $message);

            return $this->redirectToRoute('easyadmin', [
            'action' => 'edit',
            'id' => $clone->getId(),
            'entity' => $this->request->query->get('entity'),
            ]);
        }

        $refererUrl = $this->request->query->get('referer', '');

        return !empty($refererUrl)
        ? $this->redirect(urldecode($refererUrl))
        : $this->redirectToRoute('easyadmin', [
        'action' => 'list',
        'entity' => $this->request->query->get('entity'),
        ]);
    }

    public function createNewEventEntity()
    {
        $event = new Event();

        $event->setLangcode('da');
        $event->setOccurrences(new ArrayCollection([new Occurrence()]));

        return $event;
    }

    public function createNewGroupEntity()
    {
        return new Group('');
    }

    // @see https://github.com/javiereguiluz/EasyAdminBundle/blob/master/Resources/doc/tutorials/fosuserbundle-integration.md

    public function createNewUserEntity()
    {
        return $this->get('fos_user.user_manager')->createUser();
    }

    public function prePersistUserEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
    }

    public function preUpdateUserEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
    }

    public function prePersistEventEntity(Event $event)
    {
        $this->handleRepeatingOccurrences($event);
    }

    public function preUpdateEventEntity(Event $event)
    {
        $this->handleRepeatingOccurrences($event);
    }

    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null)
    {
        $this->limitByUser($dqlFilter, $entityClass, 'entity');

        return parent::createListQueryBuilder($entityClass, $sortDirection, $sortField, $dqlFilter);
    }

    protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        // Use only list fields in search query.
        $this->entity['search']['fields'] = array_filter($this->entity['search']['fields'], function ($key) {
            return isset($this->entity['list']['fields'][$key]);
        }, ARRAY_FILTER_USE_KEY);

        $this->limitByUser($dqlFilter, $entityClass, 'entity');

        return parent::createSearchQueryBuilder($entityClass, $searchQuery, $searchableFields, $sortField, $sortDirection, $dqlFilter);
    }

    private function limitByUser(string &$dqlFilter = null, string $entityClass, string $alias)
    {
        $limitByUserFilter = $this->getLimitByUserFilter($entityClass, $alias);
        if ($limitByUserFilter) {
            if ($dqlFilter) {
                $dqlFilter .= ' and '.$limitByUserFilter;
            } else {
                $dqlFilter = $limitByUserFilter;
            }
        }
    }

    private function getLimitByUserFilter(string $entityClass, string $alias)
    {
        // instanceof does not work with string as first operand.
        if (!is_subclass_of($entityClass, Blameable::class)) {
            return null;
        }

        $token = $this->get('security.token_storage')->getToken();
        $user = $token ? $token->getUser() : null;
        $filter = $this->request->get('_event_list_filter');
        switch ($filter) {
            case 'all':
                return null;
            case 'mine':
            case 'my':
                if ($user) {
                    return $alias.'.createdBy = '.$user->getId();
                }

                break;
            case 'editable':
                // @TODO: Use EditVoter to get editable events.
                return null;
        }

        return null;
    }

    private function handleRepeatingOccurrences(Event $event)
    {
        // Check that 'update_repeating_occurrences' submit button has been clicked.
        // @TODO: There must be a better way to do this.
        $values = $this->request->request->all();
        if (isset($values['event'], $values['event']['repeating_occurrences'], $values['event']['repeating_occurrences']['update_repeating_occurrences'])) {
            $repeatingOccurrences = $event->getRepeatingOccurrences();
            if ($repeatingOccurrences) {
                /** @var Place $place */
                $place = isset($repeatingOccurrences['place']) ? $repeatingOccurrences['place'] : null;
                if (!$place instanceof Place) {
                    $place = $this->em->getRepository(Place::class)->find($place);
                }
                $ticketPriceRange = isset($repeatingOccurrences['ticket_price_range']) ? $repeatingOccurrences['ticket_price_range'] : null;
                /** @var \DateTime $startDay */
                $startDay = isset($repeatingOccurrences['start_day']) ? clone $repeatingOccurrences['start_day'] : null;
                /** @var \DateTime $endDay */
                $endDay = isset($repeatingOccurrences['end_day']) ? clone $repeatingOccurrences['end_day'] : null;

                // $startDay is a UTC time and we convert it to the view timezone.
                $viewTimeZone = new \DateTimeZone($this->getParameter('view_timezone'));
                $startDay->setTimezone($viewTimeZone);
                $endDay->setTimezone($startDay->getTimeZone());

                $utc = new \DateTimeZone('UTC');

                if ($place && $startDay && $endDay && $startDay <= $endDay) {
                    $occurrences = new ArrayCollection();

                    $startDay->setTime(0, 0, 0);
                    $endDay->setTime(0, 0, 0);
                    while ($startDay <= $endDay) {
                        $day = $startDay->format('N');
                        $startTime = $repeatingOccurrences['start_time_'.$day];
                        $endTime = $repeatingOccurrences['end_time_'.$day];
                        if ($startTime && $endTime) {
                            $occurrence = new Occurrence();
                            $occurrence->setPlace($place);
                            $occurrence->setStartDate(clone $startDay);
                            $occurrence->getStartDate()->setTime($startTime->format('H'), $startTime->format('i'));
                            $occurrence->setEndDate(clone $startDay);
                            $occurrence->getEndDate()->setTime($endTime->format('H'), $endTime->format('i'));
                            $occurrence->setTicketPriceRange($ticketPriceRange);
                            // We store UTC dates in the database.
                            $occurrence->getStartDate()->setTimeZone($utc);
                            $occurrence->getEndDate()->setTimeZone($utc);
                            $occurrences[] = $occurrence;
                        }

                        $startDay->add(new \DateInterval('P1D'));
                    }

                    $event->getOccurrences()->clear();
                    $event->setOccurrences($occurrences);

                    $message = $event->getId()
                           ? sprintf('Updated %d occurrence(s)', count($occurrences))
                           : sprintf('Created %d occurrence(s)', count($occurrences));
                    $this->addFlash('info', $message);
                }
            }
        }
    }
}
