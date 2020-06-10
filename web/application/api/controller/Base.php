<?php
namespace app\api\controller;

use think\facade\Cache;
use think\facade\Validate;
use think\Controller;
use app\common\AppException;
use app\api\model\User;

class Base extends Controller
{
  protected $user = null;
  protected $user_token = '';
  protected $user_id = 0;
  protected $company_id = 0;

  protected function getUser($update = false) {
    if ($this->user == null || $update) {
      if (input('?user-token')) {
        $this->user_token = input('user-token');
      } else {
        $this->user_token = request()->header('User-Token');
      }
      if ($this->user_token) {
        $result = User::getUserByToken($this->user_token);
        if ($result) {
          $this->user = $result;
          if ($this->user != null) {
            $this->user_id = $this->user->id;
            if ($this->user->company_id) {
              $this->company_id = $this->user->company_id;
            } else {
              $this->user->company_id = 0;
            }
          }
        }
      }
    }
    return $this->user;
  }

  protected function setResult($success = false, $message = '', $data = null) {
    ob_clean();
    return array(
      'success' => $success,
      'message' => $message,
      'data' => $data
    );
  }

  protected function succeed($data = null, $message = '') {
    return $this->setResult(true, $message, $data);
  }

  protected function fail($message = '', $data = null) {
    if ($message == '') {
      $message = '系统异常。';
    }
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
    $form_token = $this->request->token('__token__', 'sha1');
    Cache::set($this->user_token, $form_token, 600);
    return $form_token;
  }

  /**
   * 验证表单令牌
   */
  protected function checkFormToken($data) {
    if (!Validate::token(null, '__token__', $data)) {
      if (Cache::get($this->user_token) == $data['__token__']) {
        Cache::rm($this->user_token);
        return true;
      } else {
        Cache::rm($this->user_token);
        return false;
      }
    }
    Cache::rm($this->user_token);
    return true;
  }
}