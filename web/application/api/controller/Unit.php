<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\File;
use app\api\model\Building as modelBuilding;
use app\api\model\Unit as modelUnit;
use app\api\model\Customer;

class Unit extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['except'=>'detail']
  ];

  /**
   * 获取房源单元
   */
  public function buildingUnit($id) {
    if ($id) {
      $data = modelUnit::getByBuildingId($id, $this->user_id, $this->company_id);
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 查看单元信息
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelUnit::detail($id, $this->user_id, $this->company_id);
      if ($data != null) {
        $data->customer = Customer::search(['status' => '0,1,2', 'clash' => false], $this->user_id, $this->company_id);
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
      $companyList = \app\api\model\Company::my($this->user_id);
      if ($id > 0) {
        $data = modelUnit::get($id);
        if ($data == null) {
          return $this->exception('单元不存在。');
        } else if ($data->user_id != $this->user_id && 
          $data->company_id != $this->company_id) {
          return $this->exception('您没有权限修改这个单元。');
        }
        $data->__token__ = $form_token;
        $data->images = File::getList($id, 'unit');
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
          'floor'  => 'require|token'
        ],[
          'floor.require' => '必须填写楼层。',
          'floor.token' => '无效请求，请勿重复提交。'
        ]);
      } else {
        $validate = Validate::make([
          'floor'  => 'require|token',
          'mobile' =>'mobile'
        ],[
          'floor.require' => '必须填写楼层。',
          'floor.token' => '无效请求，请勿重复提交。',
          'mobile.mobile' => '联系人手机号码无效'
        ]);
      }

      $data = input('post.');
      
      if (isset($data['mobile'])) {
        $data['mobile'] = str_replace(' ', '', $data['mobile']);
      }
      
      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        unset($data['__token__']);
        $result = modelUnit::addUp($id, $data, $this->user_id, $this->company_id);
        if ($result) {
          return $this->succeed($result);
        } else {
          return $this->fail();
        }
      }
    }
  }

  /**
   * 添加到收藏夹
   */
  public function favorite($id) {
    $result = modelBuilding::favorite(0, $id, $this->user_id);
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
    $result = modelBuilding::unFavorite(0, $id, $this->user_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 删除单元
   */
  public function remove($id, $bid) {
    $result = modelUnit::remove($id, $this->user_id);
    if ($result == 1) {
      if ($bid) {
        $data = modelUnit::getByBuildingId($bid, $this->user_id, $this->company_id);
        return $this->succeed($data);
      } else {
        return $this->succeed();
      }
    } else {
      return $this->fail();
    }
  }
}