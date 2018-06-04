<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\Customer as modelCustomer;
use app\api\model\Filter;
use app\api\model\Recommend;
use app\api\model\Log;

class Customer extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['except'=>'index,detail,show']
  ];

  /**
   * 检索客户信息
   */
  public function index() {
    $params = input('post.');

    if ($params) {
      $list = modelCustomer::search($params, $this->user_id, $this->company_id);

      if (!$list->isEmpty()) {
        foreach($list as $key=>$customer) {
          $customer->title = '【' . modelCustomer::$status[$customer['status']] . '】' . $customer->customer_name;
          
          $customer->desc = (empty($customer->lease_buy) ? '' : $customer->lease_buy) . 
            (empty($customer->demand) ? '' : $customer->demand . ' ');

          if ($customer->min_acreage && $customer->max_acreage) {
              $customer->desc = $customer->desc . ' ' . $customer->min_acreage . ' 至 ' . $customer->max_acreage . ' 平米';
          } else if ($customer->min_acreage) {
              $customer->desc = $customer->desc . ' ' . $customer->min_acreage . ' 平米以上';
          } else if ($customer->max_acreage) {
              $customer->desc = $customer->desc . ' ' . $customer->max_acreage . ' 平米以内';
          }

          if ($customer->budget) {
            $customer->desc = $customer->desc . ' ' . $customer->budget;
          }

          $customer->url = '/customer/view/' . $customer->id;
        }
      }
      return $this->succeed($list);
    } else {
      return;
    }
  }

  /**
   * 查看客户信息
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelCustomer::detail($id, $this->user_id, $this->company_id);
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 添加/修改客户信息
   */
  public function edit($id = 0) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      $companyList = \app\api\model\Company::my($this->user_id);
      if ($id > 0) {
        $data = modelCustomer::get($id);
        if ($data == null) {
          return $this->exception('客户不存在。');
        } else if ($data->user_id != $this->user_id) {
          return $this->exception('您没有权限修改这个客户。');
        } else {
          $data->__token__ = $form_token;
          $data->companyList = $companyList;
          return $this->succeed($data);
        }
      } else {
        return $this->succeed([
          "__token__" => $form_token, 
          'companyList' => $companyList
        ]);
      }
    } else {
      if ($id > 0) {
        $validate = Validate::make([
          'customer_name'  => 'require|token'
        ],[
          'customer_name.require' => '必须填写客户名称。',
          'customer_name.token' => '无效请求，请勿重复提交。'
        ]);
      } else {
        $validate = Validate::make([
          'customer_name'  => 'require|token',
          'linkman'  => 'require',
          'mobile' =>'require|mobile'
        ],[
          'customer_name.require' => '必须填写客户名称。',
          'customer_name.token' => '无效请求，请勿重复提交。',
          'linkman.require' => '必须填写联系人姓名。',
          'mobile.require' => '必须填写联系人手机号码',
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
        $result = modelCustomer::addUp($id, $data, 
          $this->user_id, $this->company_id);
        if ($result) {
          return $this->succeed($result);
        } else {
          return $this->fail();
        }
      }
    }
  }

  /**
   * 删除客户
   */
  public function remove($id) {
    $result = modelCustomer::remove($id, $this->user_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 添加/修改日志
   */
  public function log($id = 0, $cid = 0) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();

      if ($id > 0) {
        $data = Log::get($id);
        if ($data == null) {
          return $this->exception('跟进信息不存在。');
        } else {
          if ($data->system == 1) {
            return $this->exception('系统日志不可修改。');
          } else if ($data->user_id != $this->user_id) {
            return $this->exception('您没有权限修改此项跟进。');
          }
          $data->__token__ = $form_token;
          return $this->succeed($data);
        }
      } else if ($cid > 0) {
        $data = modelCustomer::get($cid);
        if ($data) {
          $data->__token__ = $form_token;
          return $this->succeed($data);
        } else {
          return $this->exception('客户信息不存在。');
        }
      } else {
        return $this->succeed(["__token__" => $form_token]);
      }
    } else {
      $data = input('post.');

      $validate = Validate::make([
        'title'  => 'require|token',
        'create_time' => 'date'
      ],[
        'title.require' => '必须填写摘要。',
        'title.token' => '无效请求，请勿重复提交。',
        'create_time.date' => '时间无效。'
      ]);

      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        unset($data['__token__']);
        if ($id == 0) {
          $data['table'] = 'customer';
        } else if (isset($data['owner_id'])) {
          unset($data['owner_id']);
        }
        Log::addUp($id, $data, $this->user_id);
        return $this->succeed();
      }
    }
  }

  /**
   * 删除跟进纪要
   */
  public function removeLog($id) {
    $result = Log::remove($id, $this->user_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 添加筛选
   */
  public function addFilter($cid, $ids) {
    $result = 0;
    foreach ($ids as $id) {
      $arrIds = explode(',', $id);
      if (count($arrIds) == 2) {
        $result += Filter::addBuilding($cid, intval($arrIds[0]), intval($arrIds[1]), $this->user_id);
      }
    }
    if ($result > 0) {
      $list = Filter::query($cid, $this->user_id);
      return $this->succeed($list);
    } else {
      return $this->fail();
    }
  }

  /**
   * 删除筛选
   */
  public function removeFilter($id, $building_id, $unit_id) {
    $result = Filter::removeBuilding($id, $building_id, $unit_id, $this->user_id);
    if ($result == 1) {
      $list = Filter::query($id, $this->user_id);
      return $this->succeed($list);
    } else {
      return $this->fail();
    }
  }

  /**
   * 移动筛选
   */
  public function sortFilter($id, $building_id, $unit_id, $up = 0) {
    $result = Filter::sort($id, $building_id, $unit_id, $this->user_id, $up);
    if ($result == 1) {
      $list = Filter::query($id, $this->user_id);
      return $this->succeed($list);
    } else {
      return $this->fail();
    }
  }

  /**
   * 生成推荐资料
   */
  public function recommend($cid, $mode, $ids) {
    $result = Recommend::addNew($cid, $mode, $ids, $this->user_id);
    if ($result) {
      $list = Recommend::query($cid, $this->user_id);
      return $this->succeed($list);
    } else {
      return $this->fail();
    }
  }

  /**
   * 删除资料
   */
  public function removeRecommend($id) {
    $result = Recommend::remove($id, $this->user_id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 手机版推荐资料
   */
  public function show($id) {
    $data = Recommend::detail($id);
    return $this->succeed($data);
  }
}