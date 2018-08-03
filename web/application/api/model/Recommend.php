<?php
namespace app\api\model;

use app\api\model\Base;
use app\api\model\Log;
use app\api\model\Customer;
use app\api\model\User;
use app\api\model\Company;
use app\api\model\Building;
use app\api\model\Unit;
use app\api\model\File;

class Recommend extends Base
{
  /**
   * 客户推荐表
   */
  public static function query($user, $customer_id) {
    $list = self::alias('a')
      ->leftJoin('recom_building b', 'a.id = b.recom_id')
      ->where('a.customer_id', $customer_id)
      ->where('a.user_id', $user->id)
      ->field('a.id,a.mode,a.token,a.create_time,count(b.id) as building_count')
      ->group('a.id')
      ->order('a.create_time', 'desc')
      ->select();

    foreach($list as $key => $item) {
      $item->disabled = false;
      
      $building = db('recom_building')->alias('a')
        ->leftJoin('unit u', 'a.unit_id = u.id AND a.unit_id > 0')
        ->join('building b', 'a.building_id = b.id OR u.building_id = b.id')
        ->where('a.recom_id', $item->id)
        ->field('b.building_name')
        ->order('a.id', 'asc')
        ->find();
      
      if ($building) {
        $item->building = $building['building_name'];
      }
    }

    return $list;
  }

