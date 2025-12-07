<?php
// +---------------------------------------------------------------------------------------------------------------------------
// | 本文件是《FastAdmin客服系统》的一部分，所有代码、文字、图片、样式、风格等版权归作者所有，如有复制、仿冒、抄袭、盗用，FastAdmin和作者将追究法律责任
// +---------------------------------------------------------------------------------------------------------------------------
// | @作者: 妙码生花 <1094963513@qq.com>
// +---------------------------------------------------------------------------------------------------------------------------
// | @唯一授权链接: https://www.fastadmin.net/store/kefu.html
// +---------------------------------------------------------------------------------------------------------------------------

use addons\kefu\library\Common;
use GatewayWorker\Lib\Gateway;
use think\Db;

/**
 *
 */
class Message
{
    public $pageCount = 15;

    public function __construct()
    {

    }

    /*清退链接*/
    public function clear($client_id)
    {
        Gateway::closeClient($client_id);
    }

    /*用户端心跳*/
    public function ping()
    {
    }

    /**
     * 推送消息-主要用于推送小程序端发送的消息到客服
     */
    public function pushMessage($client_id, $data)
    {
        //think\Log::record('push_message' . json_encode($data, true), 'notice');

        $_SESSION['user_id'] = $data['sender'];
        $_SESSION['cdn_url'] = $data['origin'];

        // 标记消息已读
        Db::name('kefu_record')
            ->where('session_id', $data['session_id'])
            ->where('sender_identity', 0)
            ->where('status', 0)
            ->update(['status' => 1]);

        $session = Db::name('kefu_session')->where('id', $data['session_id'])->find();
        if ($session) {
            $session_user = Common::sessionUser($session, $data['sender']);
            if (Gateway::isUidOnline($session_user)) {
                Gateway::sendToUid($session_user, json_encode([
                    'msgtype' => 'read_message_done',
                    'data'    => [
                        'session_id' => $data['session_id'],
                        'record_id'  => 'all'
                    ]
                ]));
            }
        }

        Common::chatRecord($data['session_id'], $data['message'], $data['message_type'], $data['sender']);
    }

    /**
     * 客服代表改变状态
     */
    public function csrChangeStatus($client_id, $data)
    {
        if (isset($data['status'])) {
            $status = Common::csrStatus($data['status']);

            // 向所有人发送
            Gateway::sendToAll(json_encode([
                'msgtype' => 'csr_change_status',
                'data'    => [
                    'csr_status' => $data['status'],
                    'csr'        => $_SESSION['user_id'],
                ],
                'code'    => 1,
            ]));
        }
    }

    public function pushCid($client_id, $data)
    {
        if (!isset($data['type']) || !isset($data['clientid']) || !$data['clientid'] || $data['clientid'] == 'null' || !isset($data['platform']) || !$data['platform']) {
            common::showMsg($client_id, '缺少参数！');
            return false;
        }

        $userInfo = Common::userInfo($_SESSION['user_id']);

        if ($data['type'] == 'logout') {
            Db::name('kefu_user_push_clientid')
                ->where('user_id', $userInfo['id'])
                ->where('source', $userInfo['source'])
                ->where('clientid', $data['clientid'])
                ->where('platform', $data['platform'])
                ->delete();

            Gateway::sendToClient($client_id, json_encode([
                'msgtype' => 'logout',
                'code'    => 1,
                'data'    => []
            ]));
            return false;
        }

        $userClientValueId = Db::name('kefu_user_push_clientid')
            ->where('user_id', $userInfo['id'])
            ->where('source', $userInfo['source'])
            ->where('clientid', $data['clientid'])
            ->where('platform', $data['platform'])
            ->value('id');
        if ($userClientValueId) {
            Db::name('kefu_user_push_clientid')->where('id', $userClientValueId)->update([
                'updatetime' => time()
            ]);
        } else {
            Db::name('kefu_user_push_clientid')->insert([
                'user_id'    => $userInfo['id'],
                'source'     => $userInfo['source'],
                'clientid'   => $data['clientid'],
                'platform'   => $data['platform'],
                'updatetime' => time()
            ]);
        }
    }

