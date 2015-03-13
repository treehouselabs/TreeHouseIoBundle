<?php

namespace TreeHouse\IoBundle\Source\Cleaner;

interface ThresholdVoterInterface
{
    /**
     * @param integer $count
     * @param integer $total
     * @param integer $max
     * @param string  $message
     *
     * @return boolean
     */
    public function vote($count, $total, $max, $message = '');
}
