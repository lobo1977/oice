<?php
namespace app\api\model;

use app\api\model\Base;
use app\api\model\Log;

class Filter extends Base
{
  /**
   * 客户筛选表
   */
  public static function query($cid, $uid) {
    $list = self::alias('a')
      ->leftJoin('unit u', 'a.unit_id = u.id AND a.unit_id > 0')
      ->join('building b', 'a.building_id = b.id OR u.building_id = b.id')
      ->where('a.customer_id', $cid)
      ->where('a.user_id', $uid)
      ->field('a.building_id,b.building_name,b.level,b.area,b.district,b.address,b.price,
        a.unit_id,u.building_no,u.floor,u.room,u.acreage,u.rent_price,u.sell_price')
      ->order('a.sort', 'asc')
      ->order('a.create_time', 'asc')
      ->select();

    foreach($list as $key => $building) {
      $building->title = $building->building_name;
      if (!empty($building->building_no)) {
        $building->title = $building->title . $building->building_no;
      } 
      if (!empty($building->floor)) {
        if ($building->floor > 0) {
          $building->title = $building->title . $building->floor . '层';
        } else if ($building->floor < 0) {
          $building->title = $building->title . '地下' . abs($building->floor) . '层';
        }
      }
      if (!empty($building->room)) {
        $building->title = $building->title . $building->room;
      }

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
  public static function addBuilding($cid, $bid, $unit_id, $uid) {
    if (!self::where('customer_id', $cid)
      ->where('building_id', $bid)
      ->where('unit_id', $unit_id)
      ->where('user_id', $uid)->find()) {

      $maxSort = self::where('customer_id', $cid)
        ->where('user_id', $uid)->max('sort');

      $filter = new Filter;
      $filter->customer_id = $cid;
      $filter->building_id = $bid;
      $filter->unit_id = $unit_id;
      $filter->user_id = $uid;
      $filter->sort = $maxSort + 1;
      $result = $filter->save();
      return $result;
    } else {
      return 1;
    }
  }

  /**
   * 项目排序
   */
  public static function sort($cid, $bid, $unit_id, $uid, $up) {
    $data = self::where('customer_id', $cid)
      ->where('building_id', $bid)
      ->where('unit_id', $unit_id)
      ->where('user_id', $uid)->find();

    if ($data) {
      $oldSort = $data->sort;
      if ($up == 1) {  //上移
        $newSort = $oldSort - 1;
      } else {         //下移
        $newSort = $oldSort + 1;
      }

      $nearData = self::where('customer_id', $cid)
        ->where('user_id', $uid)
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
  public static function removeBuilding($cid, $bid, $unit_id, $uid) {
    $data = self::where('customer_id', $cid)
      ->where('building_id', $bid)
      ->where('unit_id', $unit_id)
      ->where('user_id', $uid)->find();
    
    if ($data == null) {
      return true;
    }

    $sort = $data->sort;

    // 重新排序
    db('filter')->where('customer_id', $cid)
      ->where('user_id', $uid)
      ->where('sort', '>', $sort)
      ->setDec('sort', 1);

    return $data->delete();
  }
}