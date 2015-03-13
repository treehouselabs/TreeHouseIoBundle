<?php

namespace TreeHouse\IoBundle\Twig;

class CommonExtension extends \Twig_Extension
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'io_common';
    }

    /**
     * @inheritdoc
     */
    public function getTests()
    {
        return [
            'date' => new \Twig_SimpleTest('date', function ($obj) { return ($obj instanceof \DateTime); }),
        ];
    }
}
