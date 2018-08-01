<?php
namespace app\api\model;

use app\api\model\Base;
use app\api\model\Log;
use app\api\model\Customer;
use app\api\model\Unit;

class Filter extends Base
{
  /**
   * 客户筛选表
   */
  public static function query($user, $customer_id) {
    $list = self::alias('a')
      ->leftJoin('unit u', 'a.unit_id = u.id AND a.unit_id > 0')
      ->join('building b', 'a.building_id = b.id OR u.building_id = b.id')
      ->where('a.customer_id', $customer_id)
      ->where('a.user_id', $user->id)
      ->field('a.building_id,b.building_name,b.level,b.area,b.district,b.address,b.price,
        a.unit_id,u.building_no,u.floor,u.room,u.acreage,u.rent_price,u.sell_price')
      ->order('a.sort', 'asc')
      ->order('a.create_time', 'asc')
      ->select();

    foreach($list as $key => $building) {
      $building->title = $building->building_name;
      Unit::formatInfo($building);

      $building->desc = (empty($building->level) ? '' : $building->level . '级 ') .
        (empty($building->area) ? '' : $building->area . ' ') .
        (empty($building->district) ? '' : $building->district . ' ');

      if (!empty($building->acreage)) {
        $building->desc = $building->desc . $building->acreage . '平米 ';
      }

      if (!empty($building->rent_price)) {
        $building->desc = $building->desc . $building->rent_price . '元/平米/天 ';
      } else if (!empty($building->sell_price)) {
        $building->desc = $building->desc . $building->sell_price . '元/平米 ';
      } else if (!empty($building->price)) {
        $building->desc = $building->desc . $building->price;
      }

      if (!empty($building->unit_id)) {
        $building->url = '/unit/view/' . $building->unit_id;
        $building->building_id = 0;
      } else {
        $building->url = '/building/view/' . $building->building_id;
        $building->unit_id = 0;
      }

      $building->checked = false;
    }

    return $list;
  }

  /**
   * 添加推荐项目
   */
  public static function addBuilding($user, $customer_id, $building_id, $unit_id) {
    $customer = Customer::get($id);
    if ($customer == null) {
      self::exception('客户不存在。');
    } else if (!self::allow($user, $customer, 'follow')) {
      self::exception('您没有权限跟进此客户。');
    }

    if (!self::where('customer_id', $customer_id)
      ->where('building_id', $building_id)
      ->where('unit_id', $unit_id)
      ->where('user_id', $user->id)->find()) {

      $maxSort = self::where('customer_id', $customer_id)
        ->where('user_id', $user->id)->max('sort');

      $filter = new Filter;
      $filter->customer_id = $customer_id;
      $filter->building_id = $building_id;
      $filter->unit_id = $unit_id;
      $filter->user_id = $user->id;
      $filter->sort = $maxSort + 1;
      $result = $filter->save();

      if ($result) {
        Log::add($user, [
          "table" => 'customer',
          "owner_id" => $customer->id,
          "title" => '筛选项目'
        ]);
      }

      return $result;
    } else {
      return 1;
    }
  }

  /**
   * 项目排序
   */
  public static function sort($user, $customer_id, $building_id, $unit_id, $up) {
    $data = self::where('customer_id', $customer_id)
      ->where('building_id', $building_id)
      ->where('unit_id', $unit_id)
      ->where('user_id', $user->id)->find();

    if ($data) {
      $oldSort = $data->sort;
      if ($up == 1) {  //上移
        $newSort = $oldSort - 1;
      } else {         //下移
        $newSort = $oldSort + 1;
      }

      $nearData = self::where('customer_id', $customer_id)
        ->where('user_id', $user->id)
        ->where('sort', $newSort)
        ->find();

      if ($nearData) {
        $nearData->sort = $oldSort;
        $nearData->save();
        $data->sort = $newSort;
        return $data->save();
      }
    }

    return 0;
  }

  /**
   * 删除推荐项目
   */
  public static function removeBuilding($user, $customer_id, $building_id, $unit_id) {
    $customer = Customer::get($id);
    if ($customer == null) {
      self::exception('客户不存在。');
    } else if (!self::allow($user, $customer, 'follow')) {
      self::exception('您没有权限跟进此客户。');
    }

    $data = self::where('customer_id', $customer_id)
      ->where('building_id', $building_id)
      ->where('unit_id', $unit_id)
      ->where('user_id', $user->id)->find();
    
    if ($data == null) {
      return true;
    }

    $sort = $data->sort;

    // 重新排序
    db('filter')->where('customer_id', $customer_id)
      ->where('user_id', $user->id)
      ->where('sort', '>', $sort)
      ->setDec('sort', 1);

    $result = $data->delete();

    if ($result) {
      Log::add($user, [
        "table" => 'customer',
        "owner_id" => $customer->id,
        "title" => '筛选项目'
      ]);
    }

    return $result;
  }
}