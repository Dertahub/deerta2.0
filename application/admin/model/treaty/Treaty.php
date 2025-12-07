<?php

namespace app\admin\model\treaty;

use fast\Random;
use think\Model;


class Treaty extends Model
{





    // 表名
    protected $name = 'treaty_info';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];


    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }








    public function user()
    {
        return $this->belongsTo('app\admin\model\User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function treatycategory()
    {
        return $this->belongsTo('app\admin\model\treaty\Category', 'category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public static function exportPdf($treaty_info, $treaty_category){
        if(!empty($treaty_info["images"])){
            $treaty_info["images"] = json_decode($treaty_info["images"], true);
        }
        $config_file = ADDON_PATH . "treaty" . DS . 'library' . DS . 'TCPDF-main' . DS . "tcpdf.php";
        require_once($config_file);
//        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle($treaty_category["name"]."_".Random::build());
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont('courier');

        // 设置间距
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // 设置分页
        $pdf->SetAutoPageBreak(TRUE, 25);

        // set image scale factor
        $pdf->setImageScale(1.25);

        // set default font subsetting mode
        $pdf->setFontSubsetting(true);


        //设置签名
        /*
        如果要添加签名请打开下面的代码 需要自己在服务器根据下方的NOTES生成crt文件
        https://tcpdf.org/examples/example_052/
        NOTES:
         - To create self-signed signature: openssl req -x509 -nodes -days 365000 -newkey rsa:1024 -keyout tcpdf.crt -out tcpdf.crt
         - To export crt to p12: openssl pkcs12 -export -in tcpdf.crt -out tcpdf.p12
         - To convert pfx certificate to pem: openssl pkcs12 -in tcpdf.pfx -out tcpdf.crt -nodes
        */
//        $certificate = "file://".ADDON_PATH . "treaty" . DS . 'library' . DS . 'crt' . DS . "tcpdf.crt";
//        $info = array(
//            'Name' => 'TCPDF',
//            'Location' => 'Office',
//            'Reason' => 'Testing TCPDF',
//            'ContactInfo' => 'http://www.tcpdf.org',
//        );
//        $pdf->setSignature($certificate, $certificate, '', '', 2, $info);



        //设置字体
        $pdf->SetFont('stsongstdlight');

        $pdf->AddPage();
        $html = $treaty_info["content"];
        /*if($treaty_info["images"]){
            foreach ($treaty_info["images"] as $key => $value){
                $path = self::getImgUrl($value["url"]);
                $html = str_replace("【".$value['name']."】", "<img src=\"{$path}\" style=\"width: 80px;height: 40px\">",$html);
            }
        }*/
        //签章
        /*if($treaty_category['official_seal_image']){
            $official_seal_image = self::getImgUrl($treaty_category['official_seal_image']);
            $html = str_replace("【gongzhang】", "<img src=\"{$official_seal_image}\" style=\"position:absolute;left:100px;top:150px;width: 120px;height: 120px\">",$html);
        }*/



        // output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        //输出PDdF
        $pdf->Output(date("Y-m-d H:i:s",$treaty_info['createtime']).'.pdf', 'I');exit;
    }

    public static function getImgUrl($url){
        $preg = "/^http(s)?:\\/\\/.+/";
        if(preg_match($preg,$url))
        {
            $path = $url;
        }else
        {
            $path = request()->domain().$url;
        }
        return $path;
    }
}
