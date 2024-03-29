<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\common\Utils;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\Building;
use app\api\model\Unit;

class File extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  /**
   * 权限检查
   */
  public static function allow($user, $type, $id, $operate) {
    if ($type == 'building') {
      $building = Building::getById($user, $id);
      return Building::allow($user, $building, $operate);
    } else if ($type == 'unit') {
      $unit = Unit::getById($user, $id);
      return Unit::allow($user, $unit, $operate);
    } else if ($type == 'customer') {
      $customer = Customer::getById($user, $id);
      return Customer::allow($user, $customer, $operate);
      // return Unit::allow($user, $unit, $operate);
    } else {
      return true;
    }
  }

  /**
   * 查询列表
   */
  public static function getList($user, $type, $parent_id) {
    $query = self::alias('a')
      ->leftJoin('user u', 'a.user_id = u.id')
      ->where('a.parent_id', $parent_id);
    if ($type) {
      $query = $query->where('a.type', $type);
    }
    $list = $query->field('a.id,a.title,a.file,a.size,a.default,a.user_id,
      u.title as username,a.create_time')
      ->order('a.default', 'desc')
      ->order('a.id', 'asc')->select();

    foreach($list as $key => $file) {
      $file->is_image = Utils::isImageFile($file->file);
      $file->is_video = Utils::isVideoFile($file->file);
      if ($file->is_image) {
        if ($type == 'building' || $type == 'unit') {
          $file->src = '/upload/' . $type . '/images/900/' . $file->file;
          $file->msrc = '/upload/' . $type . '/images/300/' . $file->file;
          $file->url = $file->src;
        } else {
          $file->src = '/upload/' . $type . '/attach/' . $file->file;
          $file->msrc = '/upload/' . $type . '/attach/' . $file->file;
          $file->url = $file->src;
        }
      } else if ($file->is_video) {
        if ($type == 'building' || $type == 'unit') {
          $file->src = '/upload/' . $type . '/images/original/' . $file->file;
          $file->msrc = '/upload/' . $type . '/images/300/' . Utils::replaceExt($file->file, 'jpg');
          $file->url = $file->src;
        } else {
          $file->src = '/upload/' . $type . '/attach/' . $file->file;
          $file->msrc = '/upload/' . $type . '/attach/' . $file->file;
          $file->url = $file->src;
        }
      } else {
        $ext = Utils::getFileExt($file->file);
        if ($ext == 'ppt' || $ext == 'pptx') {
          $file->src = '/static/img/ppt.png';
          $file->msrc = '/static/img/ppt.png';
        } else if ($ext == 'pdf') {
          $file->src = '/static/img/pdf.png';
          $file->msrc = '/static/img/pdf.png';
        } else if ($ext == 'zip' || $ext == 'rar') {
          $file->src = '/static/img/zip.png';
          $file->msrc = '/static/img/zip.png';
        } else if ($ext == 'doc' || $ext == 'docx') {
          $file->src = '/static/img/docx.png';
          $file->msrc = '/static/img/docx.png';
        } else if ($ext == 'xls' || $ext == 'xlsx') {
          $file->src = '/static/img/xlsx.png';
          $file->msrc = '/static/img/xlsx.png';
        } else {
          $file->src = '/static/img/attach.png';
          $file->msrc = '/static/img/attach.png';
        }
        $file->url = '/upload/' . $type . '/attach/' . $file->file;
      }
      if (mb_strlen($file->title) > 26) {
        $file->title = mb_substr($file->title, 0, 26) . '...';
      }
      if ($file->size) {
        $file->size = round(floatval($file->size) / 1048576, 2) . 'MB';
      }
      $file->upload_time = Utils::timeSpan($file->create_time);
    }

    return $list;
  }

  /**
   * 上传文件
   */
  public static function upload($user, $type, $parent_id, $is_default, $files) {
    $operate = 'edit';
    if ($type == 'customer') {
      $operate = 'follow';
    }

    if (!self::allow($user, $type, $parent_id, $operate)) {
      self::exception('操作失败，您没有权限。');
    }

    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    $count = self::where('type', $type)
      ->where('parent_id', $parent_id)
      ->where('default', 1)
      ->count();

    $result = 0;

    foreach($files as $key => $file) {
      $info = false;
      $uploadPath = '../public/upload/' . $type . '/images';
      $info = $file->validate(['size'=>20971520, 'ext'=>'jpg,jpeg,png,gif,mp4'])
        ->rule('uniqid')->move($uploadPath . '/original');

      if ($info) {
        if (Utils::isImageFile($info->getFilename())) {
          self::thumbImage($info, [900,300], $uploadPath);
        } else if (Utils::isVideoFile($info->getFilename())) {
          Utils::getVideoCover($uploadPath . '/original/' . $info->getFilename(), 
            $uploadPath . '/300/' . Utils::replaceExt($info->getFilename(), 'jpg'));
        }
      }

      if ($info) {
        $data['type'] = $type;
        $data['parent_id'] = $parent_id;
        $data['title'] = substr($info->getInfo('name'), 0, 300);
        $data['file'] = $info->getFilename();
        $data['size'] = $info->getSize();
        if ($is_default == 1 && $count == 0 && Utils::isImageFile($info->getFilename())) {
          $data['default'] = 1;
          $count = 1;
        } else {
          $data['default'] = 0;
        }
        $data['user_id'] = $user_id;
        
        $newData = new File($data);
        $result += $newData->save();

        if ($result) {
          Log::add($user, [
            "table" => $type,
            "owner_id" => $parent_id,
            "title" => '上传' . (Utils::isImageFile($info->getFilename()) ? '图片' : '视频'),
            "summary" => mb_strlen($newData->title) > 20 ? mb_substr($newData->title, 0, 20) . '...' : $newData->title 
          ]);
        }
      } else {
        self::exception($file->getError());
      }
    }

    return $result;
  }

    /**
   * 上传文件
   */
  public static function uploadAttach($user, $type, $parent_id, $files, $name = '') {
    $operate = 'edit';
    if ($type == 'customer') {
      $operate = 'follow';
    }

    if (!self::allow($user, $type, $parent_id, $operate)) {
      self::exception('操作失败，您没有权限。');
    }

    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    $result = 0;
    foreach($files as $key => $file) {
      $info = false;
      $uploadPath = '../public/upload/' . $type . '/attach';
      $info = $file->validate(['size'=>52428800,
        'ext'=>'jpg,jpeg,png,gif,csv,txt,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,mp4'])
        ->rule('uniqid')->move($uploadPath);

      if ($info) {
        $data['type'] = $type;
        $data['parent_id'] = $parent_id;
        $data['title'] = empty($name) ? substr($info->getInfo('name'), 0, 300) : $name;
        $data['file'] = $info->getFilename();
        $data['size'] = $info->getSize();
        $data['user_id'] = $user_id;
        
        $newData = new File($data);
        $result += $newData->save();

        if ($result) {
          Log::add($user, [
            "table" => $type,
            "owner_id" => $parent_id,
            "title" => '上传附件',
            "summary" => mb_strlen($newData->title) > 20 ? mb_substr($newData->title, 0, 20) . '...' : $newData->title 
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
  public static function setDefault($user, $id) {
    $file = self::get($id);
    if ($file == null) {
      self::exception('图片不存在。');
    }

    if ($file->default == 1) return 1;

    $type = $file->getAttr('type');

    if (!self::allow($user, $type, $file->parent_id, 'edit')) {
      self::exception('操作失败，您没有权限。');
    }

    $log = [
      "table" => $file->getAttr('type'),
      "owner_id" => $file->parent_id,
      "title" => '更改封面图',
      "summary" => $file->title
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
      Log::add($user, $log);
    }
    return $result;
  }

  /**
   * 删除文件
   */
  public static function remove($user, $id) {
    $file = self::get($id);
    if ($file == null) {
      return true;
    }

    $type = $file->getAttr('type');

    if ($file->user_id != $user->id && !self::allow($user, $type, $file->parent_id, 'edit')) {
      self::exception('操作失败，您没有权限。');
    }

    if ($file->default == 1 && ($type == 'building' || $type == 'unit')) {
      self::exception('封面图不能删除。');
    }

    $log = [
      "table" => $file->getAttr('type'),
      "owner_id" => $file->parent_id,
      "title" => '删除图片',
      "summary" => mb_strlen($file->title) > 20 ? mb_substr($file->title, 0, 20) . '...' : $file->title
    ];
    $result = $file->delete();
    if ($result) {
      Log::add($user, $log);
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