    public function sessionList($client_id, $data)
    {
        $session = Common::sessionList($_SESSION['user_id'], 40);
        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'session-list',
            'data'    => $session,
            'code'    => 1
        ]));
    }

    public function addressList($client_id, $data)
    {
        $session = Db::name('kefu_session')
            ->alias('s')
            ->field('s.id,s.user_id,s.csr_id,s.createtime,CONCAT(u.id,"||user") as session_user,u.user_id as fu_user_id,u.avatar,u.nickname,u.wechat_openid,fu.avatar as fu_avatar,fu.nickname as fu_nickname')
            ->join('kefu_user u', 'u.id=s.user_id')
            ->join('user fu', 'u.user_id=fu.id', 'LEFT')
            ->where('s.csr_id', $_SESSION['user_id'])
            ->order('s.createtime desc')
            ->select();

        $sessionTemp = [];
        $nowTime     = time();
        foreach ($session as $key => $value) {
            $value['avatar']   = $value['fu_avatar'] ? $value['fu_avatar'] : $value['avatar'];
            $value['nickname'] = $value['fu_nickname'] ? $value['fu_nickname'] : $value['nickname'];
            $value['avatar']   = Common::imgSrcFill($value['avatar'], true);

            if ($data['action'] == 'recently') {
                // 最后一条聊天记录
                $last_message = Db::name('kefu_record')
                    ->where('session_id', $value['id'])
                    ->order('createtime desc')
                    ->find();

                // 用户发来的未读消息数
                $unread_msg_count = Db::name('kefu_record')
                    ->where('session_id', $value['id'])
                    ->where('sender_identity', 1)
                    ->where('sender_id', $value['user_id'])
                    ->where('status', 0)
                    ->count('id');

                $value['online'] = $value['wechat_openid'] ? 1 : Gateway::isUidOnline($value['session_user']);
                $last_time       = isset($last_message['createtime']) ? $last_message['createtime'] : $value['createtime'];
                $dialogue_time   = $value['wechat_openid'] ? 600 : 43200; // 这个时间内的会话计入会话中

                if ($value['online'] || ($nowTime - $last_time < $dialogue_time) || $unread_msg_count > 0) {

                } else {
                    $sessionTemp[] = $value;
                }
            } else {
                $sessionTemp[] = $value;
            }

            unset($value['fu_avatar'], $value['fu_nickname'], $value['wechat_openid']);
        }

        $initialPinyinArr = Common::initialPinyinArrSort($sessionTemp, 'nickname');

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'address-list',
            'data'    => $initialPinyinArr,
            'code'    => 1
        ]));
    }

    public function getInfo($client_id, $data)
    {
        if (isset($data['method']) && $data['method'] == 'post-user-edit') {
            $userInfo = Common::userInfo($_SESSION['user_id']);
            if ($userInfo['source'] != 'csr') {
                common::showMsg($client_id, '您没有权限操作！');
                return;
            }

            unset($data['method']);

            if (isset($data['id']) && $data['id']) {
                if (Db::name('kefu_user')->where('id', $data['id'])->update($data)) {
                    common::showMsg($client_id, '保存成功~');
                } else {
                    common::showMsg($client_id, '保存失败，请重试！');
                }
            } else {

                unset($data['id']);

                if (Db::name('admin')->where('id', $userInfo['id'])->update($data)) {
                    common::showMsg($client_id, '保存成功~');
                } else {
                    common::showMsg($client_id, '保存失败，请重试！');
                }
            }
            return;
        }

        if (isset($data['id']) && $data['id']) {
            $userInfo           = Common::userInfo($data['id'] . '||user');
            $userInfo['status'] = $userInfo['wechat_openid'] ? 3 : (Gateway::isUidOnline($data['id'] . '||user') ? 3 : 0);

            // 获得会话ID
            Db::name('kefu_session')
                ->where('user_id', $data['id'])
                ->update([
                    'deletetime' => null
                ]);

            $csrInfo = Common::userInfo($_SESSION['user_id']);

            $userInfo['session_id'] = Db::name('kefu_session')
                ->where('user_id', $data['id'])
                ->where('csr_id', $csrInfo['id'])
                ->value('id');

            unset($userInfo['token']);
        } else {
            $userInfo = Common::userInfo($_SESSION['user_id']);
        }

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'user-info',
            'data'    => [
                'info' => $userInfo,
                'data' => $data
            ],
            'code'    => 1
        ]));
    }

    public function blacklistUser($client_id, $data)
    {
        $userInfo = Common::userInfo($_SESSION['user_id']);

        if ($data['method'] == 'relieve') {
            Db::name('kefu_blacklist')->where('id', $data['id'])->where('admin_id', $userInfo['id'])->delete();

            $data['page']   = 1;
            $data['method'] = 'get';

            common::showMsg($client_id, '已解除黑名单~');
        }

        if (!isset($data['page']) || $data['page'] == 1) {
            $min = 0;
        } else {
            $min = ($data['page'] - 1) * $this->pageCount;
        }
        $users = Db::name('kefu_blacklist')
            ->alias('b')
            ->field('b.*,u.avatar,u.nickname,fu.avatar as fu_avatar,fu.nickname as fu_nickname')
            ->join('kefu_user u', 'b.user_id=u.id')
            ->join('user fu', 'u.user_id=fu.id', 'LEFT')
            ->where('admin_id', $userInfo['id'])
            ->order('createtime desc')
            ->limit($min, $this->pageCount)
            ->select();
        foreach ($users as $key => &$value) {
            $value['avatar']   = $value['fu_avatar'] ? $value['fu_avatar'] : $value['avatar'];
            $value['nickname'] = $value['fu_nickname'] ? $value['fu_nickname'] : $value['nickname'];
            $value['avatar']   = Common::imgSrcFill($value['avatar'], true);
            unset($value['fu_avatar'], $value['fu_nickname']);
        }

        $data['nextpage'] = (count($users) < $this->pageCount) ? false : true;

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'blacklists',
            'data'    => [
                'users' => $users,
                'data'  => $data
            ],
            'code'    => 1
        ]));
    }

    /**
     * 一个用户的全部快捷回复-会话用
     */
    public function userAllFastReply($client_id, $data)
    {
        $userInfo  = Common::userInfo($_SESSION['user_id']);
        $fastReply = Db::name('kefu_fast_reply')
            ->field('id,title,content')
            ->where(function ($query) use ($userInfo) {
                $query->where('admin_id', $userInfo['id'])->whereOr('admin_id', 0);
            })
            ->where('status', '1')
            ->where('deletetime', null)
            ->order('admin_id desc,createtime desc')
            ->select();

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'user-all-fast-reply',
            'data'    => $fastReply,
            'code'    => 1
        ]));
    }

    public function fastReply($client_id, $data)
    {
        $userInfo = Common::userInfo($_SESSION['user_id']);

        if ($data['method'] == 'del') {
            Db::name('kefu_fast_reply')->where('id', $data['id'])->update([
                'deletetime' => time()
            ]);

            $data['page'] = 1;

            common::showMsg($client_id, '快捷回复已删除~');

            $this->userAllFastReply($client_id, []);
        } elseif ($data['method'] == 'edit') {
            $info = Db::name('kefu_fast_reply')
                ->where('id', $data['id'])
                ->where('deletetime', null)
                ->find();

            $info['content'] = strip_tags($info['content']);

            Gateway::sendToClient($client_id, json_encode([
                'msgtype' => 'fast-reply',
                'data'    => [
                    'data' => $data,
                    'info' => $info
                ],
                'code'    => 1
            ]));

            $this->userAllFastReply($client_id, []);
            return;
        } elseif ($data['method'] == 'update') {
            $updateData = $data['data'];
            unset($data['data']);
            $data['method'] = 'get';

            if ($updateData['id'] > 0) {
                // 更新
                Db::name('kefu_fast_reply')
                    ->where('id', $updateData['id'])
                    ->where('admin_id', $userInfo['id'])
                    ->update($updateData);
                $msg = '快捷回复已更新~';
            } else {
                // 添加
                unset($updateData['id']);
                $updateData['admin_id']   = $userInfo['id'];
                $updateData['createtime'] = time();
                Db::name('kefu_fast_reply')->insert($updateData);
                $msg = '快捷回复添加成功~';
            }

            common::showMsg($client_id, $msg);
            $this->userAllFastReply($client_id, []);
        }

        if (!isset($data['page']) || $data['page'] == 1) {
            $min = 0;
        } else {
            $min = ($data['page'] - 1) * $this->pageCount;
        }

        $fastReplyList = Db::name('kefu_fast_reply')
            ->where('admin_id', $userInfo['id'])
            ->where('deletetime', null)
            ->limit($min, $this->pageCount)
            ->select();

        $data['nextpage'] = (count($fastReplyList) < $this->pageCount) ? false : true;

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'fast-reply',
            'data'    => [
                'data'      => $data,
                'fastreply' => $fastReplyList
            ],
            'code'    => 1
        ]));
    }

    public function sessionSetting($client_id, $data)
    {
        // 会话信息
        $session_info = Db::name('kefu_session')->where('id', $data['session_id'])->find();

        if (!$session_info) {
            common::showMsg($client_id, '会话找不到啦！');
            return;
        }

        $userInfo = Common::userInfo($_SESSION['user_id']);
        if ($session_info['csr_id'] != $userInfo['id'] || $userInfo['source'] != 'csr') {
            common::showMsg($client_id, '您没有权限查看！');
            return;
        }

        $session_user                      = Common::sessionUser($session_info);
        $session_user_info                 = Common::userInfo($session_user);
        $session_user_info['session_user'] = $session_user;

        // 是否屏蔽此人
        $session_user_info['blacklist'] = Db::name('kefu_blacklist')
            ->where('user_id', $session_info['user_id'])
            ->value('id');

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'session-setting',
            'code'    => 1,
            'data'    => [
                'session_user_info' => $session_user_info
            ]
        ]));
    }

    /*
     * 直接标记消息已读
     */
    public function readMessage($client_id, $data)
    {
        if (isset($data['record_id']) && $data['record_id']) {
            // 查得会话信息
            if (Db::name('kefu_record')
                    ->where('id', (int)$data['record_id'])
                    ->update(['status' => 1]) && isset($data['session_id']) && $data['session_id']) {

                $session = Db::name('kefu_session')->where('id', (int)$data['session_id'])->find();
                if ($session) {
                    $session_user = Common::sessionUser($session);
                    if (Gateway::isUidOnline($session_user)) {
                        Gateway::sendToUid($session_user, json_encode([
                            'msgtype' => 'read_message_done',
                            'data'    => [
                                'session_id' => $data['session_id'],
                                'record_id'  => $data['record_id']
                            ]
                        ]));
                    }
                }
            }
        }
    }

    /*
     * 用户打开聊天窗口->分配客服->获取聊天记录
     */
    public function userInitialize($client_id, $data)
    {
        if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']) {
            return;
        }

        $auto_distribution_csr = true;// 是否需要自动分配客服代表

        $user_info = Common::userInfo($_SESSION['user_id']);
        if ($user_info['source'] == 'csr') {
            common::showMsg($client_id, '无法为客服成员分配客服代表！');
            return;
        }

        // 查询之前的客服代表
        $session = Db::name('kefu_session')
            ->alias('s')
            ->field('s.*,a.id as admin_id,a.nickname')
            ->join('admin a', 's.csr_id=a.id')
            ->where('s.user_id', $user_info['id'])
            ->find();

        // 客服状态
        if ($session) {
            $csr_status      = Db::name('kefu_csr_config')->where('admin_id', $session['admin_id'])->value('status');
            $uni_push_switch = Db::name('kefu_config')->where('name', 'uni_push_switch')->value('value');
            $cid             = Db::name('kefu_user_push_clientid')
                ->where('user_id', $session['csr_id'])
                ->where('source', 'csr')
                ->value('id');

            // 上次的客服代表在线
            if (($csr_status && $csr_status == 3) || ($uni_push_switch && $cid)) {
                $auto_distribution_csr = false;
                if ($session['deletetime']) {
                    Db::name('kefu_session')->where('id', $session['id'])->update([
                        'deletetime' => null
                    ]);
                }
            }
        }

        // 前台固定客服，只要 name 能查出客服代表均有效
        if (!empty($data['csr'])) {
            $csr_admin_id = Db::name('kefu_csr_config')->where('name', $data['csr'])->value('admin_id');
            if (!$csr_admin_id) {
                common::showMsg($client_id, '指定的用户不是客服代表！');
                return;
            }

            $auto_distribution_csr = false;

            if (!$session || $session['csr_id'] != $csr_admin_id) {
                $session = Common::distributionCsr($csr_admin_id . '||csr', $_SESSION['user_id']);
            }
        } else {
            // 前台通过客服ID指定客服检测
            // 有手动转移客服的记录时指定无效（仅新访客有效）
            $is_transfer = Db::name('kefu_trajectory')
                ->where('user_id', $user_info['id'])
                ->where('log_type', 8)
                ->value('id');

            if (!$is_transfer && isset($data['fixed_csr']) && (int)$data['fixed_csr'] > 0) {

                $csr_admin_id = Db::name('kefu_csr_config')->where('id', $data['fixed_csr'])->value('admin_id');
                if (!$csr_admin_id) {
                    common::showMsg($client_id, '指定的用户不是客服代表！');
                    return;
                }

                $auto_distribution_csr = false;

                if (!$session || $session['csr_id'] != $csr_admin_id) {
                    $session = Common::distributionCsr($csr_admin_id . '||csr', $_SESSION['user_id']);
                }
            }
        }

        // 自动分配客服代表
        if ($auto_distribution_csr) {
            $csr = Common::getAppropriateCsr();
            if ($csr) {
                $session = Common::distributionCsr($csr, $_SESSION['user_id']);
            } else {
                Gateway::sendToClient($client_id, json_encode([
                    'msgtype' => 'user_initialize',
                    'code'    => 302,
                    'msg'     => '无在线客服！',
                ]));
                return;
            }
        }

        if ($session) {

            // 客服状态
            $session['csr_status'] = Db::name('kefu_csr_config')
                ->where('admin_id', $session['admin_id'])
                ->value('status');

            $session['csr'] = $session['csr_id'] . '||csr';

            // 记录客服接待人数
            Db::name('kefu_csr_config')->where('admin_id', $session['admin_id'])->inc('reception_count')->update([
                'last_reception_time' => time(),
            ]);

            // 获取聊天记录
            $this->chatRecord($client_id, [
                'session_id' => $session['id'],
            ]);

            // 游客且有绑定会员
            if (isset($_SESSION['is_tourists']) && $_SESSION['is_tourists'] && $user_info['user_id']) {

                $session['user_tourists']  = true;
                $session['user_login_url'] = url('index/user/login');
            }

            $initialize_data['session'] = $session;

            Gateway::sendToClient($client_id, json_encode([
                'msgtype' => 'user_initialize',
                'code'    => 1,
                'data'    => $initialize_data,
            ]));
        } else {
            common::showMsg($client_id, '分配客服代表失败，请重试！');
            return;
        }
    }

    public function equipmentInspection($client_id, $data)
    {
        if ($data['type'] == 'check') {
            $clientids = Gateway::getClientIdByUid($_SESSION['user_id']);
            if (!$clientids) {
                return false;
            }
            foreach ($clientids as $clientid) {
                Gateway::sendToClient($clientid, json_encode([
                    'msgtype' => 'equipment-inspection',
                    'code'    => 1,
                    'data'    => [
                        'time'     => $data['time'],
                        'clientid' => $clientid
                    ]
                ]));
            }
        } elseif ($data['type'] == 'close') {
            Gateway::closeClient($data['clientid']);
        }
    }

    /**
     * 加载更多聊天记录
     * @return void
     */
    public function chatRecord($client_id, $data)
    {
        $page_count = 20; //一次加载20条

        if (!isset($data['page'])) {
            $data['page'] = 1;
        }

        if (!isset($data['session_id'])) {
            Gateway::sendToClient($client_id, json_encode([
                'msgtype' => 'chat_record',
                'code'    => 1,
                'data'    => [
                    'chat_record'  => [],
                    'session_info' => [
                        'nickname' => '无会话',
                    ],
                    'next_page'    => 'done',
                    'page'         => $data['page'],
                ],
            ]));

            return;
        }

        $chat_record_count = Db::name('kefu_record')->where('session_id', $data['session_id'])->count('id');

        $page_number = ceil($chat_record_count / $page_count);

        if ($data['page'] == 1) {
            $min = 0;
        } else {
            $min = ($data['page'] - 1) * $page_count;
            if (isset($data['unexpected_records']) && $data['unexpected_records'] > 0) {
                $min += $data['unexpected_records'];
            }
        }

        // 会话信息
        $session_info = Db::name('kefu_session')->where('id', $data['session_id'])->find();

        if (!$session_info) {
            common::showMsg($client_id, '会话找不到啦！');
            return;
        }

        $session_user                      = Common::sessionUser($session_info);
        $session_user_info                 = Common::userInfo($session_user);
        $session_user_info['session_user'] = $session_user;
        $session_user_info['status']       = (isset($session_user_info['wechat_openid']) && $session_user_info['wechat_openid']) ? 3 : (Gateway::isUidOnline($session_user) ? 3 : 0);

        // 标记此会话所有不是当前用户发的消息为已读->SQL不使用不等于->查得会话对象的ID
        Db::name('kefu_record')
            ->where('session_id', $data['session_id'])
            ->where('sender_identity', $session_user_info['source'] == 'csr' ? 0 : 1)
            ->where('status', 0)
            ->update(['status' => 1]);

        if (Gateway::isUidOnline($session_user)) {
            Gateway::sendToUid($session_user, json_encode([
                'msgtype' => 'read_message_done',
                'data'    => [
                    'session_id' => $data['session_id'],
                    'record_id'  => 'all'
                ]
            ]));
        }

        // 是否屏蔽此人
        $session_user_info['blacklist'] = Db::name('kefu_blacklist')
            ->where('user_id', $session_info['user_id'])
            ->value('id');

        $chat_record = Db::name('kefu_record')
            ->where('session_id', $data['session_id'])
            ->limit($min, $page_count)
            ->order('createtime desc,id desc')
            ->select();

        $user_info = Common::userInfo($_SESSION['user_id']);

        $tourists_record = false;
        if (isset($_SESSION['is_tourists']) && $_SESSION['is_tourists'] && $user_info['user_id']) {
            // 游客且有绑定会员
            $tourists_record[] = [
                'datetime' => '为保护您的隐私,您需要登录后才能查看历史聊天记录',
                'data'     => [],
            ];
        }

        // 消息按时间分组
        if ($chat_record && !$tourists_record) {

            $record_temp = [];
            $createtime  = $chat_record[0]['createtime'];

            foreach ($chat_record as $value) {

                if (($value['sender_identity'] == 0 && $user_info['source'] == 'csr') || ($value['sender_identity'] == 1 && $user_info['source'] == 'user')) {
                    $value['sender'] = 'me';
                } else {
                    $value['sender'] = 'you';
                }

                if ($value['message_type'] == 1 || $value['message_type'] == 2) {
                    $value['message'] = Common::imgSrcFill($value['message']);
                } else {
                    $value['message'] = htmlspecialchars_decode($value['message']);
                }

                if ($value['createtime'] - $createtime >= 3600) {
                    $createtime = $value['createtime'];
                }
                $record_temp[$createtime][] = $value;
            }

            unset($chat_record);

            foreach ($record_temp as $key => $value) {
                $chat_record[] = [
                    'datetime' => Common::formatTime($key),
                    'data'     => $value,
                ];
            }
            unset($record_temp);
        } elseif ($tourists_record) {
            $chat_record = $tourists_record;
        } else {
            $chat_record[] = [
                'datetime' => '还没有消息',
                'data'     => [],
            ];
        }

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'chat_record',
            'code'    => 1,
            'data'    => [
                'chat_record'  => $chat_record ?? [],
                'session_info' => $session_user_info,
                'next_page'    => ($data['page'] >= $page_number) ? 'done' : $data['page'] + 1,
                'page'         => $data['page']
            ],
        ]));
    }

    /**
     * 更新云存储 multipart
     */
    public function getUploadMultipart($client_id)
    {
        // 获取上传配置
        $upload = \app\common\model\Config::upload();
        \think\Hook::listen("upload_config_init", $upload);

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'upload_multipart',
            'code'    => 1,
            'data'    => [
                'upload_multipart' => isset($upload['multipart']) ? $upload['multipart'] : []
            ],
        ]));
    }

    /**
     * 记录留言
     */
    public function leaveMessage($client_id, $data)
    {
        if (!$data['contact']) {
            common::showMsg($client_id, '联系方式不能为空~');
            return;
        }

        $user_info = Common::userInfo($_SESSION['user_id']);

        if ($user_info['source'] == 'csr') {

            common::showMsg($client_id, '客服代表无需留言~');
            return;
        }

        $data['user_id']    = $user_info['id'];
        $data['createtime'] = time();

        $last_leave_message_time = Db::name('kefu_leave_message')
            ->where('user_id', $user_info['id'])
            ->order('createtime desc')
            ->value('createtime');

        if ($last_leave_message_time && ($last_leave_message_time + 600) > $data['createtime']) {
            common::showMsg($client_id, '请勿频繁留言，请稍后再试~');
            return;
        }

        // 入库
        if (Db::name('kefu_leave_message')->insert($data)) {

            $leave_message_id = Db::name('kefu_leave_message')->getLastInsID();

            // 记录轨迹
            $trajectory = [
                'user_id'    => $user_info['id'],
                'csr_id'     => 0,
                'log_type'   => 6,
                'note'       => $leave_message_id,
                'url'        => '',
                'referrer'   => '',
                'createtime' => time(),
            ];
            Db::name('kefu_trajectory')->insert($trajectory);

            Gateway::sendToClient($client_id, json_encode([
                'msgtype' => 'leave_message',
                'code'    => 1,
                'msg'     => '留言成功~',
            ]));

            $new_message_notice = Db::name('kefu_config')->where('name', 'new_message_notice')->value('value');
            if ($new_message_notice) {

                $markdown = '### 有新的留言 - ' . $user_info['nickname'] . PHP_EOL . '##### 联系姓名:' . $data['name'] . PHP_EOL . '##### 联系方式:' . $data['contact'] . PHP_EOL . '##### 留言内容:' . $data['message'];

                Common::dingNotice($new_message_notice, '有新的留言', $markdown, true);
            }

        } else {
            common::showMsg($client_id, '留言失败，请重试~');
            return;
        }
    }

    /**
     * 用户名片
     */
    public function userCard($client_id, $data)
    {
        if (!isset($data['session_user'])) {
            common::showMsg($client_id, '用户未找到');
            return;
        }

        // 用户信息
        $user_info = Common::userInfo($data['session_user']);
        if (!$user_info) {
            common::showMsg($client_id, '用户未找到');
            return;
        }

        if (isset($data['action']) && $data['action'] == 'done') {
            if (Db::name('kefu_user')->where('id', $user_info['id'])->update($data['form_data'])) {

                common::showMsg($client_id, '保存成功！');
                Gateway::sendToClient($client_id, json_encode([
                    'msgtype' => 'user_card',
                    'code'    => 1,
                    'data'    => Common::userInfo($data['session_user']),
                ]));
            } else {
                common::showMsg($client_id, '保存失败，请重试！');
            }

            return;
        }

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'user_card',
            'code'    => 1,
            'data'    => $user_info,
        ]));
    }

    /*
     * 邀请对话 & 删除会话 & 转接会话 & 修改用户昵称
     */
    public function actionSession($client_id, $data)
    {

        if (!isset($data['session_user'])) {
            common::showMsg($client_id, '用户未找到');
            return;
        }

        // 用户信息
        $user_info = Common::userInfo($data['session_user']);
        if (!$user_info) {
            common::showMsg($client_id, '用户未找到');
            return;
        }

        if ($data['action'] == 'del') {

            $session_info = Db::name('kefu_session')
                ->where('user_id', $user_info['id'])
                ->where('deletetime', null)
                ->find();
            if (!$session_info) {
                common::showMsg($client_id, '会话找不到啦！');
                return;
            }

            if (!Db::name('kefu_session')->where('user_id', $user_info['id'])->update(['deletetime' => time()])) {
                common::showMsg($client_id, '会话删除失败！');
                return;
            }

            common::showMsg($client_id, '会话已移除~');

            if (isset($data['platform']) && $data['platform'] == 'uni') {
                Gateway::sendToClient($client_id, json_encode([
                    'msgtype' => 'reload-session-list',
                    'code'    => 1
                ]));
            }

        } elseif ($data['action'] == 'invitation') {
            if (Gateway::isUidOnline($data['session_user'])) {

                $user     = explode('||', $data['session_user']);
                $csr_info = Common::userInfo($_SESSION['user_id']);

                // 记录轨迹
                $trajectory = [
                    'user_id'    => $user[0],
                    'csr_id'     => $csr_info['id'],
                    'log_type'   => 1,
                    'note'       => $csr_info['nickname'] . ' 邀请对话',
                    'url'        => '',
                    'referrer'   => '',
                    'createtime' => time(),
                ];
                Db::name('kefu_trajectory')->insert($trajectory);

                Gateway::sendToUid($data['session_user'], json_encode([
                    'code'    => 1,
                    'msgtype' => 'action_session',
                    'data'    => [
                        'action' => 'received_invitation',
                    ],
                ]));

                Gateway::sendToClient($client_id, json_encode([
                    'code'    => 1,
                    'msgtype' => 'action_session',
                    'data'    => [
                        'action'       => 'send_success',
                        'session_user' => $data['session_user'],
                    ],
                ]));
            } else {
                common::showMsg($client_id, '邀请对话失败，用户已下线！');
                return;
            }
        } elseif ($data['action'] == 'transfer') {

            $csr_info = Common::userInfo($_SESSION['user_id']);

            // 转接会话->返回客服列表
            $csr_list = Db::name('kefu_csr_config')
                ->alias('c')
                ->field('c.admin_id,a.nickname')
                ->join('admin a', 'a.id=c.admin_id')
                ->where('c.status', 3)
                ->select();

            foreach ($csr_list as $key => $value) {
                if ($value['admin_id'] == $csr_info['id']) {
                    unset($csr_list[$key]);
                    break;
                }
            }

            Gateway::sendToClient($client_id, json_encode([
                'code'    => 1,
                'msgtype' => 'action_session',
                'data'    => [
                    'action'       => 'transfer',
                    'csr_list'     => $csr_list,
                    'session_user' => $data['session_user'],
                ],
            ]));

        } elseif ($data['action'] == 'transfer_done') {

            // 转接会话
            if (!isset($data['csr']) || !isset($data['session_user'])) {

                common::showMsg($client_id, '会话转移失败，请重试~');
                return;
            }

            $session_user_info = Common::userInfo($data['session_user']);
            $current_csr_info  = Common::userInfo($_SESSION['user_id']);

            if ($current_csr_info['id'] == $data['csr']) {

                common::showMsg($client_id, '不能将会话转移给自己哦~');
                return;
            }

            // 带标识符的转移对像客服ID
            $csr_id  = $data['csr'] . '||csr';
            $session = Common::distributionCsr($csr_id, $data['session_user']);

            if ($session) {

                // 将会话发送给新客服
                if (Gateway::isUidOnline($csr_id)) {

                    $message = [
                        'id'           => $session['id'],
                        'session_id'   => $session['id'],
                        'session_user' => $data['session_user'],
                        'last_time'    => Common::formatSessionTime(null),
                        'last_message' => '本会话被客服 ' . $current_csr_info['nickname'] . ' 转移给您',
                        'online'       => 1,
                        'avatar'       => Common::imgSrcFill($session_user_info['avatar'], true),
                        'nickname'     => $session_user_info['nickname'],
                    ];

                    // 查询当前用户发送的未读消息条数
                    $message['unread_msg_count'] = Db::name('kefu_record')
                        ->where('session_id', $message['session_id'])
                        ->where('sender_identity', $session_user_info['source'] == 'csr' ? 0 : 1)
                        ->where('sender_id', $session_user_info['id'])
                        ->where('status', 0)
                        ->count('id');

                    $message['unread_msg_count'] += 1;

                    Gateway::sendToUid($csr_id, json_encode(['msgtype' => 'new_message', 'data' => $message]));
                }

                // 发客服转移消息给用户
                if (Gateway::isUidOnline($data['session_user'])) {
                    Gateway::sendToUid($data['session_user'], json_encode([
                        'msgtype' => 'transfer_done',
                        'data'    => [
                            'csr'      => $csr_id,
                            'nickname' => $session['nickname'],
                        ],
                    ]));
                }

                $res = $session['nickname'];
            } else {
                $res = false;
            }

            Gateway::sendToClient($client_id, json_encode([
                'code'    => 1,
                'msgtype' => 'action_session',
                'data'    => [
                    'res'          => $res,
                    'action'       => 'transfer_done',
                    'session_user' => $data['session_user'],
                ],
            ]));
        } elseif ($data['action'] == 'edit_nickname') {

            if (!isset($data['new_nickname']) || !$data['new_nickname']) {
                common::showMsg($client_id, '新昵称不能为空！');
                return;
            }

            $session_user_info = Common::userInfo($data['session_user']);

            if ($session_user_info) {
                Db::name('kefu_user')->where('id', $session_user_info['id'])->update([
                    'nickname' => $data['new_nickname'],
                ]);

                $new_nickname = $session_user_info['fu_nickname'] ? $session_user_info['fu_nickname'] . '(' . $data['new_nickname'] . ')' : $data['new_nickname'];

                $list_nickname = $session_user_info['fu_nickname'] ? $session_user_info['fu_nickname'] : $data['new_nickname'];

                Gateway::sendToClient($client_id, json_encode([
                    'code'    => 1,
                    'msgtype' => 'action_session',
                    'data'    => [
                        'action'        => 'edit_nickname',
                        'session_user'  => $data['session_user'],
                        'new_nickname'  => $new_nickname,
                        'list_nickname' => $list_nickname,
                    ],
                ]));
            } else {
                common::showMsg($client_id, '昵称修改失败，请重试~');
            }
        }

    }

    /*
     * 输入状态更新
     */
    public function messageInput($client_id, $data)
    {
        if (!isset($data['session_id']) || !isset($data['type']) || !isset($data['session_user'])) {
            return;
        }

        $toMessage = [
            'msgtype' => 'message_input',
            'data'    => [
                'session_id' => $data['session_id'],
                'type'       => $data['type'],
            ]
        ];

        if (Gateway::isUidOnline($data['session_user'])) {
            Gateway::sendToUid($data['session_user'], json_encode($toMessage));
        }
    }

    /*
    * 发送消息
    */
    public function sendMessage($client_id, $data)
    {

        if (!isset($data['session_id'])) {
            common::showMsg($client_id, '发送失败,会话找不到啦！');
            return;
        }

        if (!isset($data['message']) || $data['message'] == '') {
            common::showMsg($client_id, '请输入消息内容！');
            return;
        }

        if (!isset($data['message_type'])) {
            common::showMsg($client_id, '消息类型错误！');
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            common::showMsg($client_id, '无法确认当前您的身份，请刷新重试！');
            return;
        }

        // 复检管理员的登录态
        if ($data['modulename'] == 'admin' && !Common::checkAdmin($data['token'])) {
            common::showMsg($client_id, '无法确认当前您的身份，请刷新重试！');
            return;
        }

        $res = Common::chatRecord($data['session_id'], $data['message'], $data['message_type'], false, isset($data['message_id']) ? $data['message_id'] : false);
        Gateway::sendToClient($client_id, json_encode($res));
    }

    /*轨迹记录*/

    public function trajectory($client_id, $data)
    {
        $page_count = 20; //一次加载20条

        if (!isset($data['page'])) {
            $data['page'] = 1;
        }

        if (!isset($data['session_user'])) {
            common::showMsg($client_id, '用户未找到');
            return;
        }

        // 用户信息
        $user_info = Common::userInfo($data['session_user']);

        if (!$user_info) {
            common::showMsg($client_id, '用户未找到');
            return;
        }

        $user_info['session_user'] = $data['session_user'];

        // 是否屏蔽此人
        $user_info['blacklist'] = Db::name('kefu_blacklist')->where('user_id', $user_info['id'])->value('id');

        $trajectory_count = Db::name('kefu_trajectory')->where('user_id', $user_info['id'])->count('id');

        $page_number = ceil($trajectory_count / $page_count);

        if ($data['page'] == 1) {
            $min = 0;
        } else {
            $min = ($data['page'] - 1) * $page_count;
        }

        $trajectory = Db::name('kefu_trajectory')
            ->where('user_id', $user_info['id'])
            ->limit($min, $page_count)
            ->order('id desc')
            ->select();

        // 最后的一条消息
        $last_message = [
            'last_time'    => Common::formatSessionTime(null),
            'last_message' => '无轨迹记录',
        ];

        if (!$trajectory) {

            $now_ymd                     = date('Y-m-d');
            $trajectory_temp[$now_ymd][] = [
                'id'         => 1,
                'note'       => '无轨迹记录',
                'log_type'   => 7,
                'createtime' => Common::formatTime(null),
            ];

        } else {

            if ($data['page'] == 1) {
                $last_message = [
                    'last_time'    => Common::formatSessionTime($trajectory[0]['createtime']),
                    'last_message' => $trajectory[0]['log_type'] == 0 ? '访问 ' . $trajectory[0]['url'] : $trajectory[0]['note'],
                ];
                if (!isset($data['platform']) || $data['platform'] != 'uni') {
                    $trajectory = array_reverse($trajectory, false);
                }
            }

            // 按天分组
            $trajectory_temp = [];

            foreach ($trajectory as $key => $value) {

                $createtime          = date('Y-m-d', $value['createtime']);
                $value['createtime'] = date('H:i', $value['createtime']);

                $trajectory_temp[$createtime][] = $value;
            }
        }

        Gateway::sendToClient($client_id, json_encode([
            'msgtype' => 'trajectory',
            'code'    => 1,
            'data'    => [
                'trajectory'   => $trajectory_temp,
                'user_info'    => $user_info,
                'last_message' => $last_message,
                'next_page'    => ($data['page'] >= $page_number) ? 'done' : $data['page'] + 1,
                'page'         => $data['page'],
            ],
        ]));
    }

    /*搜索用户*/

    public function searchUser($client_id, $keywords)
    {

        $csr_info = Common::userInfo($_SESSION['user_id']);

        if ($csr_info['source'] != 'csr') {
            return;
        }

        // 读取会话列表
        $session = Db::name('kefu_session')
            ->alias('s')
            ->field('s.*,CONCAT(u.id,"||user") as session_user,u.user_id as fu_user_id,u.avatar,u.nickname,fu.avatar as fu_avatar,fu.nickname as fu_nickname')
            ->join('kefu_user u', 'u.id=s.user_id')
            ->join('user fu', 'u.user_id=fu.id', 'LEFT')
            ->where('s.csr_id', $csr_info['id'])
            ->where('s.deletetime', null)
            ->limit(40)
            ->order('s.createtime desc')
            ->select();

        foreach ($session as $key => $value) {

            // 最后一条聊天记录
            $last_message = Db::name('kefu_record')
                ->where('session_id', $value['id'])
                ->order('createtime desc')
                ->find();

            $session[$key]['last_message'] = Common::formatMessage($last_message);
            $session[$key]['last_time']    = Common::formatSessionTime($last_message['createtime']);
            $session[$key]['avatar']       = $value['fu_avatar'] ? $value['fu_avatar'] : $session[$key]['avatar'];
            $session[$key]['nickname']     = $value['fu_nickname'] ? $value['fu_nickname'] . '(' . $session[$key]['nickname'] . ')' : $session[$key]['nickname'];
            $session[$key]['avatar']       = Common::imgSrcFill($value['avatar'], true);
            $session[$key]['online']       = Gateway::isUidOnline($value['session_user']);
        }

        $invitation          = Gateway::getAllUidList();
        $invitation_user_ids = [];

        foreach ($invitation as $key => $value) {
            $invitation_user_id = explode('||', $value);

            if (isset($invitation_user_id[1]) && $invitation_user_id[1] != 'csr' && (int)$invitation_user_id[0] > 0) {
                $invitation_user_ids[] = (int)$invitation_user_id[0];
            }
        }

        $invitation_user_ids = implode(',', $invitation_user_ids);
        $invitation          = Db::name('kefu_user')
            ->alias('u')
            ->field('u.id,u.avatar,u.nickname,u.createtime,s.id as sid')
            ->join('kefu_session s', 's.user_id=u.id', 'LEFT')
            ->whereIn('u.id', $invitation_user_ids)
            ->where('s.id', null)
            ->where('u.nickname', 'like', '%' . $keywords . '%')
            ->select();

        foreach ($invitation as $key => $value) {
            $invitation[$key]['id']               = 'invitation||' . $value['id'];
            $invitation[$key]['avatar']           = Common::imgSrcFill($value['avatar'], true);
            $invitation[$key]['online']           = 1;
            $invitation[$key]['unread_msg_count'] = 0;
            $invitation[$key]['last_message']     = '';
            $invitation[$key]['session_user']     = $value['id'] . '||user';
            $invitation[$key]['last_time']        = Common::formatSessionTime($value['createtime']);
        }

        $user_list      = array_merge($invitation, $session);
        $user_list_temp = [];
        foreach ($user_list as $key => $value) {
            if (strstr($value['nickname'], $keywords)) {
                $user_list_temp[] = $value;
            }
        }
        unset($user_list);

        Gateway::sendToClient($client_id, json_encode(['msgtype' => 'search_user', 'data' => $user_list_temp]));
    }

    /*拉黑名单*/
    public function blacklist($client_id, $data)
    {
        if (!isset($data['session_user']) || !$data['session_user']) {
            common::showMsg($client_id, '用户找不到啦！');
            return;
        }

        $user_info = Common::userInfo($data['session_user']);

        if (!$user_info) {
            common::showMsg($client_id, '用户找不到啦！');
            return;
        }

        $blacklist = Db::name('kefu_blacklist')->where('user_id', $user_info['id'])->value('id');

        if ($blacklist) {

            if (Db::name('kefu_blacklist')->where('user_id', $user_info['id'])->delete()) {
                Gateway::sendToClient($client_id, json_encode([
                    'code'    => 1,
                    'msgtype' => 'blacklist',
                    'data'    => [
                        'action'       => 'del',
                        'session_user' => $data['session_user'],
                    ],
                ]));
            } else {
                common::showMsg($client_id, '取消拉黑失败，请重试！');
                return;
            }
            return;
        }

        $csr_info  = Common::userInfo($_SESSION['user_id']);
        $blacklist = [
            'user_id'    => $user_info['id'],
            'admin_id'   => $csr_info['id'],
            'createtime' => time(),
        ];

        if (Db::name('kefu_blacklist')->insert($blacklist)) {

            $black_client_id = Gateway::getClientIdByUid($data['session_user']);
            foreach ($black_client_id as $key => $value) {
                Gateway::sendToClient($value, json_encode([
                    'code'    => 0,
                    'msgtype' => 'clear',
                ]));
            }

            Gateway::sendToClient($client_id, json_encode([
                'code'    => 1,
                'msgtype' => 'blacklist',
                'data'    => [
                    'action'          => 'add',
                    'session_user'    => $data['session_user'],
                    'black_client_id' => $black_client_id,
                ],
            ]));
        } else {
            common::showMsg($client_id, '拉黑失败，请重试！');
            return;
        }
    }
}
