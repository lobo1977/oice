<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\common\Wechat;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\Verify;
use app\api\model\Oauth;
use app\api\model\Company;
use app\api\model\ShortUrl;

class User extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';
  public static $status = ['正常','冻结'];
  public static $role = ['','发展商','代理行','','','','','','','','其他'];

  /**
   * 格式化用户信息
   */
  public static function formatData($user) {
    if ($user == null) return null;
    if (isset($user->company_admin)) {
      $user->isCompanyAdmin = $user->id == $user->company_admin;
      unset($user->company_admin);
    } else {
      $user->isCompanyAdmin = false;
    }
    if (isset($user['company'])) {
      if (empty($user['full_name'])) {
        $user['full_name'] = $user['company'];
      }
    }
    if (isset($user['type'])) {
      $user->isAdmin = $user['type'] == 1;
      $user->isSystem = $user['type'] == 2;
      $user->isLimited = $user['type'] == 10;
    }
    if (isset($user->ori_avatar) && $user->ori_avatar) {
      $find = strpos($user->ori_avatar, 'http');
      if ($find === false || $find > 0) {
        $user->avatar = '/upload/user/images/60/' . $user->ori_avatar;
      }
    } else {
      $user->avatar = '/static/img/avatar.png';
    }
    if (isset($user->logo) && $user->logo) {
      $user->logo = '/upload/company/images/60/' . $user->logo;
    } else {
      $user->logo = '/static/img/null.png';
    }
    return $user;
  }

  /**
   * 获取通讯录（含同事）
   */
  public static function contact($user, $page) {
    $company = db('user_company')->where('user_id', $user->id)->column('company_id');

    $list = self::alias('a')
      ->leftJoin('user_company b', 'a.id = b.user_id and b.status = 1')
      ->leftJoin('company co', 'b.company_id = co.id')
      ->field('a.id,a.title,a.avatar as ori_avatar,a.mobile,b.company_id,co.full_name')
      ->where('b.company_id', 'IN', $company)
      ->whereOr('a.id', 'IN', function ($query) use($user) {
        $query->table('tbl_user_contact')->where('user_id', $user->id)->field('contact_id');
      })
      ->order(['b.active' => 'desc', 'b.company_id' => 'desc']);

    if ($page > 0) {
      $list->page($page, 10);
    }

    $list = $list->select();

    $ids = [];
    $data = [];

    foreach($list as &$member) {
      if (!in_array($member->id, $ids)) {
        $member->is_colleague = in_array($member->company_id, $company);
        self::formatData($member);
        $ids[] = $member->id;
        $data[] = $member;
      }
    }
    
    return $data;
  }

  /**
   * 共享用户列表
   */
  public static function shareList($type, $id, $exclude = null) {
    $list = self::alias('a')
      ->join('share s', 's.user_id = a.id')
      ->leftJoin('user_company b', 'a.id = b.user_id and b.status = 1 and b.active = 1')
      ->leftJoin('company co', 'b.company_id = co.id')
      ->field('a.id,a.title,a.avatar as ori_avatar,a.mobile,co.title as company,s.create_time')
      ->where('s.type', $type)
      ->where('s.object_id', $id);

    if ($exclude) {
      $list->where('a.id', '<>', $exclude);
    }

    $list = $list->order(['s.create_time' => 'desc'])->select();

    foreach($list as &$member) {
      self::formatData($member);
    }
    return $list;
  }

  /**
   * 添加通讯录
   */
  public static function addContact($user, $id) {
    $find = db('user_contact')
      ->where('user_id', $user->id)
      ->where('contact_id', $id)
      ->find();

    if ($find) {
      return true;
    } else {
      $result = db('user_contact')
        ->data([
          'user_id' => $user->id,
          'contact_id' => $id,
          'create_time' => date("Y-m-d H:i:s",time())
        ])->insert();

      return $result;
    }
  }

  /**
   * 移除通讯录
   */
  public static function removeContact($user, $id) {
    $result = db('user_contact')
      ->where('user_id', $user->id)
      ->where('contact_id', $id)
      ->delete();

    return $result;
  }

  /**
   * 获取企业成员
   */
  public static function companyMember($user, $id, $status = 0, $page = 0, $keyword = '') {
    $company = Company::get($id);
    if ($company == null) {
      self::exception('企业不存在。');
    }

    $list = self::alias('a')
      ->join('user_company b', 'a.id = b.user_id and b.status = ' . $status)
      ->where('b.company_id', $id);

    if ($keyword != '') {
      $list->where('a.title|a.mobile', 'like', '%' . $keyword . '%');
    }

    $list->field('a.id,a.title,a.avatar as ori_avatar,a.mobile')
      ->order('b.create_time', 'asc');

    if ($page > 0) {
      $list->page($page, 10);
    }

    $list = $list->select();

    foreach($list as $member) {
      if ($company->user_id == $member->id) {
        $member->isAdmin = true;
      }
      $member->checked = false;
      self::formatData($member);
    }
    return $list;
  }

  /**
   * 查找同事（所有企业）
   */
  public static function colleague($user, $company = 0, $keyword = '', $page = 0) {
    $list = self::alias('a')
      ->join('user_company b', 'a.id = b.user_id and b.status = 1')
      ->join('user_company c', 'b.company_id = c.company_id and c.user_id = ' . $user->id)
      ->leftJoin('company d', 'b.company_id = d.id')
      ->field('a.id,a.title,a.avatar as ori_avatar,a.mobile,b.company_id,d.title as company')
      ->where('a.id', '<>', $user->id);

    if ($company) {
      $list->where('b.company_id', $company);
    }
    
    if ($keyword != '') {
      $list->where('a.title|a.mobile', 'like', '%' . $keyword . '%')
        ->order(['a.title' => 'asc']);
    } else {
      $list->order(['b.company_id' => 'asc', 'b.create_time' => 'desc']);
    }
    
    if ($page > 0) {
      $list->page($page, 10);
    }

    $list = $list->select();

    foreach($list as $member) {
      $member->checked = false;
      self::formatData($member);
    }
    return $list;
  }

  /**
   * 查询工作日报用户
   */
  public static function dailyUser($user, $page = 0, $date = '') {
    $user_id = 0;
    $company_id = 0;

    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    if (!$date) {
      $date = date("Y-m-d", time());
    }

    $list = self::alias('a')
      ->join('user_company b', 'a.id = b.user_id and b.status = 1 and b.company_id = ' . $company_id)
      ->leftJoin('log c', "a.id = c.user_id AND c.company_id = b.company_id AND c.delete_time IS NULL " . 
        "AND c.title <> '登录' AND c.title <> '退出' AND " . 
        "c.start_time between '" . $date . "' AND '" . date("Y-m-d", strtotime($date . ' +1 day')) . "'")
      ->where('b.superior_id = ' . $user_id . ' OR a.id = ' . $user_id);

    $list->field('a.id,a.title,a.avatar as ori_avatar,a.mobile,count(c.id) as daily_count')
      ->group('a.id,a.title,a.avatar,a.mobile')
      ->order('b.create_time', 'asc');

    if ($page > 0) {
      $list->page($page, 10);
    }

    $list = $list->select();

    foreach($list as $member) {
      self::formatData($member);
    }
    return $list;
  }
  
  /**
   * 根据 id 获取用户信息
   */
  public static function getById($id) {
    $data = self::alias('a')
      ->leftJoin('user_company b', 'a.id = b.user_id and b.active = 1 and b.status = 1')
      ->leftJoin('company c', 'b.company_id = c.id')
      ->leftJoin('user u', 'b.superior_id = u.id')
      ->leftJoin('oauth o', "a.id = o.user_id and o.platform = 'wechat'")
      ->where('a.id', $id)
      ->where('a.status', 0)
      ->field('a.id,a.type,a.title,a.role,a.avatar as ori_avatar,a.mobile,a.email,a.qq,a.weixin,
        b.company_id,b.superior_id,u.title as superior,
        c.title as company,c.full_name,c.logo,c.user_id as company_admin,o.openid,o.unionid')
      ->find();
    
    if ($data) {
      $inviteMe = Company::inviteMe($data);
      if ($inviteMe) {
        $data->invite_me = count($inviteMe);
      }
      return self::formatData($data);
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
      $user = self::getById($user->id);
      return self::loginSuccess($user, $account, false);
    }
  }

  /**
   * 验证码登录
   */
  public static function loginByVerifyCode($mobile, $verify_code, $oauth = null) {
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
          $user = self::getById($user->id);
          return self::loginSuccess($user, $mobile, false);
        }
      } else {
        $user = new User();
        $user->mobile = $mobile;
        $user->title = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $mobile);
        $user->salt = substr(md5(strval(time())), 0, 5);
        if ($oauth) {
          if ($oauth->nickname) {
            $user->title = $oauth->nickname;
          }
          if ($oauth->avatar) {
            $user->avatar = $oauth->avatar;
          }
        }
        if ($user->save()) {
          $user = self::getById($user->id);
          // 新用户自动关联项目
          self::bindUser($user);
          return self::loginSuccess($user, $mobile, true);
        } else {
          self::exception('系统异常，请稍后再试。');
        }
      }
    }
  }

  /**
   * 更新 token
   */
  public static function updateToken($user) {
    if ($user == null) {
      self::exception('用户不存在。');
    }
    $newToken = self::genToken($user->id);
    if (empty($user->token)) {
      $result = db('token')->insert($newToken);
    } else {
      $result = db('token')
        ->where('user_id', $user->id)
        ->where('token', $user->token)
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
  public static function updateInfo($user, $data, $avatar) {
    $oldData = self::get($user->id);
    if ($oldData == null) {
      self::exception('用户不存在。');
    } else {
      $summary = '';

      if ($avatar) {
        $path = self::uploadAvatar($avatar);
        if ($path) {
          $data['avatar'] = $path;
          $summary = $summary . '头像：' . $oldData->avatar . ' -> ' . $data['avatar'] . '\n';
        }
      } else if (isset($data['avatar'])) {
        unset($data['avatar']);
      }

      if (isset($data['title']) && $data['title'] != $oldData->title) {
        if ($oldData->title) {
          $summary = $summary . '姓名：' . $oldData->title . ' -> ' . $data['title'] . '\n';
        } else {
          $summary = $summary . '姓名：' . $data['title'] . '\n';
        }
      }

      if (isset($data['role']) && $data['role'] != 0 && $data['role'] != 1 && $data['role'] != 2 && $data['role'] != 10) {
        $data['role'] = 0;
      }

      if (isset($data['role']) && $data['role'] != $oldData->role) {
        if ($oldData->role) {
          $summary = $summary . '行业属性：' . self::$role[$oldData->role] . ' -> ' . self::$role[$data['role']] . '\n';
        } else {
          $summary = $summary . '行业属性：' . self::$role[$data['role']] . '\n';
        }
      }

      if (isset($data['email']) && $data['email'] != $oldData->email) {
        if ($oldData->email) {
          $summary = $summary . '电子邮箱：' . $oldData->email . ' -> ' . $data['email'] . '\n';
        } else {
          $summary = $summary . '电子邮箱：' . $data['email'] . '\n';
        }
      }

      if (isset($data['weixin']) && $data['weixin'] != $oldData->weixin) {
        if ($oldData->weixin) {
          $summary = $summary . '微信：' . $oldData->weixin . ' -> ' . $data['weixin'] . '\n';
        } else {
          $summary = $summary . '微信：' . $data['weixin'] . '\n';
        }
      }

      if (isset($data['qq']) && $data['qq'] != $oldData->qq) {
        if ($oldData->qq) {
          $summary = $summary . 'QQ：' . $oldData->qq . ' -> ' . $data['qq'] . '\n';
        } else {
          $summary = $summary . 'QQ：' . $data['qq'] . '\n';
        }
      }

      $result = $oldData->save($data);

      if ($result) {
        if (isset($data['company'])) {
          $find = db('user_company')
            ->alias('a')
            ->join('Company c', "a.company_id = c.id and (c.title = '" . $data['company'] . "' OR c.full_name = '" . $data['company'] . "')")
            ->where('a.user_id', $user->id)
            ->find();

          if (!$find) {
            $company = new Company([
              'city' => self::$city,
              'user_id' => $user->id,
              'title' => $data['company'],
            ]);
            $result = $company->save();
            if ($result) {
              Company::addin($user, $company->id);
            }
          }
        }

        Log::add($user, [
          "table" => "user",
          "owner_id" => $user->id,
          "title" => '修改账号信息',
          "summary" => $summary
        ]);
      }
      return true;
    }
  }

  /**
   * 修改密码
   */
  public static function changePassword($user, $password) {
    $oldData = self::get($user->id);
    if ($oldData == null) {
      self::exception('用户不存在。');
    } else {
      $oldData->salt = substr(md5(strval(time())), 0, 5);
      $oldData->password = self::genPassword($password, $oldData->salt);
      $result = $oldData->save();
      if ($result) {
        Log::add($user, [
          "table" => "user",
          "owner_id" => $user->id,
          "title" => '修改密码'
        ]);
      }
      return $result;
    }
  }

  /**
   * 更换手机号码
   */
  public static function changeMobile($user, $mobile, $verify_code) {
    $checkResult = Verify::check($mobile, $verify_code);
    if (!$checkResult) {
      self::exception('验证码错误。');
    }

    $oldData = self::get($user->id);
    if ($oldData == null) {
      self::exception('用户不存在。');
    }

    $find = self::where('mobile', $mobile)->find();
    if ($find && $find->id != $user->id) {
      self::exception('该手机号已存在。');
    }

    $oldData->mobile = $mobile;
    if (!$oldData->title) {
      $oldData->title = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $mobile);
    }
    $result = $oldData->save();
    if ($result) {
      $user->mobile = $mobile;
      self::bindUser($user);

      Log::add($user, [
        "table" => "user",
        "owner_id" => $user->id,
        "title" => '绑定手机',
        "summary" => $mobile
      ]);
    }
    return $result;
  }

  /**
   * 退出登录
   */
  public static function logout($user) {
    if ($user) {
      if (db('token')->where('token', $user->token)
        ->where('user_id', $user->id)->delete()) {
        Log::add($user, [
          "table" => "user",
          "owner_id" => $user->id,
          "title" => '退出'
        ]);
      }
    }
    return true;
  }

  /**
   * 登录成功
   */
  public static function loginSuccess($user, $summary, $isReg = false) {
    $token = self::genToken($user->id);
    db('token')->insert($token);
    $user->token = $token["token"];
    $user->expire_time = strtotime($token["expire_time"]);

    Log::add($user, [
      "table" => "user",
      "owner_id" => $user->id,
      "title" => ($isReg ? '注册' : '登录'),
      "summary" => $summary
    ]);

    return $user;
  }

  /**
   * 向用户发送消息
   */
	public static function pushMessage($user_id, $message, $url) {
    $wechat = new Wechat();
    $user = self::get($user_id);

    if ($user == null) {
      self::exception('用户不存在。');
    }

    // 推送微信消息
    $Oauth = Oauth::getInfo($user_id, 'wechat');
    if ($Oauth == null) {
      return false;
    }

    $openid = $Oauth->openid;
		$weixinMsg = urlencode($message);
		
		if ($url) {
			if (!strpos($url, "t.o-ice.com")) {
				$url = ShortUrl::generate($url);
			}
			$weixinMsg = $weixinMsg . $url;
		}

    return $wechat->sendTextMsg($openid, $weixinMsg);
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
      self::exception($avatar->getError());
    }
  }

  /**
   * 根据项目和单元联系人电话绑定项目
   */
  private static function bindUser($user) {
    if (empty($user->mobile)) {
      return;
    }

    $linkman = db('linkman')
      ->where('mobile', $user->mobile)
      ->where('type', 'in', ['building','unit'])
      ->where('status', 0)
      ->where('delete_time', 'null')
      ->field('type,owner_id')
      ->select();

    if (empty($linkman) || count($linkman) == 0) {
      return;
    }

    foreach($linkman as $item) {
      if ($item['type'] == 'building') {
        $building = db('building')
          ->where('id', $item['owner_id'])
          ->where('delete_time', 'null')
          ->find();

        if ($building) {
          // 自动认领
          if (empty($building['user_id'])) {
            db('building')
              ->where('id', $building['id'])
              ->update(['user_id' => $user->id, 'company_id' => $user->company_id]);
          // 添加共享
          } else {
            $share = db('share')
              ->where('type', 'building')
              ->where('user_id', $user->id)
              ->where('object_id', $building['id'])
              ->find();

            if (empty($share)) {
              db('share')->insert([
                'type' => 'building',
                'user_id' => $user->id,
                'object_id' => $building['id'],
                'level' => 1
              ]);
            } else {
              db('share')
              ->where('type', 'building')
              ->where('user_id', $user->id)
              ->where('object_id', $building['id'])
              ->update(['level' => 1]);
            }
          }
        }
      } else {
        $unit = db('unit')
          ->where('id', $item['owner_id'])
          ->where('delete_time', 'null')
          ->find();
        
        if ($unit) {
          // 自动认领
          if (empty($unit['user_id'])) {
            db('unit')
              ->where('id', $unit['id'])
              ->update(['user_id' => $user->id, 'company_id' => $user->company_id]);
          // 添加共享
          } else {
            $share = db('share')
              ->where('type', 'unit')
              ->where('user_id', $user->id)
              ->where('object_id', $unit['id'])
              ->find();

            if (empty($share)) {
              db('share')->insert([
                'type' => 'unit',
                'user_id' => $user->id,
                'object_id' => $unit['id'],
                'level' => 1
              ]);
            } else {
              db('share')
              ->where('type', 'unit')
              ->where('user_id', $user->id)
              ->where('object_id', $unit['id'])
              ->update(['level' => 1]);
            }
          }
        }
      }
    }
  }

  /**
   * 新用户自动创建关联企业
   */
  // private static function CreateCompany($user) {
  //   if (empty($user->mobile)) {
  //     return;
  //   }

  //   $linkman = db('linkman')
  //     ->where('mobile', $user->mobile)
  //     ->where('type', 'building')
  //     ->where('status', 0)
  //     ->where('delete_time', 'null')
  //     ->field('type,owner_id')
  //     ->select();

  //   if (empty($linkman) || count($linkman) == 0) {
  //     return;
  //   }

  //   foreach($linkman as $item) {
  //     $building = db('building')
  //       ->where('id', $item['owner_id'])
  //       ->where('delete_time', 'null')
  //       ->find();

  //     if ($building) {

  //     }
  //   }
  // }
}