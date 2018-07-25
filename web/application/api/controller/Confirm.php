<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\Confirm as modelConfirm;

class Confirm extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth'
  ];

  /**
   * 查看客户确认书
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelConfirm::detail($id, 0, 0, $this->user_id, $this->company_id);
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 添加/修改客户确认书
   */
  public function edit($id = 0, $bid = 0, $cid = 0) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      $companyList = \app\api\model\Company::my($this->user_id);
      $data = modelConfirm::detail($id, $bid, $cid, $this->user_id, $this->company_id);
      $data->__token__ = $form_token;
      $data->companyList = $companyList;
      return $this->succeed($data);
    } else {
      $validate = Validate::make([
        'acreage'  => 'require|token',
        'confirm_date' => 'require'
      ],[
        'acreage.require' => '必须填写面积。',
        'acreage.token' => '无效请求，请勿重复提交。',
        'confirm_date.require' => '必须填写确认日期。',
      ]);

      $data = input('post.');
      
      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        unset($data['__token__']);
        $result = modelConfirm::addUp($id, $data, $this->user_id);
        if ($result) {
          return $this->succeed($result);
        } else {
          return $this->fail();
        }
      }
    }
  }

  /**
   * 下载客户确认书
   */
  public function pdf($id = 0) {
    $data = modelConfirm::detail($id, 0, 0, $this->user_id, $this->company_id);
    if ($data->file) {
      $path = '/upload/confirm/' . $data->file;
    } else {
      $path = '/upload/confirm/' . modelConfirm::toPdf($data);
    }
    return $this->succeed($path);
  }
}