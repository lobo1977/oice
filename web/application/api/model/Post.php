<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use think\facade\Validate;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\Company;

class Post extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $status = ['隐藏','公开'];

  /**
   * 格式化列表数据
   */
  protected static function formatList($list) {
    foreach($list as $key=>$item) {
    }
    return $list;
  }

  /**
   * 权限检查
   */
  public static function allow($user, $post, $operate) {
    if ($post == null && $operate != 'new') {
      return false;
    }
    if ($operate == 'view') {
      return $post->status == 1 ||
        ($user != null && $post->user_id == $user->id) ||
        ($user != null && $post->company_id > 0 && $post->company_id == $user->company_id);
    } else if ($operate == 'new') {
      return $user != null;
    } else if ($operate == 'edit') {
      return ($user != null && $post->user_id == $user->id) ||
        ($user != null && $post->company_id > 0 && $post->company_id == $user->company_id);
    } else if ($operate == 'delete') {
      return ($user != null && $post->user_id == $user->id) ||
        ($user != null && $post->company_id > 0 && $post->company_id == $user->company_id);
    } else {
      return false;
    }
  }
  
  /**
   * 检索公告信息
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

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }
  
    $list = self::alias('a')
      ->where('(a.status = 1 OR a.user_id = ' . $user_id . ' OR 
        (a.company_id > 0 AND a.company_id = ' . $company_id . '))');
    
    if (isset($filter['keyword']) && $filter['keyword'] != '') {
      $list->where('a.title|a.content', 'like', $filter['keyword'] . '%');
    }

    $result = $list->field('a.id,a.title,a.content')
      ->page($filter['page'], $filter['page_size'])
      ->order('a.update_time', 'desc')->order('a.id', 'desc')
      ->select();

    return self::formatList($result);
  }

  /**
   * 我的公告
   */
  public static function my($user, $page = 1) {
    $user_id = 0;
    $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    $list = self::alias('a')
      ->where('(a.user_id > 0 AND a.user_id = ' . $user_id . ') OR ' . 
        '(a.company_id > 0 AND a.company_id = ' . $company_id . ')');

    $result = $list->field('a.id,a.title,a.content')
      ->page($page, 10)
      ->order('a.update_time', 'desc')->order('a.id', 'desc')
      ->select();

    return self::formatList($result);
  }

  /**
   * 获取公告详细信息
   */
  public static function detail($user, $id, $operate = 'view') {
    $data = self::where('id', $id)
      ->field('id,title,content,status,user_id,company_id')->find();

    if ($data == null) {
      self::exception('公告信息不存在。');
    } else if (!self::allow($user, $data, $operate)) {
      self::exception('您没有权限' . ($operate == 'edit' ? '编辑' : '查看') . '此公告信息。');
    }
    if ($operate == 'view') {
      $data->allowEdit = self::allow($user, $data, 'edit');
      $data->allowDelete = self::allow($user, $data, 'delete');
    }
    return $data;
  }

  /**
   * 添加/修改发布信息
   */
  public static function addUp($user, $id, $data) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('公告信息不存在。');
      } else if (!self::allow($user, $oldData, 'edit')) {
        self::exception('您没有权限修改此公告信息。');
      }

      $summary = $data['title'];

      if (isset($data['user_id'])) {
        unset($data['user_id']);
      }

      $result = $oldData->save($data);

      if ($result) {
        Log::add($user, [
          "table" => "post",
          "owner_id" => $id,
          "title" => '修改公告信息',
          "summary" => $summary
        ]);
      }
      return $id;
    } else if (!self::allow($user, null, 'new')) {
      self::exception('您没有权限添加公告。');
    } else {
      $data['user_id'] = $user_id;
      $newData = new Post($data);
      $result = $newData->save();

      if ($result) {
        Log::add($user, [
          "table" => "post",
          "owner_id" => $newData->id,
          "title" => '发布公告信息',
          "summary" => $newData->title
        ]);
        return $newData->id;
      } else {
        return false;
      }
    }
  }

  /**
   * 删除公告
   */
  public static function remove($user, $id) {
    $post = self::get($id);
    if ($post == null) {
      return true;
    } else if (!self::allow($user, $post, 'delete')) {
      self::exception('您没有权限删除此公告信息。');
    }
    
    $log = [
      "table" => 'post',
      "owner_id" => $post->id,
      "title" => '删除公告信息',
      "summary" => $post->title
    ];
    $result = $post->delete();
    if ($result) {
      Log::add($user, $log);
    }
    return $result;
  }
}