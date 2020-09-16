<?php
namespace app\api\controller;

use app\common\Python;
use app\api\controller\Base;
use app\api\model\Robot as modelRobot;

class Robot extends Base
{
  protected $beforeActionList = [
    'checkAuth'
  ];

  /**
   * 微信登录二维码
   */
  public function qrcode() {
    if ($this->user->type < 10) {
      $ret = Python::ppython("oice::run");
      return $this->succeed($ret);
    } else {
      return $this->fail('对不起，您是受限用户，不能使用机器人。');
    }
  }
    
  /**
   * 在线机器人
   */
  public function online() {
    $robots = modelRobot::online($this->user);
    return $this->succeed($robots);
  }

  /**
   * 机器人联系人/群列表
   */
  public function contact($id = 0) {
    $contact = modelRobot::contact($this->user, $id);
    return $this->succeed($contact);
  }

  /**
   * 推送分享
   */
  public function push($type, $contact, $content, $url = '', $cycle = 0, $start = null, $end = null) {
    if ($type != '0' && $type != '1' && empty($contact)) {
      return $this->fail('请选择要推送的群或联系人。');
    } else {
      $result = modelRobot::push($this->user, $type, $contact, $content, $url, $cycle, $start, $end);
    }
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 离线
   */
  public function offline($id) {
    $result = modelRobot::sendAction($this->user, $id, 0);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 休眠
   */
  public function sleep($id) {
    $result = modelRobot::sendAction($this->user, $id, 2);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 唤醒
   */
  public function weakup($id) {
    $result = modelRobot::sendAction($this->user, $id, 1);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 清除所有任务
   */
  public function clearTask($id) {
    $result = modelRobot::clearTask($this->user, $id);
    if ($result) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }
}