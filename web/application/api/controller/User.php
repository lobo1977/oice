<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\User as modelUser;
use app\api\model\Company;
use app\api\model\Recommend;

class User extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' 
  ];

  /**
   * 企业成员列表
   */
  public function companyMember($id, $page = 0) {
    $result = modelUser::companyMember($this->user, $id, 1, $page);
    return $this->succeed($result);
  }

  /**
   * 检索同事
   */
  public function search($company = 0, $keyword = '') {
    $result = modelUser::colleague($this->user, $company, $keyword, 1);
    return $this->succeed($result);
  }

  /**
   * 查看用户信息
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelUser::getById($id);

      if (empty($data)) {
        return $this->fail('用户不存在');
      }

      if ($this->user != null && $this->user->id != $id) {
        $data->recommend = Recommend::queryShare($this->user, $id);
        $companyList = Company::my($data);
        $inSameCompany = false;
        $data->in_contact = false;

        // 是否在通讯录中
        $findContact = db('user_contact')
          ->where('user_id', $this->user->id)
          ->where('contact_id', $id)
          ->find();

        if ($findContact) {
          $data->in_contact = true;
        }
        
        // 是否是同事
        if ($companyList != null) {
          foreach($companyList as $company) {
            if ($company->id == $this->user->company_id) {
              $inSameCompany = true;
              break;
            }
          }
        }

        // 是否是上级
        if ($this->user->superior_id == $id) {
          $data->isSuperior = true;
        } else if ($inSameCompany) {
          $data->canSetSuperior = true;
        }
      }

      return $this->succeed($data);
    } else {
      return;
    }
  }
}