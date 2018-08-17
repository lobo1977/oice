<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\User as modelUser;
use app\api\model\Company;

class User extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' 
  ];

  /**
   * 企业成员列表
   */
  public function companyMember($id, $page) {
    $result = modelUser::companyMember($this->user, $id, 1, $page);
    return $this->succeed($result);
  }

  /**
   * 查看用户信息
   */
  public function detail($id = 0) {
    if ($id) {
      $inSameCompany = false;
      $data = modelUser::getById($id);
      $companyList = Company::my($data);

      if ($companyList != null) {
        foreach($companyList as $company) {
          if ($company->id == $this->user->company_id) {
            $inSameCompany = true;
            break;
          }
        }
      }

      if ($this->user != null) {
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