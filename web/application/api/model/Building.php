<?php
namespace app\api\model;

use think\facade\Log as sysLog;
use think\model\concern\SoftDelete;
use think\facade\Validate;
use app\common\Excel;
use app\common\Wechat;
use app\common\Utils;
use app\common\Sms;
use app\api\model\Base;
use app\api\model\File;
use app\api\model\Log;
use app\api\model\Company;
use app\api\model\Linkman;
use app\api\model\Unit;
use app\api\model\Confirm;

class Building extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $share = ['隐藏','公开'];

  /**
   * 格式化列表数据
   */
  protected static function formatList($list) {
    foreach($list as $key=>$building) {
      $building->title = $building->building_name;
      if ($building->status == 3) {
        $building->title .= '[待认领]';
      } else if ($building->share == 1) {
        if ($building->status == 0) {
          $building->title .= '[待审核]';
        } else if ($building->status == 2) {
          $building->title .= '[驳回]';
        }
      } else if ($building->share == 0) {
        $building->title .= '[私有]';
      }
      $building->desc = (empty($building->level) ? '' : $building->level . '级 ') .
        (empty($building->area) ? '' : $building->area . ' ') .
        (empty($building->district) ? '' : $building->district . ' ') .
        (empty($building->price) ? '' : $building->price);
      $building->src = empty($building->file) ? '/static/img/error.png' : '/upload/building/images/300/' . $building->file;
      $building->fallbackSrc = '/static/img/error.png';
      $building->url = '/building/view/' . $building->id;
      if (mb_strlen($building->address, 'utf-8') > 18) {
        $building->address = mb_substr($building->address, 0, 17, 'utf-8') . '...';
      }
    }
    return $list;
  }

  /**
   * 权限检查
   */
  public static function allow($user, $building, $operate) {
    if ($building == null && $operate != 'new') {
      return false;
    }
    if ($operate == 'share') {
      return true;
    } else if ($operate == 'view') {
      return ($building->share && ($building->status == 1 || $user->isAdmin)) || 
        $building->user_id == 0 ||
        (
          $user != null && ($building->share_level !== null || 
          $building->user_id == $user->id ||
          ($building->company_id > 0 && $building->company_id == $user->company_id))
        );
    } else if ($operate == 'new') {
      return $user != null && $user->company_id > 0;
    } else if ($operate == 'edit') {
      if ($user == null) {
        return false;
      } else {
        return $building->user_id == 0 || $building->share_level > 0 ||
          $building->user_id == $user->id || 
          ($building->company_id > 0 && $building->company_id == $user->company_id);
      }
    } else if ($operate == 'copy') {
      if ($user == null) {
        return false;
      }
      return $building->share == 1 && $building->status == 1 && $building->user_id != $user->id && $building->company_id != $user->company_id;
    } else if ($operate == 'audit') {
      if ($user == null) {
        return false;
      }
      return $user->isAdmin && $building->share == 1 && $building->status == 0;
    } else if ($operate == 'delete') {
      if ($user == null) {
        return false;
      }
      return $user->isAdmin;
    } else {
      return false;
    }
  }
  
  /**
   * 检索项目信息
   */
  public static function search($user, $filter) {
    if (!isset($filter['page'])) {
      $filter['page'] = 1;
    }

    if (!isset($filter['page_size'])) {
      $filter['page_size'] = 10;
    }

    $user_id = 0;
    $company_id = 0;
    $isAdmin = false;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
      $isAdmin = $user->isAdmin;
    }

    if (isset($filter['type']) && $filter['type'] == 'private') {
      $filter['my'] = 1;
      unset($filter['type']);
    }
  
    $list = self::alias('a')
      ->leftJoin('file b',"b.parent_id = a.id AND b.type = 'building' AND b.default = 1")
      ->leftJoin('share s', "s.type = 'building' and a.id = s.object_id and s.user_id = " . $user_id)
      ->where('a.city', self::$city);

    if (isset($filter['my']) && $filter['my'] == 1) {
      $list->where('(a.user_id > 0 AND (s.object_id is not null OR a.user_id = ' . $user_id . ' OR 
        (a.company_id > 0 AND a.company_id = ' . $company_id . ')))');
    } else if ($isAdmin) {
      $list->where('((a.share = 1 AND a.status <> 2) OR a.user_id = 0 OR s.object_id is not null OR a.user_id = ' . $user_id . ' OR 
        (a.company_id > 0 AND a.company_id = ' . $company_id . '))');
    } else {
      $list->where('((a.share = 1 AND a.status = 1) OR a.user_id = 0 OR s.object_id is not null OR a.user_id = ' . $user_id . ' OR 
        (a.company_id > 0 AND a.company_id = ' . $company_id . '))');
    }
    
    if (isset($filter['keyword']) && $filter['keyword'] != '') {
      $list->where("(a.pinyin like '" . $filter['keyword'] . "%' OR a.building_name like '%" . $filter['keyword'] . "%')");
    } else {
      if (isset($filter['type']) && $filter['type'] != '' && $filter['type'] != 'all') {
        if ($filter['type'] == 'empty') {
          $list->where('a.user_id', 0);
        } else {
          $list->where('a.type', 'like', '%' . $filter['type'] . '%');
        }
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

    $list->field('a.id,a.building_name,a.level,a.area,a.district,a.address,
      a.rent_sell,a.price,b.file,a.user_id,a.share,a.status,s.level as share_level')
      ->page($filter['page'], $filter['page_size']);

    if ($isAdmin) {
      $list->order([
        'a.status' => 'asc',
        'a.share' => 'desc',
        'a.update_time' => 'desc', 
        'a.id' => 'desc']);
    } else {
      $list->order([
        'a.share' => 'asc',
        'a.status' => 'asc',
        'a.update_time' => 'desc', 
        'a.id' => 'desc']);
    }

    $result = $list->select();

    return self::formatList($result);
  }

  /**
   * 检索收藏的项目信息
   */
  public static function myFavorite($user, $page = 1, $page_size = 10) {
    if ($user == null) {
      return null;
    }

    $list = db('favorite')->alias('a')
      ->leftJoin('unit u', 'a.unit_id = u.id AND a.unit_id > 0')
      ->join('building b', 'a.building_id = b.id OR u.building_id = b.id')
      ->leftJoin("file f", "f.parent_id = b.id and f.type = 'building' and f.default = 1")
      ->where('a.user_id', $user->id)
      ->field('a.building_id,b.building_name,b.level,b.area,b.district,b.address,b.price,f.file,
        a.unit_id,u.building_no,u.floor,u.room,u.acreage,u.rent_price,u.sell_price')
      ->page($page, $page_size)
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
   * 通过ID获取项目信息
   */
  public static function getById($user, $id) {
    $user_id = 0;

    if ($user) {
      $user_id = $user->id;
    }

    $data = self::alias('a')
      ->leftJoin('share s', "s.type = 'building' and a.id = s.object_id and s.user_id = " . $user_id)
      ->field('a.*,s.level as share_level')
      ->where('a.id', $id)
      ->find();

    return $data;
  }

  /**
   * 获取项目详细信息
   */
  public static function detail($user, $id, $operate = 'view', $key = '') {
    $user_id = 0;

    if ($user) {
      $user_id = $user->id;
    }

    // 通过分享链接查看自动加入共享列表
    if ($user_id > 0 && !empty($key) && $key == md5('building' . $id . config('wechat.app_secret'))) {
      $share = db('share')
        ->where('type', 'building')
        ->where('user_id', $user_id)
        ->where('object_id', $id)
        ->find();

      if (null == $share) {
        db('share')->insert([
          'type' => 'building',
          'user_id' => $user_id,
          'object_id' => $id,
          'level' => 0
        ]);
      }
    }

    $data = self::alias('a')
      ->leftJoin('share s', "s.type = 'building' and a.id = s.object_id and s.user_id = " . $user_id)
      ->leftJoin('user u', 'a.user_id = u.id')
      ->field('a.id,a.building_name,a.type,a.level,a.area,a.district,a.address,a.subway,a.longitude,a.latitude,
        a.completion_date,a.rent_sell,a.price,a.acreage,a.floor,a.floor_area,a.floor_height,a.bearing,
        a.developer,a.manager,a.fee,a.electricity_fee,a.car_seat,a.rem,a.facility,a.equipment,a.traffic,
        a.environment,a.share,a.user_id,a.company_id,a.short_url,a.create_time,u.title as user,u.avatar,
        s.create_time as share_create_time,a.status,s.level as share_level')
      ->where('a.id', $id)
      ->find();

    if ($data == null) {
      self::exception('项目信息不存在。');
    }

    if (isset($data->avatar) && $data->avatar) {
      $find = strpos($data->avatar, 'http');
      if ($find === false || $find > 0) {
        $data->avatar = '/upload/user/images/60/' . $data->avatar;
      }
    } else {
      $data->avatar = '/static/img/avatar.png';
    }

    if ($data->completion_date) {
      $data->completion_date_text = date('Y年n月j日', strtotime($data->completion_date));
    }

    if ($data->create_time) {
      $data->create_time_text = date('Y年n月j日 H:i', strtotime($data->create_time));
    }

    if ($operate != 'notes' && !self::allow($user, $data, $operate)) {
      self::exception('您没有权限' . ($operate == 'edit' ? '编辑' : '查看') . '此项目。');
    }

    $data->engInfo = db('building_en')->where('id', $id)
      ->field('name,location,situation,developer,manager,' .
        'network,elevator,hvac,amenities,	tenants')->find();

    $images = [];
    $videos = [];
    
    $files = File::getList($user, 'building', $id);
    if ($files) {
      foreach($files as $key => $file) {
        if ($file->is_image) {
          array_push($images, $file);
        } else if ($file->is_video) {
          array_push($videos, $file);
        }
      }
    }

    $data->images = $images;
    $data->videos = $videos;

    if ($operate == 'view') {
      $data->isFavorite = false;
      $data->allowEdit = self::allow($user, $data, 'edit');
      $data->allowCopy = self::allow($user, $data, 'copy');
      $data->allowAudit = self::allow($user, $data, 'audit');
      $data->allowDelete = self::allow($user, $data, 'delete');
      $data->unit = Unit::getByBuildingId($user, $id);

      self::getShortUrl($data);
      
      if ($user) {
        $data->linkman = Linkman::getByOwnerId($user, 'building', $id, true);
        $data->confirm = Confirm::query($user, 0, $id);
        if (db('favorite')->where('user_id', $user->id)
          ->where('building_id', $id)->find()) {
            $data->isFavorite = true;
        }
      }
    }

    if (empty($user) || (!$user->isAdmin && $data->company_id != $user->company_id)) {
      unset($data['user']);
      unset($data['avatar']);
      unset($data['create_time']);
      unset($data['create_time_text']);
    }

    return $data;
  }

  /**
   * 添加/修改项目信息
   */
  public static function addUp($user, $id, $data) {
    $data['pinyin'] = \my\Pinyin::convertInitalPinyin($data['building_name']);
    $user_id = 0;
    $isAdmin = false;
    if ($user) {
      $user_id = $user->id;
      $isAdmin = $user->isAdmin;
    }

    $copy = 0;
    if (isset($data['copy'])) {
      $copy = $data['copy'];
      unset($data['copy']);
    }

    if (isset($data['completion_date']) && empty($data['completion_date'])) {
      unset($data['completion_date']);
    }

    if (isset($data['acreage']) && $data['acreage'] == 'null') {
      $data['acreage'] = null;
    }

    if (isset($data['floor_height']) && $data['floor_height'] == 'null') {
      $data['floor_height'] = null;
    }

    if (isset($data['bearing']) && $data['bearing'] == 'null') {
      $data['bearing'] = null;
    }

    if ($id) {
      $oldData = self::getById($user, $id);
      if ($oldData == null) {
        self::exception('项目信息不存在。');
      } else if (!self::allow($user, $oldData, 'edit')) {
        self::exception('您没有权限修改此项目。');
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

      if (isset($data['completion_date'])) {
        if ($oldData->completion_date) {
          $oldData->completion_date = date_format(date_create($oldData->completion_date), 'Y-m-d');
        }
        if ($data['completion_date'] != $oldData->completion_date) {
          if ($oldData->completion_date) {
            $summary = $summary . '竣工日期：' . $oldData->completion_date . ' -> ' . $data['completion_date'] . '\n';
          } else {
            $summary = $summary . '竣工日期：' . $data['completion_date'] . '\n';
          }
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

      // if ($data['floor'] != $oldData->floor) {
      //   if ($oldData->floor) {
      //     $summary = $summary . '楼层：' . $oldData->floor . ' -> ' . $data['floor'] . '\n';
      //   } else {
      //     $summary = $summary . '楼层：' . $data['floor'] . '\n';
      //   }
      // }

      // if ($data['floor_area'] != $oldData->floor_area) {
      //   if ($oldData->floor_area) {
      //     $summary = $summary . '标准层面积：' . $oldData->floor_area . '平米 -> ' . $data['floor_area'] . '平米\n';
      //   } else {
      //     $summary = $summary . '标准层面积：' . $data['floor_area'] . '平米\n';
      //   }
      // }

      if (isset($data['floor_height']) && $data['floor_height'] != $oldData->floor_height) {
        if ($oldData->floor_height) {
          $summary = $summary . '层高：' . $oldData->floor_height . '米 -> ' . $data['floor_height'] . '米\n';
        } else {
          $summary = $summary . '层高：' . $data['floor_height'] . '米\n';
        }
      }

      if (isset($data['bearing']) && $data['bearing'] != $oldData->bearing) {
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

      // if ($data['electricity_fee'] != $oldData->electricity_fee) {
      //   if ($oldData->electricity_fee) {
      //     $summary = $summary . '电费：' . $oldData->electricity_fee . ' -> ' . $data['electricity_fee'] . '\n';
      //   } else {
      //     $summary = $summary . '电费：' . $data['electricity_fee'] . '\n';
      //   }
      // }

      // if ($data['car_seat'] != $oldData->car_seat) {
      //   if ($oldData->car_seat) {
      //     $summary = $summary . '停车位：' . $oldData->car_seat . ' -> ' . $data['car_seat'] . '\n';
      //   } else {
      //     $summary = $summary . '停车位：' . $data['car_seat'] . '\n';
      //   }
      // }

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

      if (isset($data['user_id'])) {
        unset($data['user_id']);
      }

      if ($oldData->user_id == 0) {
        $data['user_id'] = $user_id;
      } 
      
      // else if ($oldData->user_id != $user_id) {
      //   if (isset($data['company_id'])) {
      //     unset($data['company_id']);
      //   }
      //   if (isset($data['share'])) {
      //     unset($data['share']);
      //   }
      // }

      if (isset($data['company_id']) && $data['company_id'] != $oldData->company_id) {
        $oldCompany = null;
        $newCompany = null;
        if ($oldData->company_id) {
          $oldCompany = Company::get($oldData->company_id);
        }
        if ($data['company_id']) {
          $newCompany = Company::get($data['company_id']);
        }
        if ($oldCompany && $newCompany) {
          $summary = $summary . '所属企业：' . $oldCompany->full_name . '->' . $newCompany->full_name . '\n';
        } else if ($oldCompany) {
          $summary = $summary . '所属企业：' . $oldCompany->full_name . '->\n';
        } else {
          $summary = $summary . '所属企业：' . $newCompany->full_name . '\n';
        }
      }

      if (isset($data['share']) && $data['share'] != $oldData->share) {
        $summary = $summary . '是否公开：' . self::$share[$oldData->share] . 
          ' -> ' . self::$share[$data['share']] . '\n';
      }

      if ($isAdmin && isset($data['share']) && $data['share'] == 1) {
        $data['status'] = 1;
      } else {
        $data['status'] = 0;
      }

      $result =  $oldData->save($data);
      if ($result && $summary) {
        Log::add($user, [
          "table" => "building",
          "owner_id" => $id,
          "title" => '修改项目信息',
          "summary" => $summary
        ]);
      }

      if (isset($data['send_sms']) && $data['send_sms'] == 1) {
        self::sendCommissionConfirm($user, $id);
      }

      return $id;
    } else if (!self::allow($user, null, 'new')) {
      self::exception('您没有权限添加项目。');
    } else {
      $data['city'] = self::$city;
      $data['user_id'] = $user_id;
      if (empty($data['company_id'])) {
        $data['company_id'] = $user->company_id;
      }
      if (empty($data['floor_height'])) {
        unset($data['floor_height']);
      }
      if ($isAdmin && isset($data['share']) && $data['share'] == 1) {
        $data['status'] = 1;
      } else {
        $data['status'] = 0;
      }
      $newData = new Building($data);
      $result = $newData->save();

      if ($result) {
        Log::add($user, [
          "table" => "building",
          "owner_id" => $newData->id,
          "title" => '登记项目',
          "summary" => $newData->building_name
        ]);

        if ($copy > 0) {
          // 复制英文信息
          $engData = db('building_en')->where('id', $copy)->find();
          if ($engData) {
            $engData['id'] = $newData->id;
            db('building_en')->insert($engData);
          }
          // 复制图片
          $fileData = db('file')
            ->where('type', 'building')
            ->where('parent_id', $copy)
            ->where('delete_time', 'null')
            ->field('`type`,' . $newData->id . ' as parent_id,`title`,`file`,`size`,`default`,
              `sort`,now() as create_time,' . $user_id . ' as user_id')
            ->select();

          if ($fileData) {
            db('file')->insertAll($fileData);
          }
          
          // 复制单元
          $unitData = db('unit')
            ->where('building_id', $copy)
            ->where('delete_time', 'null')
            ->field('room,building_no,floor,face,acreage,rent_sell,rent_price,sell_price,decoration,`status`,end_date,`rem`,0 as `share`,' . 
              $company_id . ' as `company_id`,' . $user_id . ' as user_id,now() as create_time')
            ->select();

          if ($unitData) {
            db('unit')->insertAll($unitData);
          }

          // 复制联系人
          $linkmanData = db('linkman')
            ->where('type', 'building')
            ->where('owner_id', $copy)
            ->where('delete_time', 'null')
            ->field('`type`,' . $newData->id . ' as owner_id,`title`,`department`,`job`,`mobile`,tel,email,
              weixin,qq,rem,`status`,now() as create_time,' . $user_id . ' as user_id')
            ->select();
          if ($linkmanData) {
            db('linkman')->insertAll($linkmanData);
          }
        }

        return $newData->id;
      } else {
        return false;
      }
    }
  }

  /**
   * 添加修改英文信息
   */
  public static function addUpEngInfo($user, $id, $data) {
    $building = self::getById($user, $id);
    if ($building == null) {
      self::exception('项目信息不存在。');
    } else if (!self::allow($user, $building, 'edit')) {
      self::exception('您没有权限修改此项目。');
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
      Log::add($user, [
        "table" => "building",
        "owner_id" => $id,
        "title" => '修改项目英文信息',
        "summary" => $summary
      ]);
      return $result;
    } else {
      return $id;
    }
  }

  /**
   * 添加资料夹
   */
  public static function favorite($user, $building_id, $unit_id = 0) {
    if (!$user) {
      return false;
    }
    $user_id = $user->id;
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
  public static function unFavorite($user, $building_id, $unit_id = 0) {
    if (!$user) {
      return false;
    }
    $user_id = $user->id;
    return db('favorite')
      ->where('user_id', $user_id)
      ->where('building_id', $building_id)
      ->where('unit_id', $unit_id)->delete();
  }

  /**
   * 审核项目
   */
  public static function audit($user, $id, $status = 1, $summary = '') {
    $building = self::getById($user, $id);
    if ($building == null) {
      self::exception('项目不存在。');
    } else if (!self::allow($user, $building, 'audit')) {
      self::exception('您没有权限审核此项目。');
    }
    
    $result = $building->save([
      'status' => $status,
      'audit_user' => $user->id,
      'audit_time' => date("Y-m-d H:i:s",time()),
      'audit_rem' => $summary
    ]);

    if ($result) {
      $log = [
        "table" => 'building',
        "owner_id" => $building->id,
        "title" => '审核项目',
        "summary" => $building->building_name
      ];
      Log::add($user, $log);
    }
    return $result;
  }

  /**
   * 删除项目
   */
  public static function remove($user, $id) {
    $building = self::getById($user, $id);
    if ($building == null) {
      return true;
    } else if (!self::allow($user, $building, 'delete')) {
      self::exception('您没有权限删除此项目。');
    }
    
    $log = [
      "table" => 'building',
      "owner_id" => $building->id,
      "title" => '删除项目',
      "summary" => $building->building_name
    ];
    $result = $building->delete();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }

  /**
   * 批量导入项目
   */
  public static function import($user, $data) {
    if (!self::allow($user, null, 'new')) {
      self::exception('您没有权限添加项目。');
    }

    $excel = new Excel();
    $rowData = $excel->getData($data);

    if (!$rowData) {
      self::exception($excel->getError());
    }

    if (count($rowData) < 1 || count($rowData[0]) < 23) {
      self::exception('导入数据不完整，请使用导入模板。');
    }

    $user_id = 0;
    $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    $succCount = 0;
    $clashCount = 0;
    $failCount = 0;

    foreach ($rowData as $row) {
      $building = [
        'building_name' => $row[0],
        'type' => $row[1],
        'level' => $row[2],
        'area' => $row[3],
        'address' => $row[4],
        'developer' => $row[5],
        'linkman' => $row[6],
        'tel' => $row[7],
        'manager' => $row[8],
        'completion_date' => $row[9],
        'acreage' => $row[10],
        'floor' => $row[11],
        'floor_height' => $row[12],
        'floor_area' => $row[13],
        'bearing' => $row[14],
        'fee' => $row[15],
        'electricity_fee' => $row[16],
        'car_seat' => $row[17],
        'rem' => $row[18],
        'equipment' => $row[19],
        'traffic' => $row[20],
        'facility' => $row[21],
        'environment' => $row[22]
      ];

      if ($building['building_name'] || $building['type']) {
        foreach($building as $k=>$v) {
          if ($v == '' || $v == null || $v == 'null' || $v == 'NULL') {
            unset($building[$k]);
          }
        }

        if ($company_id) {
          $clash = self::where('building_name', $building['building_name'])
            ->where('company_id', $company_id)->find();
          if ($clash) {
            $clashCount++;
            continue;
          }
        }

        if (isset($building['completion_date'])) {
          $n = intval(($building['completion_date'] - 25569) * 3600 * 24);
          $building['completion_date'] = gmdate('Y-m-d', $n);
        }

        $building['city'] = self::$city;
        $building['share'] = 0;
        $building['status'] = 0;
        $building['user_id'] = $user_id;
        $building['company_id'] = $company_id;

        if ($building['linkman'] || $building['tel']) {
          $linkman['type'] = 'building';
          if (isset($building['linkman'])) {
            $linkman['title'] = $building['linkman'];
            unset($building['linkman']);
          }
          if (isset($building['tel'])) {
            if (Validate::isMobile($building['tel'])) {
              $linkman['mobile'] = $building['tel'];
            } else {
              $linkman['tel'] = $building['tel'];
            }
            unset($building['tel']);
          }
        }

        $newData = new Building($building);
        $result = $newData->save();

        if ($result) {
          Log::add($user, [
            "table" => "building",
            "owner_id" => $newData->id,
            "title" => '导入项目',
            "summary" => $newData->building_name
          ]);
          if ($linkman) {
            $linkman['owner_id'] = $newData->id;
            Linkman::addUp($user, 0, $linkman);
          }
          $succCount++;
        } else {
          $failCount++;
        }
      } else {
        $failCount++;
      }
    }

    return [
      'success' => $succCount,
      'clash' => $clashCount,
      'fail' => $failCount
    ];
  }

  /**
   * 给项目联系人发送确认委托短信
   */
  public static function sendCommissionConfirm($user, $id) {
    $building = self::getById($user, $id);
    if ($building == null) {
      return false;
    } else if (!self::allow($user, $building, 'edit')) {
      self::exception('您没有权限发送短信。');
    }

    self::getShortUrl($building);

    $linkman = db('linkman')
      ->where('type', 'building')
      ->where('owner_id', $building->id)
      ->where('status', 0)
      ->where('mobile', '<>', $user->mobile)
      ->where('delete_time', 'null')
      ->field('mobile')
      ->select();

    if (empty($linkman) || count($linkman) == 0) {
      return false;
    }

    $mobileList = '';
    foreach($linkman as $item) {
      if ($mobileList) {
        $mobileList .= ',';
      }
      $mobileList .= $item['mobile'];
    }

    $summary = $building->building_name;
    $acreage = $building->acreage;
    
    $unit = Unit::getByBuildingId($user, $id, 1);
    if ($unit && count($unit) > 0) {
      $acreage = 0;
      $summary .= ' ' . $unit[0]->title . '共' . count($unit) . '个空置房间';
      foreach ($unit as $u) {
        if (!empty($u->acreage)) {
          $acreage += intval($u->acreage);
        }
      }
    }

    $message = sprintf(config('sms.tmp_commission_confirm'), 
      $summary, $acreage . '平方米', $building->short_url);

    sysLog::info($mobileList . ':'. $message);

    $sms = new Sms();
    $sms->sendSMS($mobileList, $message);
  }

  /**
   * 获取项目短网址
   */
  private static function getShortUrl(&$building) {
    if (empty($building) || empty($building->id)) {
      return;
    }
    if (empty($building->key)) {
      $building->key = md5('building' . $building->id . config('wechat.app_secret'));
    }
    if (empty($building->short_url)) {
      $wechat = new Wechat();
      $url = 'https://' . config('app_host') . '/app/building/view/' . $building->id . '/' . $building->key;
      $building->short_url = $wechat->getShortUrl($url);
      $building->save();
    }
  }
}