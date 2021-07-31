<?php

namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\File;

class Article extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $status = ['隐藏', '公开'];

  /**
   * 权限检查
   */
  public static function allow($user, $article, $operate)
  {
    if ($article == null && $operate != 'new') {
      return false;
    }
    if ($operate == 'view') {
      return $article->status == 1 || ($user != null && ($user->isAdmin || $user->id == $article->user_id));
    } else if ($operate == 'new') {
      return $user != null && $user->isAdmin;
    } else if ($operate == 'edit') {
      return $user != null && ($user->isAdmin || $user->id == $article->user_id);
    } else if ($operate == 'delete') {
      return $user != null && ($user->isAdmin || $user->id == $article->user_id);
    } else {
      return false;
    }
  }

  /**
   * 格式化信息
   */
  public static function formatInfo($article)
  {
    if ($article != null) {
      if ($article->create_time) {
        $article->create_time_text = date('Y年n月j日 H:i', strtotime($article->create_time));
      } else {
        $article->create_time_text = '';
      }
      $article->src = empty($article->cover) ? '/static/img/error.png' : '/upload/article/images/200/' . $article->cover;
      $article->fallbackSrc = '/static/img/error.png';
      $article->url = '/article/view/' . $article->id;
      $article->h5_url = '/index/article/' . $article->id;
      $article->desc = $article->create_time_text . ' ' . $article->username;
      if ($article->cover) {
        $article->cover = '/upload/article/images/640/' . $article->cover;
      }
    }
  }

  /**
   * 查询
   */
  public static function search($user, $filter)
  {
    if (!isset($filter['page'])) {
      $filter['page'] = 1;
    }

    if (!isset($filter['page_size'])) {
      $filter['page_size'] = 10;
    }

    $list = self::alias('a')
      ->leftJoin('user u', "a.user_id = u.id");

    if (isset($filter['status'])) {
      $list->where('a.status', $filter['status']);
    }

    if (isset($filter['type'])) {
      $list->where('a.type', $filter['type']);
    }

    if (isset($filter['banner'])) {
      $list->where('a.cover', '<>', '');
    }

    if (isset($filter['user_id'])) {
      $list->where('a.user_id', $filter['user_id']);
    }

    if (isset($filter['keyword']) && $filter['keyword'] != '') {
      $list->where("(a.title like '" . $filter['keyword'] . "%' OR a.content like '%" . $filter['keyword'] . "%')");
    }

    $list = $list->field('a.id,a.type,a.cover,a.title,a.summary,a.create_time,a.user_id,u.title as username')
      ->order(['a.top' => 'desc', 'a.id' => 'desc'])
      ->page($filter['page'], $filter['page_size'])
      ->select();

    foreach ($list as $key => $article) {
      self::formatInfo($article);
    }

    return $list;
  }


  /**
   * 通过ID获取文章信息
   */
  public static function getById($user, $id)
  {
    $data = self::alias('a')
      ->leftJoin('user u', "a.user_id = u.id")
      ->where('a.id', $id)
      ->field('a.*,u.title as username')
      ->find();

    return $data;
  }

  /**
   * 根据ID获取单元信息
   */
  public static function detail($user, $id, $operate = 'view')
  {
    $data = self::alias('a')
      ->leftJoin('user u', "a.user_id = u.id")
      ->where('a.id', $id)
      ->field('a.id,a.type,a.cover,a.title,a.summary,a.content,a.top,a.status,a.user_id,a.create_time,u.title as username')
      ->find();

    if ($data == null) {
      self::exception('文章不存在。');
    } else if (!self::allow($user, $data, $operate)) {
      self::exception('您没有权限' . ($operate == 'view' ? '查看' : '修改') . '此文章。');
    } else {
      self::formatInfo($data);

      if ($operate == 'view') {
        $data->allowNew = self::allow($user, $data, 'new');
        $data->allowEdit = self::allow($user, $data, 'edit');
        $data->allowDelete = self::allow($user, $data, 'delete');
      }
    }

    return $data;
  }

  /**
   * 添加/修改文章
   */
  public static function addUp($user, $id, $data, $cover)
  {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    if ($id) {
      $oldData = self::getById($user, $id);
      if ($oldData == null) {
        self::exception('文章不存在。');
      } else if (!self::allow($user, $oldData, 'edit')) {
        self::exception('您没有权限修改此文章。');
      }

      if ($cover) {
        $coverPath = self::uploadImage($cover);
        if ($coverPath) {
          $data['cover'] = $coverPath;
        }
      } else if (isset($data['cover'])) {
        unset($data['cover']);
      }

      $log = [
        "table" => 'article',
        "owner_id" => $oldData->id,
        "title" => '修改文章',
        "summary" => $data['title']
      ];

      $result = $oldData->save($data);
      if ($result) {
        Log::add($user, $log);
      }
      return $id;
    } else  if (!self::allow($user, null, 'new')) {
        self::exception('您没有权限添加文章。');
    } else {
      $data['user_id'] = $user_id;

      if ($cover) {
        $coverPath = self::uploadImage($cover);
        if ($coverPath) {
          $data['cover'] = $coverPath;
        }
      } else if (isset($data['cover'])) {
        unset($data['cover']);
      }

      $newData = new Article($data);
      $result = $newData->save();

      if ($result) {
        Log::add($user, [
          "table" => 'article',
          "owner_id" => $newData->id,
          "title" => '添加文章',
          "summary" => $data['title']
        ]);

        return $newData->id;
      } else {
        return false;
      }
    }
  }

  /**
   * 上传图片
   */
  public static function upload($user, $image) {
    if ($image) {
      $imagePath = self::uploadImage($image);
      return  '/upload/article/images/640/' . $imagePath;
    } else {
      return false;
    }
  }

  /**
   * 顶置/取消顶置文章
   */
  public static function top($user, $id, $top)
  {
    $data = self::get($id);
    if ($data == null) {
      self::exception('文章不存在。');
    } else if (!self::allow($user, $data, 'edit')) {
      self::exception('您没有权限修改此文章。');
    }
    $log = [
      "table" => 'article',
      "owner_id" => $data->id,
      "title" => ($top == 1 ? '顶置' : '取消顶置') . '文章',
      "summary" => $data->title
    ];
    $data->top = $top;
    $result = $data->save();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }

  /**
   * 公开/隐藏文章
   */
  public static function changeStatus($user, $id, $status)
  {
    $data = self::get($id);
    if ($data == null) {
      self::exception('文章不存在。');
    } else if (!self::allow($user, $data, 'edit')) {
      self::exception('您没有权限修改此文章。');
    }
    $log = [
      "table" => 'article',
      "owner_id" => $data->id,
      "title" => ($status == 1 ? '公开' : '隐藏') . '文章',
      "summary" => $data->title
    ];
    $data->status = $status;
    $result = $data->save();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }

  /**
   * 删除文章
   */
  public static function remove($user, $id)
  {
    $data = self::get($id);
    if ($data == null) {
      return true;
    } else if (!self::allow($user, $data, 'delete')) {
      self::exception('您没有权限删除此文章。');
    }

    $log = [
      "table" => 'article',
      "owner_id" => $data->id,
      "title" => '删除文章',
      "summary" => $data->title
    ];
    $result = $data->delete();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }

  /**
   * 上传图片
   */
  private static function uploadImage($image)
  {
    $uploadPath = '../public/upload/article/images';
    $info = $image->validate(['size' => 2097152, 'ext' => 'jpg,jpeg,png,gif'])
      ->rule('uniqid')->move($uploadPath . '/original');

    if ($info) {
      File::thumbImage($info, [200, 640], $uploadPath);
      return $info->getFilename();
    } else {
      self::exception($image->getError());
    }
  }
}
