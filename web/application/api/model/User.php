<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\Verify;

class User extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  /**
   * 格式化用户信息
   */
  public static function formatUserInfo($user) {
    if ($user == null) return null;
    if ($user->avatar) {
      $user->avatar = '/upload/user/images/60/' . $user->avatar;
    } else {
      $user->avatar = '/static/img/avatar.png';
    }
    return $user;
  }
  
  /**
   * 根据 id 获取用户信息
   */
  public static function getById($user_id) {
    $data = self::alias('a')
      ->leftJoin('user_company b', 'a.id = b.user_id and b.default = 1 and b.status = 1')
      ->leftJoin('company c', 'b.company_id = c.id')
      ->where('a.id', $user_id)
      ->where('a.status', 0)
      ->field('a.id,a.type,a.title,a.avatar,a.mobile,a.email,a.qq,a.weixin,b.company_id,c.title as company,c.logo')
      ->find();
    
    if ($data) {
      return self::formatUserInfo($data);
    } else {
      return null;
    }
  }

  /**
   * 根据 token 获取用户信息
   */
  public static function getUserByToken($token) {
    $token = db('token')
      ->where('token', $token)
      ->whereTime('expire_time', '>=', time())
      ->find();
    
    if ($token != null) {
      $user = self::getById($token['user_id']);
      if ($user != null) {
        $user->token = $token["token"];
        $user->expire_time = strtotime($token["expire_time"]);
        return $user;
      }
    }

    return null;
  }

  /**
   * 账号密码登录
   */
  public static function loginByPassword($account, $password, $vcode) {
    $user = self::where('mobile|username|email', $account)->find();

    if ($user == null) {
      self::exception('账号不存在或密码错误。');
    } else if ($user->status > 0) {
      $user->setInc('login_fail');
      self::exception('账号已被冻结，禁止登录。');
    } else if ($user->login_fail > 1 && !captcha_check($vcode)) {
      $user->setInc('login_fail');
      self::exception('验证码错误。');
    } else if ($user->password != self::genPassword($password, $user->salt)) {
      $user->setInc('login_fail');
      self::exception('账号不存在或密码错误。');
    } else {
      $user->login_fail = 0;
      $user->save();
      return self::loginSuccess($user->id, $account, false);
    }
  }

  /**
   * 验证码登录
   */
  public static function loginByVerifyCode($mobile, $verify_code) {
    $checkResult = Verify::check($mobile, $verify_code);
    
    if (!$checkResult) {
      self::exception('验证码错误。');
    } else {
      $user = self::where('mobile', $mobile)->find();
      if ($user != null) {
        if ($user->status > 0) {
          $user->setInc('login_fail');
          self::exception('账号已被冻结，禁止登录。');
        } else {
          $user->login_fail = 0;
          $user->save();
          return self::loginSuccess($user->id, $mobile, false);
        }
      } else {
        $user = new User();
        $user->mobile = $mobile;
        $user->salt = substr(md5(strval(time())), 0, 5);
        if ($user->save()) {
          return self::loginSuccess($user->id, $mobile, true);
        } else {
          self::exception('系统异常，请稍后再试。');
        }
      }
    }
  }

  /**
   * 更新 token
   */
  public static function updateToken($id, $token = null) {
    $newToken = self::genToken($id);
    if ($token == null) {
      $result = db('token')->insert($newToken);
    } else {
      $result = db('token')
        ->where('user_id', $id)
        ->where('token', $token)
        ->update($newToken);
    }
    if ($result) {
      $newToken['expire_time'] = strtotime($newToken['expire_time']);
      return $newToken;
    } else {
      return false;
    }
  }

  /**
   * 更新用户信息
   */
  public static function updateInfo($data, $avatar, $user_id) {
    $user = self::get($user_id);
    if ($user == null) {
      self::exception('账号不存在。');
    } else {
      $summary = '';

      if ($avatar) {
        $path = self::uploadAvatar($avatar);
        if ($path) {
          $data['avatar'] = $path;
          $summary = $summary . '头像：' . $user->avatar . ' -> ' . $data['avatar'] . '\n';
        }
      }

      if ($data['title'] != $user->title) {
        if ($user->title) {
          $summary = '姓名：' . $user->title . ' -> ' . $data['title'] . '\n';
        } else {
          $summary = '姓名：' . $data['title'] . '\n';
        }
      }

      if ($data['email'] != $user->email) {
        if ($user->email) {
          $summary = '电子邮箱：' . $user->email . ' -> ' . $data['email'] . '\n';
        } else {
          $summary = '电子邮箱：' . $data['email'] . '\n';
        }
      }

      if ($data['weixin'] != $user->weixin) {
        if ($user->weixin) {
          $summary = '微信：' . $user->weixin . ' -> ' . $data['weixin'] . '\n';
        } else {
          $summary = '微信：' . $data['weixin'] . '\n';
        }
      }

      if ($data['qq'] != $user->qq) {
        if ($user->qq) {
          $summary = 'QQ：' . $user->qq . ' -> ' . $data['qq'] . '\n';
        } else {
          $summary = 'QQ：' . $data['qq'] . '\n';
        }
      }

      $result = $user->save($data);
      if ($result) {
        Log::add([
          "table" => "user",
          "owner_id" => $user_id,
          "title" => '修改账号信息',
          "summay" => $summary,
          "user_id" => $user_id
        ]);
      }
      return true;
    }
  }

  /**
   * 修改密码
   */
  public static function changePassword($user_id, $password) {
    $user = self::get($user_id);
    if ($user == null) {
      self::exception('账号不存在。');
    } else {
      $user->password = self::genPassword($password, $user->salt);
      $result = $user->save();
      if ($result) {
        Log::add([
          "table" => "user",
          "owner_id" => $user_id,
          "title" => '修改密码',
          "user_id" => $user_id
        ]);
      }
      return $result;
    }
  }

  /**
   * 更换手机号码
   */
  public static function changeMobile($user_id, $mobile, $verify_code) {
    $checkResult = Verify::check($mobile, $verify_code);
    if (!$checkResult) {
      self::exception('验证码错误。');
    }

    $find = self::where('mobile', $mobile)->find();
    if ($find && $find->id != $user_id) {
      self::exception('该手机号已被占用。');
    }

    $user = self::get($user_id);
    if ($user == null) {
      self::exception('账号不存在。');
    }

    $user->mobile = $mobile;
    $result = $user->save();
    if ($result) {
      Log::add([
        "table" => "user",
        "owner_id" => $user_id,
        "title" => '绑定手机',
        "summary" => $mobile,
        "user_id" => $user_id
      ]);
    }
    return $result;
  }

  /**
   * 退出登录
   */
  public static function logout($token) {
    $user = self::getUserByToken($token);

    if ($user) {
      if (db('token')->where('token', $token)
        ->where('user_id', $user->id)->delete()) {
        Log::add([
          "table" => "user",
          "owner_id" => $user->id,
          "title" => '退出',
          "user_id" => $user->id
        ]);
      }
    }

    return true;
  }

  /**
   * 登录成功
   */
  public static function loginSuccess($user_id, $summary, $isReg = false) {
    $log = [
      "table" => "user",
      "owner_id" => $user_id,
      "title" => '登录',
      "summary" => $summary,
      "user_id" => $user_id
    ];

    if ($isReg) {
      $log['title'] = '注册';
    }
    
    $token = self::genToken($user_id);
    db('token')->insert($token);

    Log::add($log);

    $user = self::getById($user_id);
    $user->token = $token["token"];
    $user->expire_time = strtotime($token["expire_time"]);

    return $user;
  }

  /**
   * 生成密码
   */
  private static function genPassword($password, $salt) {
    return md5($password . $salt);
  }

  /**
   * 生成令牌(有效期3天)
   */
  private static function genToken($user_id) {
    $time = time();
    return array(
      'user_id' => $user_id,
      'token'=> md5(strval($user_id) . strval($time)), 
      'expire_time'=> date('Y-m-d H:i:s', $time + 259200)
    );
  }

  /**
   * 上传头像
   */
  private static function uploadAvatar($avatar) {
    $uploadPath = '../public/upload/user/images';
    $info = $avatar->validate(['size'=>2097152,'ext'=>'jpg,jpeg,png,gif'])
      ->rule('uniqid')->move($uploadPath . '/original');

    if ($info) {
      File::thumbImage($info, [60,200], $uploadPath);
      return $info->getFilename();
    } else {
      return false;
    }
  }
}