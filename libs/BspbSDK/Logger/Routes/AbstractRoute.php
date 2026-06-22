<?php

namespace BspbSDK\Logger\Routes;

abstract class AbstractRoute
{
    /**
     * @var bool
     */
    protected $enabled = false;

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return $this
     */
    public function enable()
    {
        $this->enabled = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disable()
    {
        $this->enabled = false;
        return $this;
    }

    abstract public function log(string $dataString);

    abstract public function read(): string;

}
