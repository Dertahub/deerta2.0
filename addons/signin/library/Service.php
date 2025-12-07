<?php

namespace addons\signin\library;

use addons\signin\model\Signin;
use app\common\library\Auth;
use DateTime;
use fast\Date;
use stdClass;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class Service
{
    /**
     * 获取排名信息
     *
     * @param int $user_id
     * @return array
     */
    public static function getRankInfo($user_id = null)
    {
        $user_id = is_null($user_id) ? Auth::instance()->id : $user_id;
        $rankList = Signin::with(["user"])
            ->where("createtime", ">", \fast\Date::unixtime('day', -1))
            ->field("user_id,MAX(successions) AS days")
            ->group("user_id")
            ->order("days DESC,createtime ASC")
            ->limit(10)
            ->select();
        foreach ($rankList as $index => $datum) {
            $datum->getRelation('user')->visible(['id', 'username', 'nickname', 'avatar']);
        }

        $ranking = 0;
        $lastdata = Signin::where('user_id', $user_id)->order('createtime', 'desc')->find();
        //是否已签到
        $checked = $lastdata && $lastdata['createtime'] >= Date::unixtime('day') ? true : false;
        //连续登录天数
        $successions = $lastdata && $lastdata['createtime'] >= Date::unixtime('day', -1) ? $lastdata['successions'] : 0;
        if ($successions > 0) {
            //优先从列表中取排名
            foreach ($rankList as $index => $datum) {
                if ($datum->user_id == $user_id) {
                    $ranking = $index + 1;
                    break;
                }
            }
            if (!$ranking) {
                $prefix = config('database.prefix');
                $ret = Db::query("SELECT COUNT(*) nums FROM (SELECT user_id,MAX(successions) days FROM `{$prefix}signin` WHERE createtime > " . Date::unixtime('day', -1) . " GROUP BY user_id ORDER BY days) AS dd WHERE dd.days >= " . $successions);
                $ranking = $ret[0]['nums'] ?? 0;
            }
        }
        return [$rankList, $ranking, $successions, $checked];
    }

    public static function dosign($user_id = null)
    {
        $config = get_addon_config('signin');
        $signdata = $config['signinscore'];

        $user_id = $user_id ?: Auth::instance()->id;

        $successions = 0;
        $score = 0;

        // 开始事务
        Db::startTrans();
        try {
            // 使用FOR UPDATE锁定查询，防止并发
            $signin = \addons\signin\model\Signin::where('user_id', $user_id)
                ->whereTime('createtime', 'today')
                ->lock(true)
                ->find();

            if ($signin) {
                Db::rollback();
                throw new SigninException('今天已签到,请明天再来!');
            }

            // 查询最后一次签到记录并锁定
            $lastdata = \addons\signin\model\Signin::where('user_id', $user_id)
                ->order('createtime', 'desc')
                ->lock(true)
                ->find();

            $successions = $lastdata && $lastdata['createtime'] > Date::unixtime('day', -1) ? $lastdata['successions'] : 0;
            $successions++;
            $score = $signdata['s' . $successions] ?? $signdata['sn'];

            // 创建签到记录
            \addons\signin\model\Signin::create(['user_id' => $user_id, 'successions' => $successions, 'createtime' => time()]);
            \app\common\model\User::score($score, $user_id, "连续签到{$successions}天");

            // 提交事务
            Db::commit();
        } catch (SigninException $e) {
            Db::rollback();
            return ['errmsg' => $e->getMessage()];
        } catch (Exception $e) {
            Db::rollback();
            return ['errmsg' => '签到失败,请稍后重试'];
        }
        return ['successions' => $successions, 'score' => $score];
    }

    public static function fillup($date, $user_id = null)
    {
        $time = strtotime($date);
        $auth = Auth::instance();
        if (!$auth->id) {
            return ['errmsg' => '请登录后再操作'];
        }
        $config = get_addon_config('signin');
        if (!$config['isfillup']) {
            return ['errmsg' => '暂未开启签到补签'];
        }
        if ($time > time()) {
            return ['errmsg' => '无法补签未来的日期'];
        }
        if ($config['fillupscore'] > $auth->score) {
            return ['errmsg' => '你当前积分不足'];
        }
        $days = Date::span(time(), $time, 'days');
        if ($config['fillupdays'] < $days) {
            return ['errmsg' => "只允许补签{$config['fillupdays']}天的签到"];
        }
        $count = \addons\signin\model\Signin::where('user_id', $user_id)
            ->where('type', 'fillup')
            ->whereTime('createtime', 'between', [Date::unixtime('month'), Date::unixtime('month', 0, 'end')])
            ->count();
        if ($config['fillupnumsinmonth'] <= $count) {
            return ['errmsg' => "每月只允许补签{$config['fillupnumsinmonth']}次"];
        }

        // 开始事务
        Db::startTrans();

        try {
            // 使用FOR UPDATE锁定查询，防止并发
            $signin = \addons\signin\model\Signin::where('user_id', $user_id)
                ->where('type', 'fillup')
                ->whereTime('createtime', 'between', [$date, date("Y-m-d 23:59:59", $time)])
                ->lock(true)
                ->find();

            if ($signin) {
                Db::rollback();
                throw new SigninException("该日期无需补签到");
            }

            // 检查是否已有普通签到
            $normalSignin = \addons\signin\model\Signin::where('user_id', $user_id)
                ->whereTime('createtime', 'between', [$date, date("Y-m-d 23:59:59", $time)])
                ->lock(true)
                ->find();

            if ($normalSignin) {
                Db::rollback();
                throw new SigninException("该日期已签到，无需补签");
            }

            $successions = 1;
            $prev = \addons\signin\model\Signin::where('user_id', $user_id)
                ->whereTime('createtime', 'between', [date("Y-m-d", strtotime("-1 day", $time)), date("Y-m-d 23:59:59", strtotime("-1 day", $time))])
                ->lock(true)
                ->find();

            if ($prev) {
                $successions = $prev['successions'] + 1;
            }

            // 扣除积分
            \app\common\model\User::score(-$config['fillupscore'], $user_id, '签到补签');

            // 寻找日期之后的签到记录
            $nextList = \addons\signin\model\Signin::where('user_id', $user_id)
                ->where('createtime', '>=', strtotime("+1 day", $time))
                ->order('createtime', 'asc')
                ->lock(true)
                ->select();

            foreach ($nextList as $index => $item) {
                // 如果是阶段数据，则中止
                if ($index > 0 && $item->successions == 1) {
                    break;
                }
                $day = $index + 1;
                if (date("Y-m-d", $item->createtime) == date("Y-m-d", strtotime("+{$day} day", $time))) {
                    $item->successions = $successions + $day;
                    $item->save();
                }
            }

            // 创建补签记录
            \addons\signin\model\Signin::create([
                'user_id'     => $user_id,
                'type'        => 'fillup',
                'successions' => $successions,
                'createtime'  => $time + 43200
            ]);

            // 提交事务
            Db::commit();
        } catch (PDOException $e) {
            Db::rollback();
            return ['errmsg' => '补签失败,请稍后重试'];
        } catch (SigninException $e) {
            Db::rollback();
            return ['errmsg' => $e->getMessage()];
        } catch (Exception $e) {
            Db::rollback();
            return ['errmsg' => '补签失败,请稍后重试'];
        }
    }
}
