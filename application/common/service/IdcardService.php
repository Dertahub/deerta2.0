<?php

namespace app\common\service;

use Exception;

class IdcardService
{

    /**
     * 身份证号码验证函数
     * @param string $idCard 身份证号码
     * @return true 验证结果和详细信息
     * @throws Exception
     */
    public static function validateIdCard($idCard) {
        // 基本正则验证（18位，最后一位可能是X）
        if (!preg_match('/^[1-9]\d{5}(18|19|20)\d{2}((0[1-9])|(1[0-2]))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/', $idCard)) {
            throw new Exception('身份证格式不正确-101');
        }

        // 提取身份证各部分信息
        $provinceCode = substr($idCard, 0, 2);
        $year = substr($idCard, 6, 4);
        $month = substr($idCard, 10, 2);
        $day = substr($idCard, 12, 2);

        // 验证省份代码（11-91为有效省份代码，具体范围可根据最新行政区划调整）
        $validProvinceCodes = [
            '11', '12', '13', '14', '15', // 华北地区
            '21', '22', '23', // 东北地区
            '31', '32', '33', '34', // 华东地区
            '35', '36', '37', // 华中地区
            '41', '42', '43', '44', '45', '46', // 华南地区
            '50', '51', '52', '53', '54', // 西南地区
            '61', '62', '63', '64', '65', // 西北地区
            '71', '81', '82', '91' // 特别行政区和其他
        ];

        if (!in_array($provinceCode, $validProvinceCodes)) {
            throw new Exception('身份证格式不正确-102');
        }

        // 验证年月日范围
        if (!checkdate($month, $day, $year)) {
            throw new Exception('身份证格式不正确-103');
        }

        // 验证年份范围（当前年份往前推100年）
        $currentYear = date('Y');
        if ($year < ($currentYear - 150) || $year > $currentYear) {
            throw new Exception('身份证格式不正确-104');
        }
        return true;
    }
}