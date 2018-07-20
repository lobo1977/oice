<?php
namespace app\api\model;

use app\api\model\Base;
use app\api\model\Log;
use app\api\model\User;
use app\api\model\Customer;
use app\api\model\Company;
use app\api\model\Building;
use app\api\model\Unit;
use app\api\model\File;

class Confirm extends Base
{
  /**
   * 客户确认列表
   */
  public static function query($customer_id, $building_id, $user_id) {
    $list = self::alias('a')
      ->join('customer c', 'a.customer_id = c.id')
      ->leftJoin('unit u', 'a.unit_id = u.id AND a.unit_id > 0')
      ->join('building b', 'a.building_id = b.id OR u.building_id = b.id');

    if ($customer_id) {
      $list->where('a.customer_id', $customer_id);
    }

    if ($building_id) {
      $list->where('a.building_id', $building_id);
    }

    if ($user_id) {
      $list->where('a.user_id', $user_id);
    }

    $result = $list->field('a.*,b.building_name,u.building_no,u.floor,u.room,c.customer_name')
      ->order('a.create_time', 'desc')
      ->select();

    foreach($result as $key => $confirm) {
      if ($building_id) {
        $confirm->title = $confirm->customer_name;
      } else {
        $confirm->title = $confirm->building_name;
        Unit::formatInfo($confirm);
      }
      $confirm->desc = $confirm->create_time;
    }

    return $result;
  }
  
  /**
   * 生成客户确认
   */
  public static function addNew($cid, $bid, $uid, $user_id) {
    $find = self::where('customer_id', $cid)
      ->where('building_id', $bid)
      ->where('unit_id', $uid)->find();

    $customer = Customer::get($cid);
    $building = null;
    $unit = null;

    if ($customer == null) {
      self::exception('客户不存在。');
    }

    if ($uid) {
      $unit = Unit::get($uid);
      if ($unit) {
        Unit::formatInfo($unit);
        $building = Building::get($unit->building_id);
      } else {
        self::exception('单元不存在。');
      }
    } else if ($bid) {
      $building = Building::get($bid);
    }

    if ($building == null) {
      self::exception('项目不存在。');
    }

    if ($find == null) {
      $confirm = new Confirm;
      $confirm->customer_id = $cid;
      $confirm->user_id = $user_id;
      $confirm->building_id = $bid;
      $confirm->unit_id = $uid;
      $result = $confirm->save();

      if ($result) {
        $summary = $building->building_name;
        if ($unit != null) {
          $summary = $summary . $unit->title;
        }

        Log::add([
          "table" => "customer",
          "owner_id" => $cid,
          "title" => '生成客户确认书',
          "summary" => $summary,
          "user_id" => $user_id
        ]);
      } else {
        self::exception('系统异常。');
      }
    } else {
      $result = 1;
    }
    Customer::changeStatus($cid, 3, $user_id);
    return $result;
  }
}