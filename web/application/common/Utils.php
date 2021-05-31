<?php

namespace app\common;

require_once '../extend/phpqrcode/phpqrcode.php';

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
  public static function timeSpan($time)
  {
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
  public static function formatDatetime($datetime)
  {
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
  public static function emojiToChar($str)
  {
    return preg_replace_callback(
      '#<span class=\"emoji emoji([A-Fa-f\d]+)\"><\/span>#',
      create_function(
        '$matches',
        'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'
      ),
      $str
    );
  }

  /**
   *  判断是否是微信客户端
   */
  public static function isWechat()
  {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
      return true;
    }
    return false;
  }

  /**
   * 判断是否是QQ客户端
   */
  public static function isQQ()
  {
    if (strpos($_SERVER['HTTP_USER_AGENT'], ' QQ/') !== false) {
      return true;
    }
    return false;
  }

  /**
   * 获取文件扩展名
   */
  public static function getFileExt($fileName, $includeDot = false)
  {
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
  public static function replaceExt($fileName, $newExt)
  {
    $pos = strrpos($fileName, '.');
    if ($pos) {
      $fileName = substr($fileName, 0, $pos);
    }
    return $fileName . '.' . $newExt;
  }

  /**
   * 通过文件名判断是否为图片文件
   */
  public static function isImageFile($fileName = '')
  {
    if (!$fileName) {
      return false;
    }
    $exts = array('jpg', 'jpeg', 'png', 'gif', 'bmp');
    $ext = strtolower(self::getFileExt($fileName));
    return in_array($ext, $exts);
  }

  /**
   * 通过文件名判断是否为视频文件
   */
  public static function isVideoFile($fileName = '')
  {
    if (!$fileName) {
      return false;
    }
    $exts = array('mp4');
    $ext = strtolower(self::getFileExt($fileName));
    return in_array($ext, $exts);
  }

  /**
   * 判断是否为PDF文件
   */
  public static function isPdfFile($fileName = '')
  {
    if (!$fileName) {
      return false;
    }
    $exts = array('pdf');
    $ext = strtolower(self::getFileExt($fileName));
    return in_array($ext, $exts);
  }

  /**
   * 截取视频某一帧
   *
   * @param  $file  	视频文件
   * @param  $savePath     截图保存路径
   * @param  $time    第几帧
   */
  public static function getVideoCover($video, $savePath, $time = 3)
  {
    $ffmpeg = \FFMpeg\FFMpeg::create(config('ffmpeg.'));
    $video = $ffmpeg->open($video);
    $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($time))
      ->save($savePath);
  }


  /**
   * 地图坐标系转换
   * GCJ02 TO BD09
   */
  public static function ConvertGCJ02ToBD09($lat, $lng)
  {
    $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    $x = $lng;
    $y = $lat;
    $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
    $theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
    $lng = $z * cos($theta) + 0.0065;
    $lat = $z * sin($theta) + 0.006;
    return array('lng' => $lng, 'lat' => $lat);
  }

  /**
   * 地图坐标系转换
   * 腾讯地图用的也是GCJ02坐标
   * BD09 TO GCJ02
   */
  public static function ConvertBD09ToGCJ02($lat, $lng)
  {
    $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    $x = $lng - 0.0065;
    $y = $lat - 0.006;
    $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
    $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
    $lng = $z * cos($theta);
    $lat = $z * sin($theta);
    return array('lng' => $lng, 'lat' => $lat);
  }

  /**
   * 由长连接生成短链接操作
   *
   * 算法描述：使用6个字符来表示短链接，我们使用ASCII字符中的'a'-'z','0'-'9','A'-'Z'，共计62个字符做为集合。
   *      每个字符有62种状态，六个字符就可以表示62^6（56800235584），那么如何得到这六个字符，
   *           具体描述如下：
   *  1. 对传入的长URL+设置key值 进行Md5，得到一个32位的字符串(32 字符十六进制数)，即16的32次方；
   *        2. 将这32位分成四份，每一份8个字符，将其视作16进制串与0x3fffffff(30位1)与操作, 即超过30位的忽略处理；
   *  3. 这30位分成6段, 每5个一组，算出其整数值，然后映射到我们准备的62个字符中, 依次进行获得一个6位的短链接地址。
   *
   * @author flyer0126
   * @since 2012/07/13
   */
  public static function shortUrl($long_url)
  {
    $key = config('app_host');
    $base32 = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    // 利用md5算法方式生成hash值
    $hex = hash('md5', $long_url . $key);
    $hexLen = strlen($hex);
    $subHexLen = $hexLen / 8;
    $output = array();

    for ($i = 0; $i < $subHexLen; $i++) {
      // 将这32位分成四份，每一份8个字符，将其视作16进制串与0x3fffffff(30位1)与操作
      $subHex = substr($hex, $i * 8, 8);
      $idx = 0x3FFFFFFF & (1 * ('0x' . $subHex));

      // 这30位分成6段, 每5个一组，算出其整数值，然后映射到我们准备的62个字符
      $out = '';
      for ($j = 0; $j < 6; $j++) {
        $val = 0x0000003D & $idx;
        $out .= $base32[$val];
        $idx = $idx >> 5;
      }
      $output[$i] = $out;
    }

    return $output[rand(0, 3)];
  }

  /**
   * 生成二维码
   */
  public static function qrcode($code, $addLogo = false)
  {
    $qrCode = new \QRcode();

    $errorCorrectionLevel = 'Q';      //容错级别  
    $matrixPointSize = 6;          //生成图片大小
    $margin = 2;
    $logoFile = 'static/img/logo_q.png';
    $codeFile = 'qrcode/' . microtime() . '.png';
    $qrCode->png($code, $codeFile, $errorCorrectionLevel, $matrixPointSize, $margin);
    $QR = imagecreatefromstring(file_get_contents($codeFile));

    if ($addLogo && file_exists($logoFile)) {
      $logo = imagecreatefromstring(file_get_contents($logoFile));
      $QR_width = imagesx($QR);      //二维码图片宽度   
      $QR_height = imagesy($QR);      //二维码图片高度   
      $logo_width = imagesx($logo);    //logo图片宽度   
      $logo_height = imagesy($logo);    //logo图片高度   
      $logo_qr_width = $QR_width / 5;     //组合之后logo的宽度(占二维码的1/5)
      $scale = $logo_width / $logo_qr_width;     //logo的宽度缩放比(本身宽度/组合后的宽度)
      $logo_qr_height = $logo_height / $scale;  //组合之后logo的高度
      $from_width = ($QR_width - $logo_qr_width) / 2;   //组合之后logo左上角所在坐标点

      //重新组合图片并调整大小
      imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
      imagedestroy($logo);
      unlink($codeFile);
    }

    return $QR;
  }
}
