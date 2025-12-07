<?php

namespace addons\kefu\library\pushapi\request\user;

use addons\kefu\library\pushapi\request\GTApiRequest;

class GTUserQueryRequest extends GTApiRequest
{
    private $tag = [];

    public function getTag()
    {
        return $this->tag;
    }

    public function addTag($condition)
    {
        array_push($this->tag, $condition);
    }

    public function setTag($conditions)
    {
        $this->tag = $conditions;
    }

    public function getApiParam()
    {
        $this->apiParam["tag"] = [];
        foreach ($this->tag as $value) {
            array_push($this->apiParam["tag"], $value->getApiParam());
        }
        return $this->apiParam;
    }
}
