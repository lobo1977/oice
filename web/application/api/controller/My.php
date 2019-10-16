<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\User;
use app\api\model\Building;
use app\api\model\Customer;

class My extends Base
{
  protected $beforeActionList = [
    'checkAuth'
  ];

  /**
   * 我的收藏
   */
  public function favorite($page = 1) {
    $list = Building::myFavorite($this->user, $page);
    return $this->succeed($list);
  }

  /**
   * 通讯录
   */
  public function contact($page = 1) {
    $list = User::contact($this->user, $page);
    return $this->succeed($list);
  }

  /**
   * 添加通讯录
   */
  public function addContact($contact_id) {
    $result = User::addContact($this->user, $contact_id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 移除通讯录
   */
  public function removeContact($contact_id) {
    $result = User::removeContact($this->user, $contact_id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 我的项目
   */
  public function building($page = 1) {
    $list = Building::search($this->user, ['only_my' => 1, '' => $page]);
    return $this->succeed($list);
  }

  /**
   * 到期客户
   */
  public function task($page = 1) {
    $list = Customer::search($this->user, ['status' => '0,4,5', 'endDate' => true]);
    return $this->succeed($list);
  }

  /**
   * 我的可跟进客户
   */
  public function customer() {
    $list = Customer::search($this->user, ['status' => '0,1,2,3', 'clash' => false]);
    return $this->succeed($list);
  }

  /**
   * 修改账号信息
   */
  public function edit() {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      $data = User::getById($this->user_id);
      if ($data == null) {
        return $this->exception('账号不存在。');
      }
      $data->__token__ = $form_token;
      return $this->succeed($data);
    } else {
      $validate = Validate::make([
        'title'  => 'require|token',
        'email' => 'email|unique:user,email,' . $this->user_id
      ],[
        'title.require' => '必须填写姓名。',
        'title.token' => '无效请求，请勿重复提交。',
        'email.email' => '电子邮箱无效。',
        'email.unique' => '电子邮箱已存在，请使用其他邮箱。'
      ]);

      $data = input('post.');
      $avatar = request()->file('avatar');
      
      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        unset($data['__token__']);
        $result = User::updateInfo($this->user, $data, $avatar);
        if ($result) {
          return $this->succeed($this->getUser(true));
        } else {
          return $this->fail();
        }
      }
    }
  }

  /**
   * 更换手机号码
   */
  public function mobile($mobile, $verifyCode) {
    if ($mobile && $verifyCode) {
      $result = User::changeMobile($this->user, $mobile, $verifyCode);
      return $this->succeed($this->getUser(true));
    } else {
      return;
    }
  }

  /**
   * 修改密码
   */
  public function changePwd() {
    if (input('?post.password')) {
      $password = input('post.password');
      $result = User::changePassword($this->user, $password);
      if ($result == 1) {
        return $this->succeed();
      } else {
        return $this->fail('新密码和旧密码相同。');
      }
    } else {
      return;
    }
  }
}