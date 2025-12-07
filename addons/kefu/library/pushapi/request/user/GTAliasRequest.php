<?php

namespace addons\kefu\library\pushapi\request\user;

use addons\kefu\library\pushapi\request\GTApiRequest;


class GTAliasRequest extends GTApiRequest
{
    //dataList	Json Array	数据列表，数组长度不大于200
    private $dataList = [];

    public function getDataList()
    {
        return $this->dataList;
    }

    //添加单个CidAlias
    public function addDataList($cidAlias)
    {
        array_push($this->dataList, $cidAlias);
    }

    //set CidAlias数组
    public function setDataList($cidAliasList)
    {
        $this->dataList = $cidAliasList;
    }

    public function getApiParam()
    {
        $this->apiParam["data_list"] = [];
        foreach ($this->dataList as $value) {
            array_push($this->apiParam["data_list"], $value->getApiParam());
        }
        return $this->apiParam;
    }
}