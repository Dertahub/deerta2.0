<?php

namespace addons\treaty\common\library;

class TreatyWord
{
    function start()
    {
        ob_start();
    }

    function save($path)
    {
        $data = ob_get_contents();
        ob_end_clean();
        $this->wirtefile($path, $data);
    }


    function wirtefile($fn, $data)
    {
        $fp = fopen($fn, "wb");
        fwrite($fp, $data);
        fclose($fp);
    }

}
