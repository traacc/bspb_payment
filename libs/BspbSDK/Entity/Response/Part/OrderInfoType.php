<?php

namespace BspbSDK\Entity\Response\Part;

use BspbSDK\Entity\Response\AbstractResponseEntity;

class OrderInfoType extends AbstractResponseEntity
{
    protected $allowVoid;
    protected $title;
    protected $allowCVV2;

    /**
     * @return mixed
     */
    public function getAllowVoid()
    {
        return $this->allowVoid;
    }

    /**
     * @param mixed $allowVoid
     * @return OrderInfoType
     */
    public function setAllowVoid($allowVoid)
    {
        $this->allowVoid = $allowVoid;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return OrderInfoType
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAllowCVV2()
    {
        return $this->allowCVV2;
    }

    /**
     * @param mixed $allowCVV2
     * @return OrderInfoType
     */
    public function setAllowCVV2($allowCVV2)
    {
        $this->allowCVV2 = $allowCVV2;
        return $this;
    }


}
