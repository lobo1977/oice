<?php
namespace app\common;

class Utils
{
    /**
     * 生成随机字符串
     * @param length 字符串长度
     */
    public static function getRandChar($length)
    {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)]; //rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }
    
    /**
     * 生成随机数字符串
     * @param length 字符串长度
     */
    public static function getRandNumber($length)
    {
        $str = null;
        $strPol = "0123456789";
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];
        }

        return $str;
    }

    /**
     * 表情符号转义
     */
    public static function emojiToChar($str) {
        return preg_replace_callback('#<span class=\"emoji emoji([A-Fa-f\d]+)\"><\/span>#',
            create_function(
                '$matches',
                'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
            ),
            $str);
    }

    /**
     *  判断是否是微信客户端 
     */
    public static function isWechat() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }

    /**
     * 判断是否是QQ客户端 
     */
    public static function isQQ() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], ' QQ/') !== false) {
            return true;
        }
        return false;
    }

    /**
     * 获取文件扩展名
     */
    public static function getFileExt($fileName, $includeDot = false) {
        $ext = '';
        $pos = strrpos($fileName, '.');
        if ($pos) {
            if ($includeDot) {
                $ext = substr($fileName, $pos);
            } else {
                $ext = substr($fileName, $pos + 1);
            }
        } else if ($includeDot) {
            $ext = '.';  
        }

        return $ext;
    }

    /**
     * 通过文件名判断是否为图片文件
     */
    public static function isImageFile($fileName = '') {
        if (!$fileName) {
            return false;
        }
        $images = array('jpg','jpeg','png','gif','bmp');
        $ext = strtolower(self::getFileExt($fileName));
        return in_array($ext, $images);
    }
}