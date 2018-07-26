<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\File;
use app\api\model\Linkman;

class Unit extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $status = ['在驻','空置','已出'];
  public static $share = ['隐藏','公开'];

  /**
   * 格式化单元信息
   */
  public static function formatInfo($unit) {
    if ($unit != null) {
      if (!empty($unit->title)) {
        $unit->title = $unit->title . $unit->building_no;
      } else {
        $unit->title = $unit->building_no;
      }
      if ($unit->floor > 0) {
        $unit->title = $unit->title . $unit->floor . '层';
      } else if ($unit->floor < 0) {
        $unit->title = $unit->title . '地下' . abs($unit->floor) . '层';
      }
      if ($unit->room) {
        $unit->title = $unit->title . $unit->room;
      }
    }
  }

  /**
   * 通过房源ID获取单元列表
   */
  public static function getByBuildingId($id, $user_id = 0, $company_id = 0) {
    $list = self::where('building_id', $id)
      ->where('share = 1 OR (user_id = ' . $user_id . ') OR (company_id > 0 AND company_id = ' . $company_id . ')')
      ->order('building_no', 'asc')
      ->order('floor', 'desc')
      ->order('room', 'asc')
      ->order('id', 'asc')
      ->select();
    
    foreach($list as $key=>$unit) {
      self::formatInfo($unit);
    }
    
    return $list;
  }

  /**
   * 根据ID获取单元信息
   */
  public static function detail($id, $user_id = 0, $company_id = 0) {
    $unit = self::alias('a')
      ->join('building b', 'a.building_id = b.id')
      ->where('a.id', $id)
      ->field('a.*,b.building_name')
      ->find();

    if ($unit == null) {
      self::exception('单元不存在。');
    } else if ($unit->share == 0 &&
      $unit->user_id != $user_id && 
      $unit->company_id != $company_id) {
      self::exception('您没有权限查看此单元。');
    } else {
      $unit->images = File::getList($id, 'unit');
      $unit->linkman = Linkman::getByOwnerId($id, 'unit', $user_id);
      $unit->isFavorite = false;
      self::formatInfo($unit);
      if ($user_id) {
        if (db('favorite')->where('user_id', $user_id)
          ->where('unit_id', $id)->find() != null) {
            $unit->isFavorite = true;
        }
      }
    }
    return $unit;
  }

  /**
   * 添加/修改单元信息
   */
  public static function addUp($id, $data, $user_id, $company_id = 0) {
    if (empty($data['end_date'])) {
      unset($data['end_date']);
    }

    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('单元不存在。');
      } else if ($oldData->share == 0 &&
        $oldData->user_id != $user_id && 
        $oldData->company_id != $company_id) {
        self::exception('您没有权限查看此单元。');
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

      if ($data['share'] != $oldData->share) {
        $summary = $summary . '是否公开：' . self::$share[$oldData->share] . 
          ' -> ' . self::$share[$data['share']] . '\n';
      }

      $log = [
        "table" => 'building',
        "owner_id" => $oldData->building_id,
        "title" => '修改单元',
        "summary" => $summary,
        "user_id" => $user_id
      ];

      if (isset($data['building_id'])) {
        unset($data['building_id']);
      }

      if (isset($data['user_id'])) {
        unset($data['user_id']);
      }

      if ($oldData->user_id != $user_id) {
        if (isset($data['share'])) {
          unset($data['share']);
        }
        if (isset($data['company_id'])) {
          unset($data['company_id']);
        }
      }

      $result =  $oldData->save($data);
      if ($result && $summary) {
        Log::add($log);
      }
      return $id;
    } else {
      $data['user_id'] = $user_id;

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
      $result = $newData->save();

      if ($result) {
        Log::add([
          "table" => 'building',
          "owner_id" => $data['building_id'],
          "title" => '添加单元',
          "summary" => $summary,
          "user_id" => $user_id
        ]);

        if ($linkman) {
          $linkman['type'] = 'unit';
          $linkman['owner_id'] = $newData->id;
          Linkman::addUp(0, $linkman, $user_id);
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
  public static function remove($id, $user_id) {
    $unit = self::get($id);
    if ($unit == null) {
      return true;
    } else if ($unit->user_id != $user_id) {
      self::exception('您没有权限删除此单元。');
    }

    self::formatInfo($unit);

    $log = [
      "table" => 'building',
      "owner_id" => $unit->building_id,
      "title" => '删除单元',
      "summary" => $unit->title,
      "user_id" => $user_id
    ];
    $result = $unit->delete();
    if ($result) {
      Log::add($log);
    }
    return $result;
  }
}