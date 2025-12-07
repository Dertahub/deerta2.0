<?php

namespace app\admin\command;

use app\common\library\Token;
use fast\Random;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class UserToken extends Command
{
    //Token默认有效时长
    protected $keeptime = 2592000;

    protected function configure()
    {
        $this->setName('user_token')
            ->setDescription('UserToken')
            ->addArgument('id', null, 'The first argument');
    }

    /**
     * @param Input $input
     */
    protected function execute(Input $input, Output $output)
    {
        $id = $input->getArgument('id');
        if (!$id){
            echo '请输入用户id';exit();
        }
        $token = Random::uuid();
        Token::set($token, $id, $this->keeptime);
        echo $id . 'token:' . $token;exit();
    }

}