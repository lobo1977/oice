<?php
namespace app\api\controller;

use think\Controller;
use app\common\AppException;
use app\api\model\User;

class Base extends Controller
{
  protected $user = null;
  protected $user_id = 0;
  protected $company_id = 0;

  protected function getUser($update = false) {
    if ($this->user == null || $update) {
      $token = request()->header('User-Token');
      if ($token) {
        $result = User::getUserByToken($token);
        if ($result) {
          $this->user = $result;
          if ($this->user != null) {
            $this->user_id = $this->user->id;
            if ($this->user->company_id) {
              $this->company_id = $this->user->company_id;
            }
          }
        }
      }
    }
    return $this->user;
  }

  protected function setResult($success = false, $message = '', $data = null) {
    return array(
      'success' => $success,
      'message' => $message,
      'data' => $data
    );
  }

  protected function succeed($data = null, $message = '') {
    return $this->setResult(true, $message, $data);
  }

  protected function fail($message = '系统异常。', $data = null) {
    return $this->setResult(false, $message, $data);
  }

  protected function checkAuth() {
    if ($this->getUser() == null) {
      abort(401, '访问的资源未授权，请登录。');
    }
  }

  protected function exception($message) {
    throw new AppException($message);
  }

  /**
   * 表单令牌
   */
  protected function formToken() {
    return $this->request->token('__token__', 'sha1');
  }
}