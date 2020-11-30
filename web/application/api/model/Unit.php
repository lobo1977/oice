<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\File;
use app\api\model\Building;
use app\api\model\Linkman;

class Unit extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $status = ['在驻','空置','已出'];
  public static $share = ['隐藏','公开'];

  /**
   * 权限检查
   */
  public static function allow($user, $unit, $operate, $building = null) {
    if ($unit == null) {
      return false;
    }
    if ($building == null) {
      $building = Building::getById($user, $unit->building_id);
    }

    if ($operate == 'view') {
      return 
        $unit->share || 
        (
          $user != null && ($unit->user_id == $user->id ||
          $unit->share_level !== null ||
          ($unit->company_id > 0 && $unit->company_id == $user->company_id))
        );
    } else if ($operate == 'new') {
      return $user != null && Building::allow($user, $building, 'edit');
    } else if ($operate == 'edit') {
      return $user != null && (
        Building::allow($user, $building, 'edit') || 
        $unit->share_level > 0 ||
        $unit->user_id == $user->id ||
        ($unit->company_id > 0 && $unit->company_id == $user->company_id));
    } else if ($operate == 'copy') {
      return $user != null && Building::allow($user, $building, 'edit');
    } else if ($operate == 'delete') {
      return $user != null && (
        Building::allow($user, $building, 'edit') ||
        $unit->share_level > 1 ||
        $unit->user_id == $user->id ||
        ($unit->company_id > 0 && $unit->company_id == $user->company_id));
    } else {
      return false;
    }
  }

  /**
   * 格式化单元信息
   */
  public static function formatInfo($unit) {
    if ($unit != null) {
      if ($unit->create_time) {
        $unit->create_time_text = date('Y年n月j日 H:i', strtotime($unit->create_time));
      }

      if (!empty($unit->building_no)) {
        if (!empty($unit->title)) {
          $unit->title = $unit->title . $unit->building_no . ' ';
        } else {
          $unit->title = $unit->building_no . ' ';
        }
      } else if (empty($unit->title)) {
        $unit->title = '';
      }

      if ($unit->floor > 0) {
        $unit->title = $unit->title . $unit->floor . '层 ';
      } else if ($unit->floor < 0) {
        $unit->title = $unit->title . '地下' . abs($unit->floor) . '层 ';
      }
      if ($unit->room) {
        $unit->title = $unit->title . $unit->room;
      }
      if (isset($unit->status) && $unit->status != null) {
        $unit->statusText = self::$status[$unit->status];
        if ($unit->status != 1) {
          $unit->title = $unit->title . '(' . $unit->statusText . ')';
        }
      }
      if (isset($unit->share) && $unit->share == 0) {
        $unit->title = $unit->title . '[私有]';
      }

      if (empty($unit->file)) {
        $unit->src = '/static/img/error.png';
      } else if (!empty($unit->file_type)) {
        $unit->src = '/upload/' . $unit->file_type . '/images/300/' . $unit->file;
      } else {
        $unit->src = '/upload/unit/images/300/' . $unit->file;
      }

      $unit->desc = '';
      if (!empty($unit->acreage)) {
        $unit->desc = $unit->acreage . '㎡ ';
      }
      if (!empty($unit->rent_price)) {
        $unit->desc = $unit->desc . $unit->rent_price . '元/平米/天 ';
      } else if (!empty($unit->sell_price)) {
        $unit->desc = $unit->desc . $unit->sell_price . '元/平米 ';
      }

      if (isset($unit->end_date) && $unit->end_date) {
        $unit->end_date_text = date('Y年n月j日', strtotime($unit->end_date));
      }
    }
  }

  /**
   * 通过项目ID获取单元列表
   */
  public static function getByBuildingId($user, $id, $status = -1) {
    $user_id = 0;
    $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    $building = Building::getById($user, $id);

    $list = self::alias('a')
      ->leftJoin('file b',"b.parent_id = a.id AND b.type = 'unit' AND b.default = 1")
      ->leftJoin('share s', "s.type = 'unit' and a.id = s.object_id and s.user_id = " . $user_id)
      ->where('a.building_id', $id);

      if ($status >= 0) {
        $list->where('a.status', $status);
      }

      $list = $list->field('a.id,a.building_no,a.floor,a.room,a.acreage,a.rent_sell,a.rent_price,
        a.sell_price,a.end_date,a.status,a.share,a.user_id,a.company_id,b.file,s.level as share_level')
      ->order('a.building_no', 'asc')
      ->order('a.floor', 'desc')
      ->order('a.room', 'asc')
      ->order('a.id', 'asc')
      ->select();
    
    foreach($list as $key=>$unit) {
      self::formatInfo($unit);
      $unit->allowView = self::allow($user, $unit, 'view', $building);
      $unit->allowEdit = self::allow($user, $unit, 'edit', $building);
      $unit->allowDelete = self::allow($user, $unit, 'delete', $building);
    }
    
    return $list;
  }

    /**
   * 通过ID获取单元信息
   */
  public static function getById($user, $id) {
    $user_id = 0;

    if ($user) {
      $user_id = $user->id;
    }

    $data = self::alias('a')
      ->join('building b', 'a.building_id = b.id')
      ->leftJoin('share s', "s.type = 'unit' and a.id = s.object_id and s.user_id = " . $user_id)
      ->where('a.id', $id)
      ->field('a.*,s.level as share_level')
      ->find();

    return $data;
  }

  /**
   * 根据ID获取单元信息
   */
  public static function detail($user, $id, $operate = 'view', $key = '') {
    $user_id = 0;

    if ($user) {
      $user_id = $user->id;
    }

    // 通过分享链接查看自动加入共享列表
    if ($user_id > 0 && !empty($key) && $key == md5('unit' . $id . config('wechat.app_secret'))) {
      $share = db('share')
        ->where('type', 'unit')
        ->where('user_id', $user_id)
        ->where('object_id', $id)
        ->find();

      if (null == $share) {
        db('share')->insert([
          'type' => 'unit',
          'user_id' => $user_id,
          'object_id' => $id
        ]);
      }
    }
    
    $unit = self::alias('a')
      ->join('building b', 'a.building_id = b.id')
      ->leftJoin('share s', "s.type = 'unit' and a.id = s.object_id and s.user_id = " . $user_id)
      ->leftJoin('user u', 'a.user_id = u.id')
      ->where('a.id', $id)
      ->field('a.id,building_id,b.building_name,a.building_no,a.floor,a.room,a.face,
        a.acreage,a.rent_sell,a.rent_price,a.sell_price,a.decoration,a.status,
        a.end_date,a.rem,a.share,a.user_id,a.company_id,a.create_time,u.title as user,u.avatar,
        s.level as share_level')
      ->find();

    if ($unit == null) {
      self::exception('单元不存在。');
    } else if ($operate != 'notes' && !self::allow($user, $unit, $operate)) {
      self::exception('您没有权限' . ($operate == 'view' ? '查看' : '修改') . '此单元。');
    } else {

      if (isset($unit->avatar) && $unit->avatar) {
        $find = strpos($unit->avatar, 'http');
        if ($find === false || $find > 0) {
          $unit->avatar = '/upload/user/images/60/' . $unit->avatar;
        }
      } else {
        $unit->avatar = '/static/img/avatar.png';
      }

      $images = [];
      $videos = [];
      
      $files = File::getList($user, 'unit', $id);
      if ($files) {
        foreach($files as $key => $file) {
          if ($file->is_image) {
            array_push($images, $file);
          } else if ($file->is_video) {
            array_push($videos, $file);
          }
        }
      }

      $unit->images = $images;
      $unit->videos = $videos;

      self::formatInfo($unit);
      $unit->key = md5('unit' . $unit->id . config('wechat.app_secret'));

      if ($operate == 'view') {
        $building = Building::getById($user, $unit->building_id);
        $unit->allowNew = self::allow($user, $unit, 'new', $building);
        $unit->allowEdit = self::allow($user, $unit, 'edit', $building);
        $unit->allowCopy = self::allow($user, $unit, 'copy', $building);
        $unit->allowDelete = self::allow($user, $unit, 'delete', $building);
        $unit->isFavorite = false;
        if ($user_id) {
          if (db('favorite')->where('user_id', $user_id)
            ->where('unit_id', $id)->find() != null) {
              $unit->isFavorite = true;
          }
        }

        $unit->linkman = Linkman::getByOwnerId($user, 'unit', $id, true, $unit->allowEdit ? -1 : 0);
      }
    }

    if (empty($user) || (!$user->isAdmin && $unit->company_id != $user->company_id)) {
      unset($unit['user']);
      unset($unit['avatar']);
      unset($unit['create_time']);
      unset($unit['create_time_text']);
    }

    return $unit;
  }

  /**
   * 添加/修改单元信息
   */
  public static function addUp($user, $id, $data) {
    if (empty($data['end_date'])) {
      unset($data['end_date']);
    }

    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    $copy = 0;
    if (isset($data['copy'])) {
      $copy = $data['copy'];
      unset($data['copy']);
    }

    if (isset($data['floor']) && ($data['floor'] == 'null' || empty($data['floor']))) {
      $data['floor'] = null;
    }

    if (isset($data['acreage']) && ($data['acreage'] == 'null' || empty($data['acreage']))) {
      $data['acreage'] = null;
    }

    if (isset($data['rent_price']) && ($data['rent_price'] == 'null' || empty($data['rent_price']))) {
      $data['rent_price'] = null;
    }

    if (isset($data['sell_price']) && ($data['sell_price'] == 'null' || empty($data['rent_price']))) {
      $data['sell_price'] = null;
    }

    if ($id) {
      $oldData = self::getById($user, $id);
      if ($oldData == null) {
        self::exception('单元不存在。');
      } else if (!self::allow($user, $oldData, 'edit')) {
        self::exception('您没有权限修改此单元。');
      }

      $summary = '';
      if ($data['building_no'] != $oldData->building_no) {
        if ($oldData->building_no) {
          $summary = '楼栋：' . $oldData->building_no . ' -> ' . $data['building_no'] . '\n';
        } else {
          $summary = '楼栋：' . $data['building_no'] . '\n';
        }
      }

      if ($data['floor'] != $oldData->floor) {
        if ($oldData->floor) {
          $summary = $summary . '楼层：' . $oldData->floor . ' -> ' . $data['floor'] . '\n';
        } else {
          $summary = $summary . '楼层：' . $data['floor'] . '\n';
        }
      }

      if ($data['room'] != $oldData->room) {
        if ($oldData->room) {
          $summary = $summary . '房号：' . $oldData->room . ' -> ' . $data['room'] . '\n';
        } else {
          $summary = $summary . '房号：' . $data['room'] . '\n';
        }
      }

      if ($data['face'] != $oldData->face) {
        if ($oldData->face) {
          $summary = $summary . '朝向：' . $oldData->face . ' -> ' . $data['face'] . '\n';
        } else {
          $summary = $summary . '朝向：' . $data['face'] . '\n';
        }
      }

      if ($data['acreage'] != $oldData->acreage) {
        if ($oldData->acreage) {
          $summary = $summary . '面积：' . $oldData->acreage . '平米 -> ' . $data['acreage'] . '平米\n';
        } else {
          $summary = $summary . '面积：' . $data['acreage'] . '平米\n';
        }
      }

      if ($data['rent_sell'] != $oldData->rent_sell) {
        if ($oldData->rent_sell) {
          $summary = $summary . '租售：' . $oldData->rent_sell . ' -> ' . $data['rent_sell'] . '\n';
        } else {
          $summary = $summary . '租售：' . $data['rent_sell'] . '\n';
        }
      }

      if ($data['rent_price'] != $oldData->rent_price) {
        if ($oldData->rent_price) {
          $summary = $summary . '出租价格：' . $oldData->rent_price . ' -> ' . $data['rent_price'] . '\n';
        } else {
          $summary = $summary . '出租价格：' . $data['rent_price'] . '\n';
        }
      }

      if ($data['sell_price'] != $oldData->sell_price) {
        if ($oldData->sell_price) {
          $summary = $summary . '出售价格：' . $oldData->sell_price . ' -> ' . $data['sell_price'] . '\n';
        } else {
          $summary = $summary . '出售价格：' . $data['sell_price'] . '\n';
        }
      }

      if ($data['decoration'] != $oldData->decoration) {
        if ($oldData->decoration) {
          $summary = $summary . '装修状况：' . $oldData->decoration . ' -> ' . $data['decoration'] . '\n';
        } else {
          $summary = $summary . '装修状况：' . $data['decoration'] . '\n';
        }
      }

      if ($data['status'] != $oldData->status) {
        $summary = $summary . '状态：' . self::$status[$oldData->status] . 
          ' -> ' . self::$status[$data['status']] . '\n';
      }

      if (isset($data['end_date'])) {
        if ($oldData->end_date) {
          $oldData->end_date = date_format(date_create($oldData->end_date), 'Y-m-d');
        }
        if ($data['end_date'] != $oldData->end_date) {
          if ($oldData->end_date) {
            $summary = $summary . '到期日：' . $oldData->end_date . ' -> ' . $data['end_date'] . '\n';
          } else {
            $summary = $summary . '到期日：' . $data['end_date'] . '\n';
          }
        }
      }

      if ($data['rem'] != $oldData->rem) {
        if ($oldData->rem) {
          $summary = $summary . '备注：' . $oldData->rem . ' -> ' . $data['rem'] . '\n';
        } else {
          $summary = $summary . '备注：' . $data['rem'] . '\n';
        }
      }

      $log = [
        "table" => 'building',
        "owner_id" => $oldData->building_id,
        "title" => '修改单元',
        "summary" => $summary
      ];

      if (isset($data['building_id'])) {
        unset($data['building_id']);
      }

      if (isset($data['user_id'])) {
        unset($data['user_id']);
      }

      // if ($oldData->user_id != $user_id) {
      //   if (isset($data['share'])) {
      //     unset($data['share']);
      //   }
      //   if (isset($data['company_id'])) {
      //     unset($data['company_id']);
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

      $result =  $oldData->save($data);
      if ($result && $summary) {
        Log::add($user, $log);
      }
      return $id;
    } else {
      $data['user_id'] = $user_id;
      if (empty($data['company_id'])) {
        $data['company_id'] = $user->company_id;
      }

      $linkman = null;

      if (isset($data['linkman'])) {
        if ($data['linkman']) {
          $linkman['title'] = $data['linkman'];
        }
        unset($data['linkman']);
      }

      if (isset($data['mobile'])) {
        if ($data['mobile']) {
          $linkman['mobile'] = $data['mobile'];
        }
        unset($data['mobile']);
      }

      $summary = $data['building_no'];
      if ($data['floor'] > 0) {
        $summary = $summary . $data['floor'] . '层';
      } else if ($data['floor'] < 0) {
        $summary = $summary . '地下' . abs($data['floor']) . '层';
      }
      if ($data['room']) {
        $summary = $summary . $data['room'];
      }

      $newData = new Unit($data);

      if (!self::allow($user, $newData, 'new')) {
        self::exception('您没有权限添加单元。');
      }

      $result = $newData->save();

      if ($result) {
        Log::add($user, [
          "table" => 'building',
          "owner_id" => $data['building_id'],
          "title" => '添加单元',
          "summary" => $summary
        ]);

        if ($linkman) {
          $linkman['type'] = 'unit';
          $linkman['owner_id'] = $newData->id;
          Linkman::addUp($user, 0, $linkman);
        }

        if ($copy > 0) {
          // 复制图片
          $fileData = db('file')
            ->where('type', 'unit')
            ->where('parent_id', $copy)
            ->where('delete_time', 'null')
            ->field('`type`,' . $newData->id . ' as parent_id,`title`,`file`,`size`,`default`,`sort`,now() as create_time,' . $user_id . ' as user_id')
            ->select();
          if ($fileData) {
            db('file')->insertAll($fileData);
          }
          // 复制联系人
          $linkmanData = db('linkman')
            ->where('type', 'unit')
            ->where('owner_id', $copy)
            ->where('delete_time', 'null')
            ->field('`type`,' . $newData->id . ' as owner_id,`title`,`department`,`job`,`mobile`,`tel`,`email`,`weixin`,`qq`,`rem`,`status`,now() as create_time,' . $user_id . ' as user_id')
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
   * 删除单元
   */
  public static function remove($user, $id) {
    $unit = self::get($id);
    if ($unit == null) {
      return true;
    } else if (!self::allow($user, $unit, 'delete')) {
      self::exception('您没有权限删除此单元。');
    }

    self::formatInfo($unit);

    $log = [
      "table" => 'building',
      "owner_id" => $unit->building_id,
      "title" => '删除单元',
      "summary" => $unit->title
    ];
    $result = $unit->delete();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }
}