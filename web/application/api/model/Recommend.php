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
   * 格式化数据
   */
  private static function formatList($list) {
    if (empty($list)) {
      return $list;
    }

    foreach($list as $key => $item) {
      $item->disabled = false;
      
      $building = db('recom_building')->alias('a')
        ->leftJoin('unit u', 'a.unit_id = u.id AND a.unit_id > 0')
        ->join('building b', 'a.building_id = b.id OR u.building_id = b.id')
        ->leftJoin('file bf', "bf.parent_id = b.id AND bf.type = 'building' AND bf.default = 1")
        ->leftJoin('file uf', "uf.parent_id = u.id AND uf.type = 'unit' AND uf.default = 1")
        ->where('a.recom_id', $item->id)
        ->field('b.building_name,bf.file as building_img,uf.file as unit_img')
        ->order('a.id', 'asc')
        ->find();
      
      if ($building) {
        $item->building = $building['building_name'];
        $item->image = '/static/img/error.png';
        
        if ($building['unit_img']) {
          $item->image = '/upload/building/images/300/' . $building['unit_img'];
        } else if ($building['building_img']) {
          $item->image = '/upload/building/images/300/' . $building['building_img'];
        }
      }
    }

    return $list;
  }

  /**
   * 查询推荐资料
   */
  public static function query($user, $customer_id = 0) {
    $list = self::alias('a')
      ->leftJoin('recom_building b', 'a.id = b.recom_id');

    if ($customer_id) {
      $list->where('a.customer_id', $customer_id);
    } else {
      $list->where('a.user_id', $user->id);
    }
     
    $list = $list->field('a.id,a.mode,a.token,a.create_time,count(b.id) as building_count')
      ->group('a.id')
      ->order('a.create_time', 'desc')
      ->select();

    return self::formatList($list);
  }

  /**
   * 查询已共享的推荐资料
   */
  public static function queryShare($user, $owner_id = 0) {
    $list = self::alias('a')
      ->join('share s', "s.type = 'recommend' and a.id = s.object_id and s.user_id = " . $user->id)
      ->leftJoin('recom_building b', 'a.id = b.recom_id')
      ->leftJoin('customer c', 'a.customer_id = c.id');
    if ($owner_id) {
      $list->where('a.user_id', $owner_id);
    }
     
    $list = $list->field('a.id,a.mode,a.token,a.create_time,count(b.id) as building_count,c.customer_name')
      ->group('a.id')
      ->order('a.create_time', 'desc')
      ->select();

      return self::formatList($list);
  }

  public static function detail($user, $token) {
    $user_id = 0;
    // $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      // $company_id = $user->company_id;
    }

    $recommend = self::where('token', $token)->find();

    if ($recommend == null) {
      self::exception('资料不存在。');
    }

    if ($user_id) {
      $share = db('share')
        ->where('type', 'recommend')
        ->where('user_id', $user_id)
        ->where('object_id', $recommend->id)
        ->find();

      if (null == $share) {
        db('share')->insert([
          'type' => 'recommend',
          'user_id' => $user_id,
          'object_id' => $recommend->id
        ]);
      }
    }

    $customer = Customer::where('id', $recommend->customer_id)
      ->field('customer_name,company_id')->find();

    $manager = User::where('id', $recommend->user_id)
      ->field('title,avatar,mobile,email,weixin')
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
      ->field('b.id as building_id,b.building_name,b.type,b.level,b.acreage,b.area,b.district,b.address,b.longitude,b.latitude,
        b.completion_date,b.rent_sell,b.price,b.floor as building_floor,b.floor_area,b.floor_height,
        b.bearing,b.developer,b.manager,b.fee,b.electricity_fee,b.car_seat,
        b.rem,b.facility,b.equipment,b.traffic,b.environment,
        u.id as unit_id,u.building_no,u.floor,u.room,u.face,u.acreage,u.rent_price,u.sell_price,u.decoration')
      ->order('a.id', 'asc')
      ->select();

    foreach($list as $building) {
      $find = false;
      $unit = array();

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
        $unit['face'] = $building['face'];
        $unit['acreage'] = $building['acreage'];
        $unit['rent_price'] = $building['rent_price'];
        $unit['sell_price'] = $building['sell_price'];
        $unit['decoration'] = $building['decoration'];
        $unit['desc'] = $building['decoration'];
        if ($building['face']) {
          $unit['desc'] = $unit['desc'] . ' ' . $building['face'] . '向';
        }
        if ($unit['acreage']) {
          $unit['desc'] = $unit['desc'] . ' ' . $unit['acreage'] . '平方米';
        }
        if ($unit['rent_price']) {
          $unit['desc'] = $unit['desc'] . ' ' . $unit['rent_price'] . '元/平米/天';
        } else if ($unit['sell_price']) {
          $unit['desc'] = $unit['desc'] . ' ' . $unit['sell_price'] . '元/平米';
        }
        $unit['images'] = File::getList(null, 'unit', $building['unit_id']);
        if ($unit['images'] && count($unit['images']) > 0) {
          $unit['src'] = $unit['images'][0]['msrc'];
        }
        $unit['title'] = $unit['unit'];

        foreach($treeList as &$item) {
          if ($item['building_id'] == $building['building_id']) {
            $item['units'][] = $unit;
            if ($unit['images']) {
              $item['images'] = array_merge($item['images'], $unit['images']->toArray());
            }
            $find = true;
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
        if (isset($unit['images'])) {
          $building['images'] = array_merge($building['images'], $unit['images']->toArray());
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
    $customer = Customer::getById($user, $customer_id);
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
      $customer = Customer::getById($user, $recommend->customer_id);
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
      $customer = Customer::getById($user, $recommend->customer_id);
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