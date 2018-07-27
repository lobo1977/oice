<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;

class File extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  /**
   * 查询列表
   */
  public static function getList($parent_id, $type) {
    $query = self::where('parent_id', $parent_id);
    if ($type) {
      $query = $query->where('type', $type);
    }
    $list = $query->field('id,title,file,default')
      ->order('default', 'desc')
      ->order('id', 'asc')->select();

    if ($type == 'building' || $type == 'unit') {
      foreach($list as $key => $img) {
        $img->src = '/upload/' . $type . '/images/900/' . $img->file;
        $img->msrc = '/upload/' . $type . '/images/300/' . $img->file;
      }
    }
    return $list;
  }

  /**
   * 上传图片
   */
  public static function uploadImage($type, $parent_id, $files, $user_id = 0, $company_id = 0) {
    $parent = db($type)->where('id', $parent_id)->find();

    if ($parent) {
      if ($parent['user_id'] > 0 &&
        $parent['user_id'] != $user_id &&
        $parent['company_id'] > 0 &&
        $parent['company_id'] != $company_id) {
        self::exception('操作失败，您没有权限。');
      }
    }

    $count = self::where('type', $type)
      ->where('parent_id', $parent_id)
      ->where('default', 1)->count();

    $result = 0;

    foreach($files as $key => $file) {
      $uploadPath = '../public/upload/' . $type . '/images';
      $info = $file->validate(['size'=>6291456,'ext'=>'jpg,jpeg,png,gif'])
        ->rule('uniqid')->move($uploadPath . '/original');
      if ($info) {
        if ($type == 'building' || $type == 'unit') {
          self::thumbImage($info, [900,300], $uploadPath);
        }

        $data['type'] = $type;
        $data['parent_id'] = $parent_id;
        $data['title'] = substr($info->getInfo('name'), 0, 300);
        $data['file'] = $info->getFilename();
        if ($count == 0 && $key == 0) {
          $data['default'] = 1;
        } else {
          $data['default'] = 0;
        }
        $data['use_id'] = $user_id;
        
        $newData = new File($data);
        $result += $newData->save();

        if ($result) {
          Log::add([
            "table" => $type,
            "owner_id" => $parent_id,
            "title" => '上传图片',
            "summary" => $newData->title,
            "user_id" => $user_id
          ]);
        }
      } else {
        self::exception($file->getError());
      }
    }

    return $result;
  }

  /**
   * 设置默认图片
   */
  public static function setDefault($id, $user_id, $company_id = 0) {
    $file = self::get($id);
    if ($file == null) {
      self::exception('图片不存在。');
    }

    $parent = db($file->getAttr('type'))->where('id', $file->parent_id)->find();
    if ($parent) {
      if ($parent['share'] == 0 && 
        $parent['user_id'] > 0 && 
        $parent['user_id'] != $user_id && 
        $parent['company_id'] != $company_id) {
        self::exception('操作失败，您没有权限。');
      }
    }

    if ($file->default == 1) return 1;

    $log = [
      "table" => $file->getAttr('type'),
      "owner_id" => $file->parent_id,
      "title" => '更改封面图',
      "summary" => $file->title,
      "user_id" => $user_id
    ];
    $before = self::where('parent_id', $file->parent_id)
      ->where('type', $file->getAttr('type'))
      ->where('default', 1)
      ->find();
    if ($before) {
      $before->default = 0;
      $before->save();
    }
    $file->default = 1;
    $result = $file->save();
    if ($result) {
      Log::add($log);
    }
    return $result;
  }

  /**
   * 删除图片
   */
  public static function removeImage($id, $user_id, $company_id = 0) {
    $file = self::get($id);
    if ($file == null) {
      return true;
    }

    $parent = db($file->getAttr('type'))->where('id', $file->parent_id)->find();
    if ($parent) {
      if ($parent['share'] == 0 && 
        $parent['user_id'] > 0 && 
        $parent['user_id'] != $user_id && 
        $parent['company_id'] != $company_id) {
        self::exception('操作失败，您没有权限。');
      }
    }

    if ($file->default == 1) {
      self::exception('封面图不能删除。');
    }

    $log = [
      "table" => $file->getAttr('type'),
      "owner_id" => $file->parent_id,
      "title" => '删除图片',
      "summary" => $file->title,
      "user_id" => $user_id
    ];
    $result = $file->delete();
    if ($result) {
      Log::add($log);
    }
    return $result;
  }

  /**
   * 生成缩略图
   */
  public static function thumbImage($image, $arrSize, $savePath) {
    foreach($arrSize as $size) {
      $oriImage = \think\Image::open($image);
      $path = $savePath . '/' . $size;
      if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
          self::exception('创建目录失败。');
        }
      }
      $newImage = $path . '/' . $image->getFilename();
      if (!file_exists($newImage)) {
        if ($oriImage->width() > $size || $oriImage->height() > $size) {
          $oriImage->thumb($size, $size)->save($newImage);
        } else {
          copy($image->getPathname(), $newImage);
        }
      }
    }
  }
}