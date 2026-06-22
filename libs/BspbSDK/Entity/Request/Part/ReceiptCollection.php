<?php

namespace BspbSDK\Entity\Request\Part;

use BspbSDK\Entity\Request\AbstractRequestEntityCollection;

class ReceiptCollection extends AbstractRequestEntityCollection
{

    public function getAllFields(): array
    {
        return [
            'items' => parent::getAllFields(),
        ];
    }

}
