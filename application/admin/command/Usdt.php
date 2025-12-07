<?php

namespace app\admin\command;

use app\admin\model\keerta\kline\Kline;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Usdt extends Command
{

    protected function configure()
    {
        $this->setName('usdt')
            ->setDescription('usdt');
    }

    /**
     * @param Input $input
     */
    protected function execute(Input $input, Output $output)
    {
        $url = 'https://www.okx.com/api/v5/market/exchange-rate';
        $res = \fast\Http::sendRequest($url, [], 'GET');
        if($res['ret']){
            $res = json_decode($res['msg'], true);
            if ($res['code'] == 0){
                $usdCny = $res['data'][0]['usdCny'];

                $kline = Kline::where('id',1)->find();
                if(!$kline){
                    Kline::create([
                        'price'=>$usdCny
                    ]);
                }else{
                    if($kline->price != $usdCny){
                        $kline->price = $usdCny;

                        $output->write(date("Y-M-D H:i:s") . ' 实时汇率更新成功：' .$usdCny);
                    }else{
                        $kline->updatetime = time();
                        $output->write(date("Y-M-D H:i:s") . ' 汇率未更新：' .$usdCny);
                    }
                    $kline->save();
                }
            }
        }
        exit();
    }

}