<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\Customer as modelCustomer;
use app\api\model\Company;
use app\api\model\Filter;
use app\api\model\Recommend;
use app\api\model\Confirm;
use app\api\model\Log;
use app\api\model\File;

class Customer extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['except'=>'show']
  ];

  /**
   * 检索客户信息
   */
  public function index() {
    $params = input('post.');

    if ($params) {
      $list = modelCustomer::search($this->user, $params);
      return $this->succeed($list);
    } else {
      return;
    }
  }

  /**
   * 查看客户信息
   */
  public function detail($id = 0, $key = '') {
    if ($id) {
      $data = modelCustomer::detail($this->user, $id, $key);
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 添加/修改客户信息
   */
  public function edit($id = 0, $flag = '', $bid = 0, $uid = '') {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      $companyList = Company::my($this->user);
      if ($id > 0) {
        $data = modelCustomer::detail($this->user, $id, '', 'edit');
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
          'customer_name'  => 'require'
        ],[
          'customer_name.require' => '必须填写客户名称。'
        ]);
      } else {
        $validate = Validate::make([
          'customer_name'  => 'require'
          //'tel' =>'require',
          //'linkman'  => 'require'
        ],[
          'customer_name.require' => '必须填写客户名称。'
          //'tel.require' => '必须填写直线电话',
          //'linkman.require' => '必须填写联系人姓名。'
        ]);
      }

      $data = input('post.');
      
      // if (isset($data['mobile'])) {
      //   $data['mobile'] = str_replace(' ', '', $data['mobile']);
      // }
      
      if (!$this->checkFormToken($data)) {
        return $this->fail('无效请求，请勿重复提交表单');
      } else if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), ['token' => $form_token]);
      } else {
        unset($data['__token__']);
        $result = modelCustomer::addUp($this->user, $id, $data);
        if (is_numeric($result) && $result > 0) {
          $message = '';
          if (isset($data['clash']) && $data['clash'] > 0) {
            $message = '撞单客户不可跟进，请等候管理员处理。';
          } else {
            // 添加筛选
            if ($flag == 'filter') {
              if ($bid) {
                Filter::addBuilding($this->user, $result, $bid, 0);
              } else if ($uid) {
                $arrIds = explode(',', $uid);
                foreach ($arrIds as $unit_id) {
                  Filter::addBuilding($this->user, $result, 0, intval($unit_id));
                }
              }
            }
          }
          return $this->succeed($result, $message);
        } else if (isset($result['message'])) {
          $form_token = $this->formToken();
          if (isset($result['data'])) {
            $result['data']['token'] = $form_token;
          } else {
            $result['data'] = ['token' => $form_token];
          }
          return $this->fail($result['message'], $result['data']);
        } else {
          return $this->fail();
        }
      }
    }
  }

  /**
   * 转交客户
   */
  public function turn($id, $user_id, $company_id = 0) {
    $data = null;
    if ($company_id) {
      $data = ['company_id' => $company_id];
    }
    $result = modelCustomer::transfer($this->user, $id, $user_id, $data, true);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 移除共享
   */
  public function removeShare($id, $user_id) {
    $result = modelCustomer::removeShare($this->user, $id, $user_id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 批量导入客户
   */
  public function import() {
    $data = request()->file('data');
    $result = modelCustomer::import($this->user, $data);
    if ($result) {
      return $this->succeed($result);
    } else {
      return $this->fail();
    }
  }

  /**
   * 导出客户
   */
  public function export($type) {
    modelCustomer::export($this->user, $type);
  }

  /**
   * 撞单处理
   */
  public function clashPass($id, $operate) {
    $result = modelCustomer::clashPass($this->user, $id, $operate);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 删除客户
   */
  public function remove($id) {
    $result = modelCustomer::remove($this->user, $id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 添加/修改跟进纪要
   */
  public function log($id = 0, $cid = 0) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      if ($id > 0) {
        $data = Log::getById($this->user, $id, 'edit');
        $data->__token__ = $form_token;
        return $this->succeed($data);
      } else {
        return $this->succeed(['__token__' => $form_token]);
      }
    } else {
      $data = input('post.');

      $validate = Validate::make([
        'title'  => 'require',
        'start_time' => 'date'
      ],[
        'title.require' => '必须填写摘要。',
        'start_time.date' => '时间无效。'
      ]);

      if (!$this->checkFormToken($data)) {
        return $this->fail('无效请求，请勿重复提交表单');
      } else if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        unset($data['__token__']);
        if ($id == 0) {
          $data['table'] = 'customer';
        } else if (isset($data['owner_id'])) {
          unset($data['owner_id']);
        }
        Log::addUp($this->user, $id, $data);
        return $this->succeed();
      }
    }
  }

  /**
   * 删除跟进纪要
   */
  public function removeLog($id) {
    $result = Log::remove($this->user, $id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 上传附件
   */
  public function uploadAttach($id, $name = '') {
    $files = request()->file('attach');
    if ($files) {
      $result = File::uploadAttach($this->user, 'customer', $id, $files, $name);
      if ($result >= 1) {
        $attach = File::getList($this->user, 'customer', $id);
        return $this->succeed($attach);
      } else {
        return $this->fail();
      }
    } else {
      return $this->fail('请选择要上传的图片或文件。');
    }
  }

  /**
   * 删除附件
   */
  public function removeAttach($attach_id) {
    $result = File::remove($this->user, $attach_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 添加筛选
   */
  public function addFilter($cid, $bids = '', $uids = '') {
    $result = 0;
    
    if ($bids) {
      $arrIds = explode(',', $bids);
      foreach ($arrIds as $building_id) {
        $result = Filter::addBuilding($this->user, $cid, intval($building_id), 0);
      }
    }
    
    if ($uids) {
      $arrIds = explode(',', $uids);
      foreach ($arrIds as $unit_id) {
        $result += Filter::addBuilding($this->user, $cid, 0, intval($unit_id));
      }
    }
    
    if ($result > 0) {
      $list = Filter::query($this->user, $cid);
      return $this->succeed($list);
    } else {
      return $this->fail();
    }
  }

  /**
   * 删除筛选
   */
  public function removeFilter($id, $building_id, $unit_id) {
    $result = Filter::removeBuilding($this->user, $id, $building_id, $unit_id);
    if ($result == 1) {
      $list = Filter::query($this->user, $id);
      return $this->succeed($list);
    } else {
      return $this->fail();
    }
  }

  /**
   * 移动筛选
   */
  public function sortFilter($id, $building_id, $unit_id, $up = 0) {
    $result = Filter::sort($this->user, $id, $building_id, $unit_id, $up);
    if ($result == 1) {
      $list = Filter::query($this->user, $id);
      return $this->succeed($list);
    } else {
      return $this->fail();
    }
  }

  /**
   * 生成推荐资料
   */
  public function recommend($cid, $mode, $ids) {
    $result = Recommend::addNew($this->user, $cid, $mode, $ids);
    if ($result) {
      $list = Recommend::query($this->user, $cid);
      return $this->succeed($list);
    } else {
      return $this->fail();
    }
  }

  /**
   * 删除资料
   */
  public function removeRecommend($id) {
    $result = Recommend::remove($this->user, $id);
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
    $list = Recommend::detail($this->user, $id);
    return $this->succeed($list);
  }
}