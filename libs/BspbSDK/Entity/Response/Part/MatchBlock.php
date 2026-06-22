<?php

namespace BspbSDK\Entity\Response\Part;

use BspbSDK\Entity\Response\AbstractResponseEntity;

class MatchBlock extends AbstractResponseEntity
{

    protected $tranActionId;
    protected $ridByPmo;
    protected $pmoResultCode;

    /**
     * @return mixed
     */
    public function getTranActionId()
    {
        return $this->tranActionId;
    }

    /**
     * @param mixed $tranActionId
     * @return MatchBlock
     */
    public function setTranActionId($tranActionId)
    {
        $this->tranActionId = $tranActionId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRidByPmo()
    {
        return $this->ridByPmo;
    }

    /**
     * @param mixed $ridByPmo
     * @return MatchBlock
     */
    public function setRidByPmo($ridByPmo)
    {
        $this->ridByPmo = $ridByPmo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPmoResultCode()
    {
        return $this->pmoResultCode;
    }

    /**
     * @param mixed $pmoResultCode
     * @return MatchBlock
     */
    public function setPmoResultCode($pmoResultCode)
    {
        $this->pmoResultCode = $pmoResultCode;
        return $this;
    }

}
