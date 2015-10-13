<?php

namespace TreeHouse\IoBundle\Item\Modifier\Data\Transformer;

use TreeHouse\Feeder\Modifier\Data\Transformer\StringToDateTimeTransformer as FallbackTransformer;
use TreeHouse\Feeder\Modifier\Data\Transformer\TransformerInterface;

class DutchStringToDateTimeTransformer implements TransformerInterface
{
    protected $monthNames = [
        1 => 'januari',
        2 => 'februari',
        3 => 'maart',
        4 => 'april',
        5 => 'mei',
        6 => 'juni',
        7 => 'juli',
        8 => 'augustus',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    ];

    /**
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $monthRegex;

    /**
     * @param array         $monthNames
     * @param \DateTimeZone $timezone
     */
    public function __construct(array $monthNames = [], \DateTimeZone $timezone = null)
    {
        if (!empty($monthNames)) {
            $this->monthNames = $monthNames;
        }

        $this->monthRegex = implode('|', array_values($this->monthNames));
        $this->timezone = $timezone ?: new \DateTimeZone('UTC');
    }

    /**
     * @inheritdoc
     */
    public function transform($value)
    {
        if (is_null($value) || empty($value)) {
            return null;
        }

        if ($value instanceof \DateTime) {
            return $value;
        }

        // "per direct" => now
        if (preg_match('/(per )?dire(c|k)t/i', $value) || preg_match('/(gelijk|heden)/i', $value)) {
            return new \DateTime('now', $this->timezone);
        }

        // [sinds] 2 [mnd|maand|maanden]
        if (preg_match('/^(sinds\s)?(?P<months>\d+)\s(maand|maanden|mnd)\s?\+?/i', $value, $matches)) {
            return new \DateTime(sprintf('-%s months', $matches['months']), $this->timezone);
        }

        // [12] oktober 2012|'12
        $regex = '/^(?P<day>\d*\s?)?(?P<month>' . $this->monthRegex . ')\s+(?P<year>\d{4}|\\\'\d{2})/i';
        if (preg_match($regex, $value, $matches)) {
            $year = $matches['year'];
            $month = array_search(mb_strtolower($matches['month']), $this->monthNames);
            $day = (isset($matches['day']) && ($matches['day'] !== '')) ? $matches['day'] : 1;

            return $this->createDate($year, $month, $day);
        }

        // 12 oktober [2012|'12]
        $regex = '/^(?P<day>\d{1,2})\s?(?P<month>' . $this->monthRegex . ')(\s+(?P<year>\d{4}|\\\'\d{2}))?/i';
        if (preg_match($regex, $value, $matches)) {
            $year = isset($matches['year']) ? $matches['year'] : date('Y');
            $month = array_search(mb_strtolower($matches['month']), $this->monthNames);
            $day = $matches['day'];

            return $this->createDate($year, $month, $day);
        }

        // oktober [12|2012]
        $regex = '/^(?P<month>' . $this->monthRegex . ')(\s+(?P<day_or_year>\d{4}|\d{1,2}))?/i';
        if (preg_match($regex, $value, $matches)) {
            $month = array_search(mb_strtolower($matches['month']), $this->monthNames);
            $year = date('Y');
            $day = 1;

            if (isset($matches['day_or_year'])) {
                if (strlen($matches['day_or_year']) === 2) {
                    $day = $matches['day_or_year'];
                } elseif (strlen($matches['day_or_year']) === 4) {
                    $year = $matches['day_or_year'];
                }
            }

            return $this->createDate($year, $month, $day);
        }

        // [begin|eind] mei [2013]
        $regex = '/^(?P<day>begin|eind)?\s?(?P<month>' . $this->monthRegex . ')(?P<year>\s+\d{4})?/i';
        if (preg_match($regex, $value, $matches)) {
            $year = isset($matches['year']) ? $matches['year'] : date('Y');
            $month = array_search(mb_strtolower($matches['month']), $this->monthNames);
            if (isset($matches['day'])) {
                $day = $matches['day'] === 'begin' ? 5 : 25;
            } else {
                $day = 1;
            }

            return $this->createDate($year, $month, $day);
        }

        $regexes = [
            // 12-10-[2012|'12]
            '/^(?P<day>\d{1,2})[\-\/](?P<month>\d{1,2})[\-\/](?P<year>\d{4}|\\\'?\d{2})/i' => [null, null, null],
            // 2012-10-26
            '/^(?P<year>\d{4})[\-\/](?P<month>\d{1,2})[\-\/](?P<day>\d{1,2})/i' => [null, null, null],
            // 2012-10 (defaults the day to 1)
            '/^(?P<year>\d{4})[\-\/](?P<month>\d{1,2})/i' => [null, null, 1],
            // 2012 (defaults to jan. 1st)
            '/^(?P<year>\d{4})/i' => [null, 1, 1],
        ];

        foreach ($regexes as $regex => $defaults) {
            if (preg_match($regex, $value, $matches)) {
                list($defaultYear, $defaultMonth, $defaultDay) = $defaults;
                $year = isset($matches['year'])  ? $matches['year']  : $defaultYear;
                $month = isset($matches['month']) ? $matches['month'] : $defaultMonth;
                $day = isset($matches['day'])   ? $matches['day']   : $defaultDay;

                return $this->createDate($year, $month, $day);
            }
        }

        // check if strtotime matches
        if (false !== strtotime($value)) {
            return new \DateTime($value, $this->timezone);
        }

        // 26/03/2013
        if (preg_match('/^(?P<day>\d{1,2})\/(?P<month>\d{1,2})\/(?<year>\d{4})/i', $value, $matches)) {
            return $this->createDate($matches['year'], $matches['month'], $matches['day']);
        }

        // last resort
        $transformer = new FallbackTransformer('d-m-Y H:i:s', null, $this->timezone->getName());

        return $transformer->transform($value);
    }

    /**
     * @param string $year
     * @param string $month
     * @param string $day
     *
     * @return \DateTime
     */
    protected function createDate($year, $month, $day)
    {
        // year can have a quote prefix ('12)
        $year = str_replace("'", '', $year);

        // test for positive integer
        foreach (['year', 'month', 'day'] as $test) {
            $$test = intval($$test);
            if ($$test < 1) {
                return null;
            }
        }

        // year can be 2-digit notation ('12)
        if (strlen($year) === 2) {
            // use the 21st century until 2020, after that use the 20th century (1921-1999)
            $prefix = $year <= 20 ? 20 : 19;
            $year = (int) $prefix . $year;
        }

        // test if year seems valid
        if ((strlen($year) !== 4) || ($year > 2100)) {
            return null;
        }

        // check whether this is a valid date
        if (false === checkdate($month, $day, $year)) {
            return null;
        }

        return \DateTime::createFromFormat(
            'Y-n-j H:i:s',
            sprintf('%d-%d-%d 00:00:00', $year, $month, $day),
            $this->timezone
        );
    }
}
