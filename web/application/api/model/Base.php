<?php
namespace app\api\model;

use think\Model;
use app\common\AppException;

class Base extends Model {
  protected static $city = '北京';

  protected static function exception($message) {
    throw new AppException($message);
  }
}