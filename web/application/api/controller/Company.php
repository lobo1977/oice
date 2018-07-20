<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\Company as modelCompany;
use app\api\model\File;

class Company extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth'
  ];

 /**
  * 我的企业
  */
  public function index() {
    // 已加入企业
    $myList = modelCompany::my($this->user_id);
    // 待加入企业
    $waitList = modelCompany::my($this->user_id, 0);
    // 收到邀请的企业
    $inviteList = modelCompany::inviteMe($this->user->mobile);
    // 我创建的企业
    $createList = modelCompany::myCreate($this->user_id);

    return $this->succeed([
      'my'=> $myList, 
      'myWait' => $waitList,
      'inviteMe' => $inviteList,
      'myCreate' => $createList
    ]);
  }

  /**
   * 检索公开企业
   */
  public function search($keyword) {
    $list = modelCompany::search($keyword);
    return $this->succeed($list);
  }

  /**
   * 查看企业
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelCompany::detail($id, $this->user_id);
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 添加修改企业
   */
  public function edit($id) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      if ($id > 0) {
        $data = modelCompany::detail($id, $this->user_id);
        if ($data->user_id != $this->user_id) {
          return $this->exception('您没有权限修改此企业。');
        } else {
          $data->__token__ = $form_token;
          return $this->succeed($data);
        }
      } else {
        return $this->succeed([
          "__token__" => $form_token
        ]);
      }
    } else {
      $validate = Validate::make([
        'title'  => 'require|token'
      ],[
        'title.require' => '必须填写企业名称。',
        'title.token' => '无效请求，请勿重复提交。'
      ]);

      $data = input('post.');
      $logo = request()->file('logo');
      $stamp = request()->file('stamp');
      
      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        unset($data['__token__']);
        $result = modelCompany::addUp($id, $data, $logo, $stamp, $this->user_id);
        if ($result) {
          return $this->succeed($result);
        } else {
          return $this->fail();
        }
      }
    }
  }

  /**
   * 删除企业
   */
  public function remove($id) {
    $result = modelCompany::remove($id, $this->user_id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 切换企业
   */
  public function setDefault($id) {
    $result = modelCompany::setDefault($id, $this->user_id);
    if ($result) {
      return $this->succeed($this->getUser(true));
    } else {
      return $this->fail();
    }
  }

  /**
   * 加入企业
   */
  public function addin($id) {
    $result = modelCompany::addin($id, $this->user_id);
    if ($result === 0 || $result == 1) {
      return $this->succeed($result);
    } else {
      return $this->fail();
    }
  }

  /**
   * 退出企业
   */
  public function quit($id) {
    $result = modelCompany::quit($id, $this->user_id);
    if ($result) {
      return $this->succeed($this->getUser(true));
    } else {
      return $this->fail();
    }
  }

  /**
   * 邀请加入
   */
  public function invite($id, $mobile) {
    $result = modelCompany::invite($id, $mobile, $this->user_id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 加入企业审核通过
   */
  public function passAddin($id, $user_id) {
    $result = modelCompany::passAddin($id, $user_id, $this->user_id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 驳回加入企业/移除企业成员
   */
  public function rejectAddin($id, $user_id) {
    $result = modelCompany::rejectAddin($id, $user_id, $this->user_id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 企业成员列表
   */
  public function user($id, $page) {
    $result = modelCompany::Member($id, 1, $this->user_id, $page);
    return $this->succeed($result);
  }
}