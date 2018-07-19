<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\File;
use app\api\model\Log;
use app\api\model\Linkman;
use app\api\model\Unit;

class Building extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $share = ['隐藏','公开'];

  /**
   * 格式化列表数据
   */
  protected static function formatListData($list) {
    foreach($list as $key=>$building) {
      $building->title = $building->building_name;
      $building->desc = (empty($building->level) ? '' : $building->level . '级 ') .
        (empty($building->area) ? '' : $building->area . ' ') .
        (empty($building->district) ? '' : $building->district . ' ') .
        (empty($building->price) ? '' : $building->price);
      $building->src = empty($building->file) ? '/static/img/error.png' : '/upload/building/images/300/' . $building->file;
      $building->fallbackSrc = '/static/img/error.png';
      $building->url = '/building/view/' . $building->id;
    }
    return $list;
  }
  
  /**
   * 检索房源信息
   */
  public static function search($filter, $user_id = 0, $company_id = 0) {
    if (!isset($filter['page'])) {
      $filter['page'] = 1;
    }

    if (!isset($filter['page_size'])) {
      $filter['page_size'] = 10;
    }
    
    $list = self::alias('a')
      ->leftJoin('file b',"b.parent_id = a.id AND b.type = 'building' AND b.default = 1")
      ->where('a.city', self::$city)
      ->where('a.share = 1 OR a.user_id = 0 OR (a.user_id = ' . $user_id . ') OR (a.company_id > 0 AND a.company_id = ' . $company_id . ')');
    
    if (isset($filter['keyword']) && $filter['keyword'] != '') {
      $list->where('a.building_name|a.pinyin', 'like', $filter['keyword'] . '%');
    } else {
      if (isset($filter['type']) && $filter['type'] != '' && $filter['type'] != 'all') {
        $list->where('a.type', 'like', '%' . $filter['type'] . '%');
      }
      if (isset($filter['district']) && $filter['district'] != '' && $filter['district'] != 'all') {
        $list->where('a.district', $filter['district']);
      } else if (isset($filter['area']) && $filter['area'] != '' && $filter['area'] != 'all') {
        $list->where('a.area', $filter['area']);
      }
      if (isset($filter['rent_sell']) && $filter['rent_sell'] != '' && $filter['rent_sell'] != 'all') {
        $list->where('a.rent_sell', 'like', '%' . $filter['rent_sell'] . '%');
      }
      if (isset($filter['level']) && $filter['level'] != '' && $filter['level'] != 'all') {
        $list->where('a.level', $filter['level']);
      }
    }

    $result = $list->field('a.id,a.building_name,a.level,a.area,a.district,a.address,a.rent_sell,a.price,b.file')
      ->page($filter['page'], $filter['page_size'])
      ->order('a.update_time', 'desc')->order('a.id', 'desc')
      ->select();

    return self::formatListData($result);
  }

  /**
   * 检索收藏的房源信息
   */
  public static function myFavorite($uid, $page = 1) {
    $pageSize = 10;

    $list = db('favorite')->alias('a')
      ->leftJoin('unit u', 'a.unit_id = u.id AND a.unit_id > 0')
      ->join('building b', 'a.building_id = b.id OR u.building_id = b.id')
      ->leftJoin("file f", "f.parent_id = b.id and f.type = 'building' and f.default = 1")
      ->where('a.user_id', $uid)
      ->field('a.building_id,b.building_name,b.level,b.area,b.district,b.address,b.price,f.file,
        a.unit_id,u.building_no,u.floor,u.room,u.acreage,u.rent_price,u.sell_price')
      ->page($page, $pageSize)
      ->order('a.create_time', 'desc')
      ->select();

    foreach($list as $key => $building) {
      $building['title'] = $building['building_name'];
      if (!empty($building['building_no'])) {
        $building['title'] = $building['title'] . $building['building_no'];
      } 
      if (!empty($building['floor'])) {
        if ($building['floor'] > 0) {
          $building['title'] = $building['title'] . $building['floor'] . '层';
        } else if ($building['floor'] < 0) {
          $building['title'] = $building['title'] . '地下' . abs($building['floor']) . '层';
        }
      }
      if (!empty($building['room'])) {
        $building['title'] = $building['title'] . $building['room'];
      }

      $building['desc'] = (empty($building['level']) ? '' : $building['level'] . '级 ') .
        (empty($building['area']) ? '' : $building['area'] . ' ') .
        (empty($building['district']) ? '' : $building['district'] . ' ');

      if (!empty($building['acreage'])) {
        $building['desc'] = $building['desc'] . $building['acreage'] . '平米 ';
      }

      if (!empty($building['rent_price'])) {
        $building['desc'] = $building['desc'] . $building['rent_price'] . '元/平米/天 ';
      } else if (!empty($building['sell_price'])) {
        $building['desc'] = $building['desc'] . $building['sell_price'] . '元/平米 ';
      } else if (!empty($building['price'])) {
        $building['desc'] = $building['desc'] . $building['price'];
      }

      if (!empty($building['unit_id'])) {
        $building['url'] = '/unit/view/' . $building['unit_id'];
        $building['building_id'] = 0;
      } else {
        $building['url'] = '/building/view/' . $building['building_id'];
        $building['unit_id'] = 0;
      }

      $building['src'] = empty($building['file']) ? '/static/img/error.png' : '/upload/building/images/300/' . $building['file'];
      $building['checked'] = false;

      $list[$key] = $building;
    }

    return $list;
  }

  /**
   * 获取房源详细信息
   */
  public static function detail($id, $user_id = 0, $company_id = 0) {
    $data = self::get($id);
    if ($data == null) {
      self::exception('房源信息不存在。');
    } else if ($data->share == 0 && 
      $data->user_id > 0 && 
      $data->user_id != $user_id && 
      $data->company_id != $company_id) {
      self::exception('您没有权限查看此房源。');
    }
    $data->engInfo = db('building_en')->where('id', $id)->find();
    $data->images = File::getList($id, 'building');
    $data->linkman = Linkman::getByOwnerId($id, 'building', $user_id);
    $data->unit = Unit::getByBuildingId($id, $user_id, $company_id);
    $data->isFavorite = false;
    if ($user_id) {
      if (db('favorite')->where('user_id', $user_id)
        ->where('building_id', $id)->find() != null) {
          $data->isFavorite = true;
      }
    }
    return $data;
  }

  /**
   * 添加/修改房源信息
   */
  public static function addUp($id, $data, $user_id, $company_id = 0) {
    if (empty($data['completion_date'])) {
      unset($data['completion_date']);
    }

    $data['pinyin'] = \my\Pinyin::convertInitalPinyin($data['building_name']);

    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('房源信息不存在。');
      } else if ($oldData->share == 0 && 
        $oldData->user_id > 0 && 
        $oldData->user_id != $user_id && 
        $oldData->company_id != $company_id) {
        self::exception('您没有权限修改此房源。');
      }

      $summary = '';
      if ($data['building_name'] != $oldData->building_name) {
        if ($oldData->building_name) {
          $summary = '项目名称：' . $oldData->building_name . ' -> ' . $data['building_name'] . '\n';
        } else {
          $summary = '项目名称：' . $data['building_name'] . '\n';
        }
      }

      if ($data['type'] != $oldData->getAttr('type')) {
        if ($oldData->getAttr('type')) {
          $summary = $summary . '类别：' . $oldData->getAttr('type') . ' -> ' . $data['type'] . '\n';
        } else {
          $summary = $summary . '类别：' . $data['type'] . '\n';
        }
      }

      if ($data['level'] != $oldData->level) {
        if ($oldData->level) {
          $summary = $summary . '项目等级：' . $oldData->level . ' -> ' . $data['level'] . '\n';
        } else {
          $summary = $summary . '项目等级：' . $data['level'] . '\n';
        }
      }

      if ($data['area'] != $oldData->area) {
        if ($oldData->area) {
          $summary = $summary . '城区：' . $oldData->area . ' -> ' . $data['area'] . '\n';
        } else {
          $summary = $summary . '城区：' . $data['area'] . '\n';
        }
      }

      if ($data['district'] != $oldData->district) {
        if ($oldData->district) {
          $summary = $summary . '商圈：' . $oldData->district . ' -> ' . $data['district'] . '\n';
        } else {
          $summary = $summary . '商圈：' . $data['district'] . '\n';
        }
      }

      if ($data['address'] != $oldData->address) {
        if ($oldData->address) {
          $summary = $summary . '详细地址：' . $oldData->address . ' -> ' . $data['address'] . '\n';
        } else {
          $summary = $summary . '详细地址：' . $data['address'] . '\n';
        }
      }

      if ($oldData->completion_date) {
        $oldData->completion_date = substr($oldData->completion_date, 0, 10);
      }

      if (empty($data['completion_date'])) {
        if ($oldData->completion_date) {
          $summary = $summary . '竣工日期：' . $oldData->completion_date . ' ->\n';
        }
      } else if ($data['completion_date'] != $oldData->completion_date) {
        if ($oldData->completion_date) {
          $summary = $summary . '竣工日期：' . $oldData->completion_date . ' -> ' . $data['completion_date'] . '\n';
        } else {
          $summary = $summary . '竣工日期：' . $data['completion_date'] . '\n';
        }
      }

      if ($data['rent_sell'] != $oldData->rent_sell) {
        if ($oldData->rent_sell) {
          $summary = $summary . '租售：' . $oldData->rent_sell . ' -> ' . $data['rent_sell'] . '\n';
        } else {
          $summary = $summary . '租售：' . $data['rent_sell'] . '\n';
        }
      }

      if ($data['price'] != $oldData->price) {
        if ($oldData->price) {
          $summary = $summary . '价格：' . $oldData->price . ' -> ' . $data['price'] . '\n';
        } else {
          $summary = $summary . '价格：' . $data['price'] . '\n';
        }
      }

      if ($data['acreage'] != $oldData->acreage) {
        if ($oldData->acreage) {
          $summary = $summary . '总建筑面积：' . $oldData->acreage . '平米 -> ' . $data['acreage'] . '平米\n';
        } else {
          $summary = $summary . '总建筑面积：' . $data['acreage'] . '平米\n';
        }
      }

      // if ($data['usage_area'] != $oldData->usage_area) {
      //   if ($oldData->usage_area) {
      //     $summary = $summary . '使用面积：' . $oldData->usage_area . '平米 -> ' . $data['usage_area'] . '平米\n';
      //   } else {
      //     $summary = $summary . '使用面积：' . $data['usage_area'] . '平米\n';
      //   }
      // }

      if ($data['floor'] != $oldData->floor) {
        if ($oldData->floor) {
          $summary = $summary . '楼层：' . $oldData->floor . ' -> ' . $data['floor'] . '\n';
        } else {
          $summary = $summary . '楼层：' . $data['floor'] . '\n';
        }
      }

      if ($data['floor_area'] != $oldData->floor_area) {
        if ($oldData->floor_area) {
          $summary = $summary . '标准层面积：' . $oldData->floor_area . '平米 -> ' . $data['floor_area'] . '平米\n';
        } else {
          $summary = $summary . '标准层面积：' . $data['floor_area'] . '平米\n';
        }
      }

      if ($data['floor_height'] != $oldData->floor_height) {
        if ($oldData->floor_height) {
          $summary = $summary . '层高：' . $oldData->floor_height . '米 -> ' . $data['floor_height'] . '米\n';
        } else {
          $summary = $summary . '层高：' . $data['floor_height'] . '米\n';
        }
      }

      if ($data['bearing'] != $oldData->bearing) {
        if ($oldData->bearing) {
          $summary = $summary . '楼板承重：' . $oldData->bearing . '千克/平方米 -> ' . $data['bearing'] . '千克/平方米\n';
        } else {
          $summary = $summary . '楼板承重：' . $data['bearing'] . '千克/平方米\n';
        }
      }

      if ($data['developer'] != $oldData->developer) {
        if ($oldData->developer) {
          $summary = $summary . '开发商：' . $oldData->developer . ' -> ' . $data['developer'] . '\n';
        } else {
          $summary = $summary . '开发商：' . $data['developer'] . '\n';
        }
      }

      if ($data['manager'] != $oldData->manager) {
        if ($oldData->manager) {
          $summary = $summary . '物业管理：' . $oldData->manager . ' -> ' . $data['manager'] . '\n';
        } else {
          $summary = $summary . '物业管理：' . $data['manager'] . '\n';
        }
      }

      if ($data['fee'] != $oldData->fee) {
        if ($oldData->fee) {
          $summary = $summary . '物业费：' . $oldData->fee . ' -> ' . $data['fee'] . '\n';
        } else {
          $summary = $summary . '物业费：' . $data['fee'] . '\n';
        }
      }

      if ($data['electricity_fee'] != $oldData->electricity_fee) {
        if ($oldData->electricity_fee) {
          $summary = $summary . '电费：' . $oldData->electricity_fee . ' -> ' . $data['electricity_fee'] . '\n';
        } else {
          $summary = $summary . '电费：' . $data['electricity_fee'] . '\n';
        }
      }

      if ($data['car_seat'] != $oldData->car_seat) {
        if ($oldData->car_seat) {
          $summary = $summary . '停车位：' . $oldData->car_seat . ' -> ' . $data['car_seat'] . '\n';
        } else {
          $summary = $summary . '停车位：' . $data['car_seat'] . '\n';
        }
      }

      if ($data['rem'] != $oldData->rem) {
        if ($oldData->rem) {
          $summary = $summary . '项目说明：' . $oldData->rem . ' -> ' . $data['rem'] . '\n';
        } else {
          $summary = $summary . '项目说明：' . $data['rem'] . '\n';
        }
      }

      if ($data['facility'] != $oldData->facility) {
        if ($oldData->facility) {
          $summary = $summary . '配套设施：' . $oldData->facility . ' -> ' . $data['facility'] . '\n';
        } else {
          $summary = $summary . '配套设施：' . $data['facility'] . '\n';
        }
      }

      if ($data['equipment'] != $oldData->equipment) {
        if ($oldData->equipment) {
          $summary = $summary . '楼宇设备：' . $oldData->equipment . ' -> ' . $data['equipment'] . '\n';
        } else {
          $summary = $summary . '楼宇设备：' . $data['equipment'] . '\n';
        }
      }

      if ($data['traffic'] != $oldData->traffic) {
        if ($oldData->traffic) {
          $summary = $summary . '交通状况：' . $oldData->traffic . ' -> ' . $data['traffic'] . '\n';
        } else {
          $summary = $summary . '交通状况：' . $data['traffic'] . '\n';
        }
      }

      if ($data['environment'] != $oldData->environment) {
        if ($oldData->environment) {
          $summary = $summary . '周边环境：' . $oldData->environment . ' -> ' . $data['environment'] . '\n';
        } else {
          $summary = $summary . '周边环境：' . $data['environment'] . '\n';
        }
      }

      if ($data['share'] != $oldData->share) {
        $summary = $summary . '是否公开：' . self::$share[$oldData->share] . 
          ' -> ' . self::$share[$data['share']] . '\n';
      }

      if (isset($data['user_id'])) {
        unset($data['user_id']);
      }

      if ($oldData->user_id == 0) {
        $data['user_id'] = $user_id;
      } else if ($oldData->user_id != $user_id) {
        if (isset($data['company_id'])) {
          unset($data['company_id']);
        }
        if (isset($data['share'])) {
          unset($data['share']);
        }
      }

      $result =  $oldData->save($data);
      if ($result && $summary) {
        Log::add([
          "table" => "building",
          "owner_id" => $id,
          "title" => '修改房源信息',
          "summary" => $summary,
          "user_id" => $user_id
        ]);
      }
      return $id;
    } else {
      $data['city'] = self::$city;
      $data['user_id'] = $user_id;
      $data['status'] = 0;
      $newData = new Building($data);
      $result = $newData->save();

      if ($result) {
        Log::add([
          "table" => "building",
          "owner_id" => $newData->id,
          "title" => '登记房源',
          "summary" => $newData->building_name,
          "user_id" => $user_id
        ]);
        return $newData->id;
      } else {
        return false;
      }
    }
  }

  /**
   * 添加修改英文信息
   */
  public static function addUpEngInfo($id, $data, $user_id, $company_id = 0) {
    $building = self::get($id);
    if ($building == null) {
      self::exception('房源信息不存在。');
    } else if ($building->share == 0 && 
      $building->user_id > 0 && 
      $building->user_id != $user_id && 
      $building->company_id != $company_id) {
      self::exception('您没有权限修改此房源。');
    }

    $oldData = db('building_en')->where('id', $id)->find();
    $summary = '';

    if ($oldData == null) {
      if ($data['name']) {
        $summary = '项目名称：' . $data['name'] . '\n';
      }
      if ($data['location']) {
        $summary = '地理位置：' . $data['location'] . '\n';
      }
      if ($data['situation']) {
        $summary = '物业规模：' . $data['situation'] . '\n';
      }
      if ($data['developer']) {
        $summary = '开发商：' . $data['developer'] . '\n';
      }
      if ($data['manager']) {
        $summary = '物业管理：' . $data['manager'] . '\n';
      }
      if ($data['network']) {
        $summary = '通讯设施：' . $data['network'] . '\n';
      }
      if ($data['elevator']) {
        $summary = '电梯：' . $data['elevator'] . '\n';
      }
      if ($data['hvac']) {
        $summary = '中央空调：' . $data['hvac'] . '\n';
      }
      if ($data['amenities']) {
        $summary = '配套设施：' . $data['amenities'] . '\n';
      }
      if ($data['tenants']) {
        $summary = '入驻公司：' . $data['tenants'] . '\n';
      }

      $data['id'] = $id;
      $result = db('building_en')->insert($data);
    } else {
      if ($data['name'] != $oldData['name']) {
        if ($oldData['name']) {
          $summary = '项目名称：' . $oldData['name'] . ' -> ' . $data['name'] . '\n';
        } else {
          $summary = '项目名称：' . $data['name'] . '\n';
        }
      }

      if ($data['location'] != $oldData['location']) {
        if ($oldData['location']) {
          $summary = '地理位置：' . $oldData['location'] . ' -> ' . $data['location'] . '\n';
        } else {
          $summary = '地理位置：' . $data['location'] . '\n';
        }
      }

      if ($data['situation'] != $oldData['situation']) {
        if ($oldData['situation']) {
          $summary = '物业规模：' . $oldData['situation'] . ' -> ' . $data['situation'] . '\n';
        } else {
          $summary = '物业规模：' . $data['situation'] . '\n';
        }
      }

      if ($data['developer'] != $oldData['developer']) {
        if ($oldData['developer']) {
          $summary = '开发商：' . $oldData['developer'] . ' -> ' . $data['developer'] . '\n';
        } else {
          $summary = '开发商：' . $data['developer'] . '\n';
        }
      }

      if ($data['manager'] != $oldData['manager']) {
        if ($oldData['manager']) {
          $summary = '物业管理：' . $oldData['manager'] . ' -> ' . $data['manager'] . '\n';
        } else {
          $summary = '物业管理：' . $data['manager'] . '\n';
        }
      }

      if ($data['network'] != $oldData['network']) {
        if ($oldData['network']) {
          $summary = '通讯设施：' . $oldData['network'] . ' -> ' . $data['network'] . '\n';
        } else {
          $summary = '通讯设施：' . $data['network'] . '\n';
        }
      }

      if ($data['elevator'] != $oldData['elevator']) {
        if ($oldData['elevator']) {
          $summary = '电梯：' . $oldData['elevator'] . ' -> ' . $data['elevator'] . '\n';
        } else {
          $summary = '电梯：' . $data['elevator'] . '\n';
        }
      }

      if ($data['hvac'] != $oldData['hvac']) {
        if ($oldData['hvac']) {
          $summary = '中央空调：' . $oldData['hvac'] . ' -> ' . $data['hvac'] . '\n';
        } else {
          $summary = '中央空调：' . $data['hvac'] . '\n';
        }
      }

      if ($data['amenities'] != $oldData['amenities']) {
        if ($oldData['amenities']) {
          $summary = '配套设施：' . $oldData['amenities'] . ' -> ' . $data['amenities'] . '\n';
        } else {
          $summary = '配套设施：' . $data['amenities'] . '\n';
        }
      }

      if ($data['tenants'] != $oldData['tenants']) {
        if ($oldData['tenants']) {
          $summary = '入驻公司：' . $oldData['tenants'] . ' -> ' . $data['tenants'] . '\n';
        } else {
          $summary = '入驻公司：' . $data['tenants'] . '\n';
        }
      }

      $result = db('building_en')->where('id', $id)->data($data)->update();
    }

    if ($result && $summary) {
      Log::add([
        "table" => "building",
        "owner_id" => $id,
        "title" => '修改房源英文信息',
        "summary" => $summary,
        "user_id" => $user_id
      ]);
      return $result;
    } else {
      return $id;
    }
  }

  /**
   * 添加资料夹
   */
  public static function favorite($building_id, $unit_id, $user_id) {
    if (!db('favorite')->where('user_id', $user_id)
      ->where('building_id', $building_id)
      ->where('unit_id', $unit_id)->find()) {
      return db('favorite')->insert([
        'user_id'=> $user_id, 
        'building_id' => $building_id,
        'unit_id' => $unit_id,
        'create_time' => date("Y-m-d H:i:s",time())
      ]);
    } else {
      return 1;
    }
  }

  /**
   * 从资料夹删除
   */
  public static function unFavorite($building_id, $unit_id, $user_id) {
    return db('favorite')
      ->where('user_id', $user_id)
      ->where('building_id', $building_id)
      ->where('unit_id', $unit_id)->delete();
  }

  /**
   * 删除房源
   */
  public static function remove($id, $user_id) {
    $building = self::get($id);
    if ($building == null) {
      return true;
    }else if ($building->user_id != $user_id) {
      self::exception('您没有权限删除此房源。');
    }
    $log = [
      "table" => 'building',
      "owner_id" => $building->id,
      "title" => '删除房源',
      "summary" => $building->building_name,
      "user_id" => $user_id
    ];
    $result = $building->delete();
    if ($result) {
      Log::add($log);
    }
    return $result;
  }
}