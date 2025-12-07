<?php

namespace addons\kefu\library\pushapi\test;

use addons\kefu\library\pushapi\GTClient;

define("APPKEY", "*");
define("APPID", "*");
define("MS", "*");
define("URL", "*");
define("CID1", "*");
define("CID2", "*");
define("CID3", "*");

$token  = null;
$taskId = null;
$api    = new GTClient(URL, APPKEY, APPID, MS);

//closeAuth();
//别名
bindAlias1();
bindAlias2();
queryAliasByCid();
queryCidByAlias();
unBindAlias();
unBindAllAlias();

//标签
queryUserTag();
setTagForCid();
batchModifyTagForBatchCid();
unbindTag();
queryUserStatus();
addBlackUser();
removeBlackUser();
setBadge();
queryUserCount();

function closeAuth()
{
    global $api;
    echo json_encode($api->userApi()->closeAuth());
}

//用户
function bindAlias1()
{
    $cidAliasListRequest = new GTAliasRequest();
    //    $als1 = new GTCidAlias();
    //    $als1->setCid(CID1);
    //    $als1->setAlias("aaa");
    $als2 = new GTCidAlias();
    $als2->setCid(CID3);
    $als2->setAlias("cccc");
    //    $cidAliasListRequest->addDataList($als1);
    $cidAliasListRequest->addDataList($als2);
    global $api;
    echo json_encode($api->userApi()->bindAlias($cidAliasListRequest));
}

function bindAlias2()
{
    $cidAliasListRequest = new GTAliasRequest();
    $als1                = new GTCidAlias();
    $als1->setCid(CID1);
    $als1->setAlias("tag1");
    $als2 = new GTCidAlias();
    $als2->setCid(CID3);
    $als2->setAlias("tag3");
    $arr = [$als1, $als2];
    $cidAliasListRequest->setDataList($arr);
    global $api;
    echo json_encode($api->userApi()->bindAlias($cidAliasListRequest));
}

function queryAliasByCid()
{
    global $api;
    echo json_encode($api->userApi()->queryAliasByCid(CID3));
}

function queryCidByAlias()
{
    global $api;
    echo json_encode($api->userApi()->queryCidByAlias("tag1"));
}

function unBindAlias()
{
    $cidAliasListRequest = new GTAliasRequest();
    $als1                = new GTCidAlias();
    $als1->setCid(CID1);
    $als1->setAlias("aaa");
    $cidAliasListRequest->addDataList($als1);
    global $api;
    echo json_encode($api->userApi()->unBindAlias($cidAliasListRequest));
}

function unBindAllAlias()
{
    global $api;
    echo json_encode($api->userApi()->unBindAllAlias("tag1"));
}

function setTagForCid()
{
    $tags = new GTTagSetRequest();
    $tags->setCid(CID1);
    $array = ["tag3", "tag2", "tag4"];
    $tags->setCustomTag($array);
    global $api;
    echo json_encode($api->userApi()->setTagForCid($tags));
}

function batchModifyTagForBatchCid()
{
    $tags = new GTTagBatchSetRequest();
    $tags->setCustomTag("tagb");
    $array = [CID1, CID2];
    $tags->setCid($array);
    global $api;
    echo json_encode($api->userApi()->batchModifyTagForBatchCid($tags));
}

function unbindTag()
{
    $tags = new GTTagBatchSetRequest();
    $tags->setCustomTag("tag3");
    $array = [CID1];
    $tags->setCid($array);
    global $api;
    $rep = $api->userApi()->unbindTag($tags);
    echo json_encode($rep);
}

function queryUserTag()
{
    global $api;
    $rep = $api->userApi()->queryUserTag(CID3);
    echo json_encode($rep);
}

function addBlackUser()
{
    $array = [CID1];
    global $api;
    echo json_encode($api->userApi()->addBlackUser($array));
}

function queryUserStatus()
{
    $array = [CID1];
    global $api;
    echo json_encode($api->userApi()->queryUserStatus($array));
}

function removeBlackUser()
{
    $array = [CID1];
    global $api;
    echo json_encode($api->userApi()->removeBlackUser($array));
}

function setBadge()
{
    $param = new GTBadgeSetRequest();
    $param->setBadge(10);
    $array = [CID1];
    $param->setCids($array);
    global $api;
    echo json_encode($api->userApi()->setBadge($param));
}

function queryUserCount()
{
    $param     = new GTUserQueryRequest();
    $condition = new GTCondition();
    $condition->setKey("custom_tag");
    $condition->setValues(["tagb"]);
    $condition->setOptType("and");
    $condition1 = new GTCondition();
    $condition1->setKey("custom_tag");
    $condition1->setValues(["tag2"]);
    $condition1->setOptType("and");
    $param->setTag([$condition1]);
    $param->addTag($condition);
    global $api;
    echo json_encode($api->userApi()->queryUserCount($param));
}



