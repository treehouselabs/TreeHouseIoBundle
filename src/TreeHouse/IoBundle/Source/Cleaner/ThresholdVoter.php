<?php

namespace TreeHouse\IoBundle\Source\Cleaner;

class ThresholdVoter implements ThresholdVoterInterface
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param \Closure $callback
     */
    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param int    $count
     * @param int    $total
     * @param int    $max
     * @param string $message
     *
     * @return bool
     */
    public function vote($count, $total, $max, $message = '')
    {
        if (empty($message)) {
            $message = sprintf(
                'Cleanup threshold reached: %s of %s sources, %s is the maximum.',
                $count,
                $total,
                $max
            );
        }

        return call_user_func($this->callback, $count, $total, $max, $message);
    }
}
