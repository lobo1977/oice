<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\User;
use app\api\model\Log;

class Daily extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth'
  ];

 /**
  * 工作日报
  */
  public function index($page = 0) {
    $result = User::dailyUser($this->user, $page);
    return $this->succeed($result);
  }

  /**
   * 用户工作日报
   */
  public function user() {
    $params = input('post.');
    $list = Log::daily($this->user, $params);
    return $this->succeed($list);
  }
}