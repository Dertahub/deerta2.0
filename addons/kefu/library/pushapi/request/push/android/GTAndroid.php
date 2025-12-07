<?php

namespace addons\kefu\library\pushapi\request\push\android;

use addons\kefu\library\pushapi\request\GTApiRequest;

class GTAndroid extends GTApiRequest
{
    //android厂商通道推送消息内容
    private $ups;

    public function getUps()
    {
        return $this->ups;
    }

    public function setUps($ups)
    {
        $this->ups = $ups;
    }

    public function getApiParam()
    {
        if ($this->ups != null) {
            $this->apiParam["ups"] = $this->ups->getApiParam();
        }
        return $this->apiParam;
    }
}