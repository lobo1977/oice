<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Company;

class DailyReview extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  public static $level = ['不合格','合格','优秀'];

  /**
   * 权限检查
   */
  public static function allow($user, $data, $operate) {
    if ($log == null || $user == null) {
      return false;
    } else if ($operate == 'new') {
      $superior_id = Company::getSuperior($data->company_id, $data->review_user);
      return $user->id == $superior_id;
    } else if ($operate == 'view') {
      return $data->user_id == $user->id || $data->review_user == $user->id;
    } else {
      return $data->user_id == $user->id;
    }
  }

  /**
   * 获取单一记录
   */
  public static function getById($user, $id, $operate = 'view') {
    if ($operate == 'view') {
      $data = self::alias('a')
        ->leftJoin('user b','b.id = a.user_id')
        ->leftJoin('company c','c.id = a.company_id')
        ->where('a.id', $id)
        ->field('a.*,b.title as username,b.avatar,b.mobile,c.title as company')
        ->find();
    } else {
      $data = self::get($id);
    }

    if ($data == null) {
      self::exception('记录不存在。');
    } else if (!self::allow($user, $data, $operate)) {
      if ($operate == 'view') {
        self::exception('您没有权限查看此批阅。');
      } else {
        self::exception('您没有权限修改此批阅。');
      }
    }

    if ($operate == 'view') {
      User::formatData($data);
      $data->allowEdit = self::allow($user, $data, 'edit');
      $data->allowDelete = self::allow($user, $data, 'delete');
    }
    return $data;
  }

  /**
   * 添加修改
   */
  public static function addUp($user, $id, $data) {
    $review = null;

    if ($id > 0) {
      $review = self::getById($user, $id, 'edit');
      if(isset($data['level'])) {
        $review->level = $data['level'];
      }
      $review->content = $data['content'];
    } else {
      if ($user) {
        $data['user_id'] = $user->id;
        $data['company_id'] = $user->company_id;
      }
      $review = new DailyReview($data);

      if (!self::allow($user, $review, 'new')) {
        self::exception('您没有权限批阅该用户的工作日报。');
      }
    }
    $result = $review->save();
    if (!$id && $result) {
      $message = $user->title . '已批阅了你的工作日报。';
      $url = 'http://' . config('app_host') . '/app/daily/review/' . $review->review_user . '/' . $review->review_date;
      User::pushMessage($review->review_user, $message, $url);
    }
    return $result;
  }

  /**
   * 查询列表
   */
  public static function getList($user, $review_user, $review_date) {
    $user_id = 0;
    $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    if (empty($review_date)) {
      $review_date = date("Y-m-d", time());
    }
    
    $list = self::alias('a')
      ->leftJoin('user b ','a.user_id = b.id')
      ->where('(a.user_id = ' . $user_id . ' OR a.review_user = ' . $user_id . 
        ') OR a.company_id = ' . $company_id)
      ->where('a.review_user', $review_user)
      ->where('a.review_date', 'between time', [$review_date, strtotime($review_date . ' +1 day')])
      ->field('a.id,a.level,a.content,a.create_time,a.company_id,a.user_id,b.title as user,b.mobile,b.avatar')
      ->order('a.id', 'desc')
      ->select();

    return $list;
  }

  /**
   * 删除
   */
  public static function remove($user, $id) {
    $data = self::get($id);
    if ($data == null) {
      return true;
    } else if (!self::allow($user, $data, 'delete')) {
      self::exception('您没有权限删除此记录。');
    } else {
      $result = $data->delete();
      return $result;
    }
  }
}