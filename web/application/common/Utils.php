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
            if (!$includeDot) {
                $pos = $pos + 1;
            }
            $ext = strtolower(substr($fileName, $pos));
        } else if ($includeDot) {
            $ext = '.';  
        }

        return $ext;
    }

        /**
     * 获取文件扩展名
     */
    public static function replaceExt($fileName, $newExt) {
        $pos = strrpos($fileName, '.');
        if ($pos) {
            $fileName = substr($fileName, 0, $pos);
        }
        return $fileName . '.' . $newExt;
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

    /**
     * 通过文件名判断是否为视频文件
     */
    public static function isVideoFile($fileName = '') {
        if (!$fileName) {
            return false;
        }
        $images = array('mp4');
        $ext = strtolower(self::getFileExt($fileName));
        return in_array($ext, $images);
    }

    /**
     * 截取视频某一帧
     *
     * @param  $file  	视频文件
     * @param  $savePath     截图保存路径
     * @param  $time    第几帧
     */
    public static function getVideoCover($video, $savePath, $time = 3) {
        $ffmpeg = \FFMpeg\FFMpeg::create(config('ffmpeg.'));
        $video = $ffmpeg->open($video);
        $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($time))
            ->save($savePath);
    }
}