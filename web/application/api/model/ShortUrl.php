<?php
namespace app\api\model;

use app\common\Utils;
use app\api\model\Base;

class ShortUrl extends Base
{
  /**
   * 生成短链接
   */
  public static function generate($url = '') {
    $host = 'http://t.o-ice.com/';

    if (empty($url)) {
      return $url;
    }

    $find = self::where('url', $url)->find();
    if ($find) {
      return $host . $find('id');
    } else {
      $id = Utils::shortUrl($url);
      if ($id) {
        $shortUrl = new ShortUrl([
          'id' => $id,
          'url' => $url
        ]);
        $shortUrl->save();
        return $host . $id;
      } else {
        return $url;
      }
    }
  }
}