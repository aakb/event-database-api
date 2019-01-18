<?php
/**
 * Created by PhpStorm.
 * User: turegjorup
 * Date: 2019-01-17
 * Time: 10:54
 */

namespace AppBundle\Service;

use AppBundle\Entity\DailyOccurrence;
use AppBundle\Entity\Occurrence;
use Doctrine\Common\Collections\ArrayCollection;

class OccurrenceSplitterService
{
    private $dateSeparatorTimezone;
    private $splitHour;
    private $splitMinute;
    private $splitSecond;

    /**
     * OccurrenceSplitterService constructor.
     *
     * @param string $dateSeparatorTime
     * @param string $dateSeparatorTimezone
     */
    public function __construct(string $dateSeparatorTime, string $dateSeparatorTimezone)
    {
        $exploded = explode(':', $dateSeparatorTime);
        $this->splitHour= (int) $exploded[0];
        $this->splitMinute= (int) $exploded[1];
        $this->splitSecond= (int) $exploded[2];
        $this->dateSeparatorTimezone = new \DateTimeZone($dateSeparatorTimezone);
    }

    /**
     * Get new DailyOccurrences from an Occurrence
     *
     * @param Occurrence $occurrence
     *
     * @return ArrayCollection
     *
     * @throws \Exception
     */
    public function getDailyOccurrences(Occurrence $occurrence): ArrayCollection
    {
        $dailyOccurrences = new ArrayCollection();
        $this->generateDailyOccurrences($occurrence, $dailyOccurrences);

        return $dailyOccurrences;
    }

    /**
     * Recursively generate DailyOccurrences from an Occurrence
     *
     * @param Occurrence $occurrence
     * @param ArrayCollection $dailyOccurrences
     *
     * @return array
     *
     * @throws \Exception
     */
    private function generateDailyOccurrences(Occurrence $occurrence, ArrayCollection $dailyOccurrences): ArrayCollection
    {
        $tempOccurrence = clone $occurrence;
        $split = $this->getFirstSplitDateTime($tempOccurrence->getStartDate());

        if ($tempOccurrence->getEndDate() < $split) {
            $dailyOccurrence = new DailyOccurrence($tempOccurrence);
            $dailyOccurrences->add($dailyOccurrence);
        } else {
            $dailyOccurrence = new DailyOccurrence($tempOccurrence);
            $dailyOccurrence->setEndDate($split);
            $dailyOccurrences->add($dailyOccurrence);

            $tempOccurrence->setStartDate($split);

            $this->generateDailyOccurrences($tempOccurrence, $dailyOccurrences);
        }

        return $dailyOccurrences;
    }

    /**
     * Get the first split DateTime for an Occurrence based on the configured split time
     *
     * @param \DateTime $dateTime
     *
     * @return \DateTime
     *
     * @throws \Exception
     */
    private function getFirstSplitDateTime(\DateTime $dateTime): \DateTime
    {
        $split = clone $dateTime;
        $split->setTimezone($this->dateSeparatorTimezone);
        $split->setTime($this->splitHour, $this->splitMinute, $this->splitSecond);

        if ($this->isAfterSplitTime($dateTime)) {
            $oneDay = new \DateInterval('P1D');
            $split->add($oneDay);
        }

        $split->setTimezone($dateTime->getTimezone());

        return $split;
    }

    /**
     * Compares if the time of the given datetime object is before the configured split time
     *
     * @param \DateTime $dateTime
     *
     * @return bool
     */
    private function isAfterSplitTime(\DateTime $dateTime): bool
    {
        $split = clone $dateTime;
        $split->setTimezone($this->dateSeparatorTimezone);
        $split->setTime($this->splitHour, $this->splitMinute, $this->splitSecond);

        return $dateTime >= $split;
    }
}