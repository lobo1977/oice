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
    $ret = Python::ppython("oice::run");
    return $this->succeed($ret);
  }
    
  /**
   * 在线机器人
   */
  public function online() {
    $robots = modelRobot::online($this->user);
    return $this->succeed($robots);
  }
    
    /**
     * 推送活动信息到群
     * @param number $eid
     */
    // public function PushEvent($eid = 0) {
    // 	parent::checkLogin();
    	
    // 	$this->meta_title = '分享到微信群';
    // 	$this->assign("meta_title", $this->meta_title);
    	
    // 	$groups = $this->robotModel->getGroups($this->getOpenId());
    	
    // 	$this->assign('eid', $eid);
    // 	$this->assign('groups', $groups);

    // 	$this->display();
    // }
    
    /**
     * 分享活动信息到微信群
     * @param unknown $eid
     * @param unknown $groups
     */
    // public function PushPost($eid, $groups) {
    // 	if (empty($this->uid)) {
    // 		$this->error = '操作失败，请先登录。';
    // 	} else if (count($groups) < 1) {
    // 		$this->error = '操作失败，请选择要分享的微信群。';
    // 	} else {
    // 		$url = $this->eventModel->getShortUrl($data['eventid']);
    // 		if (!$this->robotModel->push_event_by_id($eid, $groups, $url)) {
    // 			$this->error = '操作失败，系统错误。';
    // 		}
    // 	}
    			
    // 	if ($this->error) {
    // 		$data['error'] = $this->error;
    // 	} else {
    // 		$data['success'] = '分享成功。';
    // 	}
    				
    // 	$this->ajaxReturn($data, 'json');
    // }
}