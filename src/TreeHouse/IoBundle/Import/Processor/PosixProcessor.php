<?php

namespace TreeHouse\IoBundle\Import\Processor;

use TreeHouse\IoBundle\Entity\ImportPart;
use TreeHouse\IoBundle\Import\Exception\RunningPartException;

class PosixProcessor implements ProcessorInterface
{
    /**
     * @inheritdoc
     */
    public function isRunning(ImportPart $part)
    {
        if (null === $pid = $part->getProcess()) {
            return false;
        }

        if (intval($pid) < 1) {
            throw new \RuntimeException(
                sprintf('Import part does not have a valid pid: %s', json_encode($pid))
            );
        }

        // kill signal 0: check whether a process is running.
        // see http://www.php.net/manual/en/function.posix-kill.php#82560
        return posix_kill($pid, 0);
    }

    /**
     * @inheritdoc
     */
    public function markProcessing(ImportPart $part)
    {
        $part->setProcess(posix_getpid());
    }

    /**
     * @inheritdoc
     */
    public function checkProcessing(ImportPart $part)
    {
        if (!$this->isRunning($part)) {
            return;
        }

        throw new RunningPartException(
            sprintf(
                'Part %d of import %d is already running by process %d',
                $part->getPosition(),
                $part->getImport()->getId(),
                $part->getProcess()
            )
        );
    }
}
