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

$taskId = null;
$api    = new GTClient(URL, APPKEY, APPID, MS);

queryPushResultByTaskIds();
queryPushResultByGroupName();
queryUserDataByDate();
queryOnlineUserData();
queryPushResultByDate();

function queryPushResultByTaskIds()
{
    global $api;
    echo json_encode($api->statisticsApi()->queryPushResultByTaskIds(["taskid"]));
}

function queryPushResultByGroupName()
{
    global $api;
    echo json_encode($api->statisticsApi()->queryPushResultByGroupName("test"));
}

function queryUserDataByDate()
{
    global $api;
    echo json_encode($api->statisticsApi()->queryUserDataByDate("2020-11-30"));
}

function queryOnlineUserData()
{
    global $api;
    echo json_encode($api->statisticsApi()->queryOnlineUserData());
}

function queryPushResultByDate()
{
    global $api;
    echo json_encode($api->statisticsApi()->queryPushResultByDate("2020-11-30"));
}
