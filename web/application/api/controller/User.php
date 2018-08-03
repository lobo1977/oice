<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\User as modelUser;

class User extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' 
  ];

  /**
   * 企业成员列表
   */
  public function companyMember($id, $page) {
    $result = modelUser::companyMember($this->user, $id, 1, $page);
    return $this->succeed($result);
  }

  /**
   * 查看用户信息
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelUser::getById($id);
      return $this->succeed($data);
    } else {
      return;
    }
  }
}