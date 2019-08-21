<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\Post as modelPost;
use app\api\model\Company;

class Post extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['except'=>'index,detail'],
  ];

  /**
   * 检索公告信息
   */
  public function index() {
    $params = input('post.');
    if ($params) {
      $list = modelPost::search($this->user, $params);
      return $this->succeed($list);
    } else {
      return;
    }
  }

  /**
   * 查看共告信息
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelPost::detail($this->user, $id);
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 添加/修改公告信息
   */
  public function edit($id) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      $companyList = Company::my($this->user);
      if ($id > 0) {
        $data = modelPost::detail($this->user, $id, 'edit');
        $data->__token__ = $form_token;
        $data->companyList = $companyList;
        return $this->succeed($data);
      } else {
        return $this->succeed([
          "__token__" => $form_token,
          'companyList' => $companyList
        ]);
      }
    } else {
      $validate = Validate::make([
        'title'  => 'require|token',
        'content'  => 'require'
      ],[
        'title.require' => '必须填写公告名称。',
        'title.token' => '无效请求，请勿重复提交。',
        'content.require' => '必须填写公告内容'
      ]);

      $data = input('post.');
      
      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        $form_token = $this->formToken();
        unset($data['__token__']);
        $result = modelPost::addUp($this->user, $id, $data);
        if ($result) {
          return $this->succeed($result);
        } else {
          return $this->fail('系统异常。', $form_token);
        }
      }
    }
  }

  /**
   * 删除公告
   */
  public function remove($id) {
    $result = modelPost::remove($this->user, $id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }
}