  public static function detail($token) {
    $recommend = self::where('token', $token)->find();

    if ($recommend == null) {
      self::exception('资料不存在。');
    }

    $customer = Customer::where('id', $recommend->customer_id)
      ->field('customer_name,company_id')->find();

    $manager = User::where('id', $recommend->user_id)
      ->field('title,avatar,mobile,email')
      ->find();
    $manager = User::formatData($manager);

    if ($customer) {
      $company = Company::where('id', $customer->company_id)
        ->field('title,full_name,logo,area,address,rem')
        ->find();
        if ($company != null && $company->logo) {
          $company->logo = '/upload/company/images/200/' . $company->logo;
        }
    } else {
      $company = null;
    }

    $treeList = [];

    $list = db('recom_building')->alias('a')
      ->leftJoin('unit u', 'a.unit_id = u.id AND a.unit_id > 0')
      ->join('building b', 'a.building_id = b.id OR u.building_id = b.id')
      ->where('a.recom_id', $recommend->id)
      ->field('b.id as building_id,b.building_name,b.type,b.level,b.area,b.district,b.address,b.longitude,b.latitude,
        b.completion_date,b.rent_sell,b.price,b.floor as building_floor,b.floor_area,b.floor_height,
        b.bearing,b.developer,b.manager,b.fee,b.electricity_fee,b.car_seat,
        b.rem,b.facility,b.equipment,b.traffic,b.environment,
        u.id as unit_id,u.building_no,u.floor,u.room,u.face,u.acreage,u.rent_price,u.sell_price,u.decoration')
      ->order('a.id', 'asc')
      ->select();

    foreach($list as $building) {
      $find = false;
      $unit = array();
      $unitImages = null;

      if ($building['unit_id']) {
        $unit['unit'] = $building['building_no'];
        if ($building['floor'] > 0) {
          $unit['floor'] = $building['floor'] . '层';
          $unit['unit'] = $unit['unit'] . $building['floor'] . '层';
        } else if ($building['floor'] < 0) {
          $unit['unit'] = $unit['unit'] . '地下' . abs($building['floor']) . '层';
          $unit['floor'] = '地下' + abs($building['floor']) + '层';
        }
        $unit['unit'] = $unit['unit'] . $building['room'];
        $unit['room'] = $building['room'];
        $unit['desc'] = $building['decoration'];
        if ($building['face']) {
          $unit['desc'] = $unit['desc'] . ' ' . $building['face'] . '向';
        }
        $unit['face'] = $building['face'];
        $unit['acreage'] = $building['acreage'];
        $unit['rent_price'] = $building['rent_price'];
        $unit['sell_price'] = $building['sell_price'];
        $unit['decoration'] = $building['decoration'];
        $unitImages = File::getList(null, 'unit', $building['unit_id']);

        foreach($treeList as $key => $item) {
          if ($item['building_id'] == $building['building_id']) {
            $item['units'][] = $unit;
            if ($unitImages) {
              $item['images'] = array_merge($item['images'], $unitImages->toArray());
            }
            $find = true;
            $treeList[$key] = $item;
            break;
          }
        }
      }

      if (!$find) {
        $building['desc'] = (empty($building['level']) ? '' : $building['level'] . '级') .
          (empty($building['type']) ? ' ' : $building['type'] . ' ') .
          (empty($building['area']) ? '' : $building['area'] . ' ') . $building['district'];
        $building['units'] = [];
        $building['images'] = [];
        $building['eng'] = db('building_en')->where('id', $building['building_id'])->find();
        if (count($unit) > 0) {
          $building['units'][] = $unit;
        }
        $images = File::getList(null, 'building', $building['building_id']);
        if ($images) {
          $building['images'] = $images->toArray();
        }
        if ($unitImages) {
          $building['images'] = array_merge($building['images'], $unitImages->toArray());
        }
        $treeList[] = $building;
      }
    }

    return [
      'customer' => $customer,
      'manager' => $manager,
      'company' => $company,
      'date' => $recommend->create_time,
      'list' => $treeList
    ];
  }

  /**
   * 生成推荐资料
   */
  public static function addNew($user, $customer_id, $mode, $ids) {
    $customer = Customer::get($customer_id);
    if (!Customer::allow($user, $customer, 'follow')) {
      self::exception('您没有权限生成资料。');
    }

    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    $time = time();
    $recommend = new Recommend;
    $recommend->customer_id = $customer_id;
    $recommend->mode = $mode;
    $recommend->user_id = $user_id;
    $recommend->token = md5(strval($customer_id) . '_' . strval($user_id) . '_' . strval($time));
    $result = $recommend->save();
    if ($result) {
      $recom_id = $recommend->id;

      foreach ($ids as $id) {
        $arrIds = explode(',', $id);
        if (count($arrIds) == 2) {
          db('recom_building')->data([
            'recom_id' => $recom_id,
            'building_id' => intval($arrIds[0]),
            'unit_id' => intval($arrIds[1])
          ])->insert();
        }
      }
      Log::add($user, [
        "table" => "customer",
        "owner_id" => $customer_id,
        "title" => '生成推荐资料'
      ]);
    }

    return $result;
  }

  /**
   * 删除推荐资料
   */
  public static function remove($user, $id) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    $recommend = self::get($id);
    if ($recommend == null) {
      return true;
    } else {
      $customer = Customer::get($recommend->customer_id);
      if (!Customer::allow($user, $customer, 'follow') || $recommend->user_id != $user_id) {
        self::exception('您没有权限删除这份资料。');
      }
    }

    $log = [
      "table" => 'customer',
      "owner_id" => $recommend->customer_id,
      "title" => '删除资料'
    ];
    $result = $recommend->delete();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }

  /**
   * 删除资料项目
   */
  public static function removeBuilding($user, $recom_id, $id) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    $recommend = self::get($recom_id);
    if ($recommend == null) {
      self::exception('资料不存在或已被删除。');
    } else {
      $customer = Customer::get($recommend->customer_id);
      if (!Customer::allow($user, $customer, 'follow') || $recommend->user_id != $user_id) {
        self::exception('您没有权限修改这份资料。');
      }
    }

    $result =  db('recom_building')
      ->where('id', $id)
      ->where('recom_id', $recom_id)
      ->delete();
      
    if ($result) {
      Log::add($user, [
        "table" => 'customer',
        "owner_id" => $recommend->customer_id,
        "title" => '编辑推荐资料'
      ]);
    }
    return $result;
  }
}