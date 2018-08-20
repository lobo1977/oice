<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\Company as modelCompany;

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
    $myList = modelCompany::my($this->user);
    // 待加入企业
    $waitList = modelCompany::my($this->user, 0);
    // 收到邀请的企业
    $inviteList = modelCompany::inviteMe($this->user);
    // 我创建的企业
    $createList = modelCompany::myCreate($this->user);

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
    $list = modelCompany::search($this->user, $keyword);
    return $this->succeed($list);
  }

  /**
   * 查看企业
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelCompany::detail($this->user, $id);
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
        $data = modelCompany::detail($this->user, $id, 'edit');
        $data->__token__ = $form_token;
        return $this->succeed($data);
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
        $result = modelCompany::addUp($this->user, $id, $data, $logo, $stamp);
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
    $result = modelCompany::remove($this->user, $id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 切换企业
   */
  public function setActive($id) {
    $result = modelCompany::setActive($this->user, $id);
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
    $result = modelCompany::addin($this->user, $id);
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
    $result = modelCompany::quit($this->user, $id);
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
    $result = modelCompany::invite($this->user, $id, $mobile);
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
    $result = modelCompany::passAddin($this->user, $id, $user_id);
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
    $result = modelCompany::rejectAddin($this->user, $id, $user_id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 设置上级
   */
  public function setSuperior($user_id) {
    $result = modelCompany::setSuperior($this->user, $user_id);
    if ($result) {
      return $this->succeed($this->getUser(true));
    } else {
      return $this->fail();
    }
  }
}