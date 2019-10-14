<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\Linkman as modelLinkman;

class Linkman extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['except'=>'linkman'],
  ];

  /**
   * 查看联系人信息
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelLinkman::detail($this->user, $id);
      return $this->succeed($data);
    } else {
      return;
    }
  }

   /**
   * 添加/修改联系人信息
   */
  public function edit($id) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      if ($id > 0) {
        $data = modelLinkman::detail($this->user, $id, 'edit');
        $data->__token__ = $form_token;
        return $this->succeed($data);
      } else {
        return $this->succeed(["__token__" => $form_token]);
      }
    } else {
      $validate = Validate::make([
        'title'  => 'require|token',
        'mobile' =>'mobile',
        'email' => 'email'
      ],[
        'title.require' => '必须填写联系人姓名。',
        'title.token' => '无效请求，请勿重复提交。',
        'mobile.mobile' => '手机号码无效',
        'email.email' => '联系人电子邮箱无效'
      ]);

      $data = input('post.');

      if (isset($data['mobile'])) {
        $data['mobile'] = str_replace(' ', '', $data['mobile']);
      }
      
      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        unset($data['__token__']);
        $result = modelLinkman::addUp($this->user, $id, $data);
        if ($result) {
          return $this->succeed($result);
        } else {
          return $this->fail();
        }
      }
    }
  }

  /**
   * 删除联系人
   */
  public function remove($id) {
    $result = modelLinkman::remove($this->user, $id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }
}