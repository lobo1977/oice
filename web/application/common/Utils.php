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
     * 计算时间跨度
     */
    public static function timeSpan($time) {
        $now = time();
        $unixTime = strtotime($time);

        $span = $now - $unixTime;
        if ($span < 5 * 60) {
            return '刚刚';
        } else if ($span < 60 * 60) {
            $minutes = round($span / 60);
            return $minutes . '分钟前';
        } else if ($span < 60 * 60 * 24) {
            $hours = round($span / (60 * 60));
            return $hours . '小时前';
        } else if ($span < 60 * 60 * 24 * 30) {
            $days = round($span / (60 * 60 * 24));
            return $days . '天前';
        } else if ($span < 60 * 60 * 24 * 360) {
            $days = round($span / (60 * 60 * 24 * 30));
            return $days . '个月前';
        } else {
            return date('Y年n月j日', $unixTime);
        }
    }

    /**
     * 格式化日期时间
     */
    public static function formatDatetime($datetime) {
      $time = 0;
      
      if (Utils::isValidDate($datetime)) {
        $time = strtotime($datetime);
      } else if (is_numeric($datetime)) {
        $time = floatval($datetime);
        if ($time > 10000000000) {
          $time = $time / 1000;
        }
      }

      if ($time > 0) {
        if (date('H:i', $time) == '00:00') {
          return date('Y-m-d', $time);
        } else {
          return date('Y-m-d H:i', $time);
        }
      }

      return '';
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


    /**
     * 地图坐标系转换
     * GCJ02 TO BD09
     */
    public static function ConvertGCJ02ToBD09($lat, $lng) {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng;
        $y = $lat;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta) + 0.0065;
        $lat = $z * sin($theta) + 0.006;
        return array('lng'=>$lng,'lat'=>$lat);
    }

    /**
     * 地图坐标系转换
     * 腾讯地图用的也是GCJ02坐标
     * BD09 TO GCJ02
     */
    public static function ConvertBD09ToGCJ02($lat, $lng) {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta);
        $lat = $z * sin($theta);
        return array('lng'=>$lng,'lat'=>$lat);
    }
}