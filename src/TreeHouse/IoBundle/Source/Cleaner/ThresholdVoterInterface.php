<?php

namespace TreeHouse\IoBundle\Source\Cleaner;

interface ThresholdVoterInterface
{
    /**
     * @param int    $count
     * @param int    $total
     * @param int    $max
     * @param string $message
     *
     * @return bool
     */
    public function vote($count, $total, $max, $message = '');
}
