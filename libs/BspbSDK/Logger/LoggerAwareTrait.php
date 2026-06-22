<?php

namespace BspbSDK\Logger;

/**
 * Basic Implementation of LoggerAwareInterface.
 */
trait LoggerAwareTrait
{
    /**
     * The logger instance.
     */
    protected $logger = null;

    /**
     * Sets a logger.
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
