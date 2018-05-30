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
      $data = modelLinkman::detail($id, $this->user_id, $this->company_id);
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
        $data = modelLinkman::get($id);
        if ($data == null) { 
          return $this->exception('联系人不存在。');
        } else if ($data->user_id > 0 && $data->user_id != $this->user_id) {
          return $this->exception('您没有权限修改此联系人。');
        }
        $data->__token__ = $form_token;
        return $this->succeed($data);
      } else {
        return $this->succeed(["__token__" => $form_token]);
      }
    } else {
      $validate = Validate::make([
        'title'  => 'require|token',
        'mobile' =>'require|mobile',
        'email' => 'email'
      ],[
        'title.require' => '必须填写联系人姓名。',
        'title.token' => '无效请求，请勿重复提交。',
        'mobile.require' => '必须填写手机号码',
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
        $result = modelLinkman::addUp($id, $data, $this->user_id);
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
    $result = modelLinkman::remove($id, $this->user_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }
}