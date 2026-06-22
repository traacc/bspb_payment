<?php

namespace BspbSDK\Entity\Response;

use BspbSDK\Entity\Response\Part\MatchBlock;

class RefundOrderFullResponse extends AbstractResponse
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
     * @return RefundOrderFullResponse
     */
    public function setApprovalCode($approvalCode)
    {
        $this->approvalCode = $approvalCode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApprovedPartial()
    {
        return $this->approvedPartial;
    }

    /**
     * @param mixed $approvedPartial
     * @return RefundOrderFullResponse
     */
    public function setApprovedPartial($approvedPartial)
    {
        $this->approvedPartial = $approvedPartial;
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
     * @return RefundOrderFullResponse
     */
    public function setMatch($match): RefundOrderFullResponse
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
