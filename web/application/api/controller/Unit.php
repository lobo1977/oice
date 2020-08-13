<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\Unit as modelUnit;
use app\api\model\Building;
use app\api\model\Customer;
use app\api\model\Company;

class Unit extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['except'=>'detail']
  ];

  /**
   * 获取项目单元
   */
  public function buildingUnit($id) {
    if ($id) {
      $data = modelUnit::getByBuildingId($this->user, $id);
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 查看单元信息
   */
  public function detail($id = 0, $key = '') {
    if ($id) {
      $data = modelUnit::detail($this->user, $id, 'view', $key);
      if ($data) {
        if ($this->user != null) {
          $data->customer = Customer::search($this->user, ['status' => '0,1,2,3', 'clash' => false]);
        }
        unset($data->user_id);
        unset($data->company_id);
      }
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 添加/修改单元信息
   */
  public function edit($id = 0) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      $companyList = Company::my($this->user);
      if ($id > 0) {
        $data = modelUnit::detail($this->user, $id, 'edit');
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
      if ($id > 0) {
        $validate = Validate::make([
          'room'  => 'require'
        ],[
          'room.require' => '必须填写房间号'
        ]);
      } else {
        $validate = Validate::make([
          'room'  => 'require',
          'mobile' =>'mobile'
        ],[
          'room.require' => '必须填写房间号',
          'mobile.mobile' => '联系人手机号码无效'
        ]);
      }

      $data = input('post.');
      
      if (isset($data['mobile'])) {
        $data['mobile'] = str_replace(' ', '', $data['mobile']);
      }
      
      if (!$this->checkFormToken($data)) {
        return $this->fail('无效请求，请勿重复提交表单');
      } else if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        $form_token = $this->formToken();
        unset($data['__token__']);
        $result = modelUnit::addUp($this->user, $id, $data);
        if ($result) {
          return $this->succeed($result);
        } else {
          return $this->fail('系统异常。', $form_token);
        }
      }
    }
  }

  /**
   * 添加到收藏夹
   */
  public function favorite($id) {
    $result = Building::favorite($this->user, 0, $id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 从收藏夹删除
   */
  public function unFavorite($id) {
    $result = Building::unFavorite($this->user, 0, $id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 删除单元
   */
  public function remove($id, $bid = 0) {
    $result = modelUnit::remove($this->user, $id);
    if ($result == 1) {
      if ($bid) {
        $data = modelUnit::getByBuildingId($this->user, $bid);
        return $this->succeed($data);
      } else {
        return $this->succeed();
      }
    } else {
      return $this->fail();
    }
  }
}