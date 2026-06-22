<?php

namespace BspbSDK\Exceptions;

class BspbBadResponseException extends BspbException
{
    /** @var string */
    private $content = '';

    public function getContent():string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

}
