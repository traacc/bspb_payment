<?php

namespace BspbSDK\Entity\Response;

use BspbSDK\Entity\Response\Part\MatchBlock;

class CancelOrderFullResponse extends AbstractResponse
{

    protected $approvalCode;
    protected $approvedPartial;
    /** @var MatchBlock */
    protected $match;

    /**
     * @return mixed
     */
    public function getApprovalCode()
    {
        return $this->approvalCode;
    }

    /**
     * @param mixed $approvalCode
     * @return CancelOrderFullResponse
     */
    public function setApprovalCode($approvalCode): CancelOrderFullResponse
    {
        $this->approvalCode = $approvalCode;
        return $this;
    }

    /**
     * @return MatchBlock
     */
    public function getMatch(): MatchBlock
    {
        return $this->match;
    }

    /**
     * @param $match
     * @return CancelOrderFullResponse
     */
    public function setMatch($match): CancelOrderFullResponse
    {
        $this->match = (new MatchBlock())->setFields($match);
        return $this;
    }

    public function setFields($fields)
    {
        if (is_object($fields) && isset($fields->tran)) return parent::setFields($fields->tran);
        return parent::setFields($fields);
    }


}
