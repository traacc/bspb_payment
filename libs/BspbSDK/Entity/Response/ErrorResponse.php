<?php

namespace BspbSDK\Entity\Response;

class ErrorResponse extends AbstractResponse
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
