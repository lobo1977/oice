<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\User;
use app\api\model\Log;
use app\api\model\DailyReview;

class Daily extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth'
  ];

 /**
  * 工作日报
  */
  public function index($page = 0) {
    $result = User::dailyUser($this->user, $page);
    return $this->succeed($result);
  }

  /**
   * 用户工作日报
   */
  public function user() {
    $params = input('post.');
    $list = Log::daily($this->user, $params);
    return $this->succeed($list);
  }

  /**
   * 查看工作日报
   */
  public function detail($id = 0) {
    if ($id) {
      $data = Log::getById($this->user, $id, 'view');
      return $this->succeed($data);
    }
  }

  /**
   * 添加/修改工作日报
   */
  public function edit($id = 0) {
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
        'title'  => 'require|token',
        'start_time' => 'date',
        'end_time' => 'date'
      ],[
        'title.require' => '必须填写摘要。',
        'title.token' => '无效请求，请勿重复提交。',
        'start_time.date' => '开始时间无效。',
        'end_time.date' => '结束时间无效。'
      ]);

      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        unset($data['__token__']);
        Log::addUp($this->user, $id, $data);
        return $this->succeed();
      }
    }
  }

  /**
   * 删除跟进纪要
   */
  public function remove($id) {
    $result = Log::remove($this->user, $id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 批阅
   */
  public function review($id = 0, $date = '') {
    $list = DailyReview::getList($this->user, $id, $date);
    return $this->succeed($list);
  }

  /**
   * 查看批阅
   */
  public function reviewDetail($id = 0) {
    if ($id) {
      $data = DailyReview::getById($this->user, $id, 'view');
      return $this->succeed($data);
    }
  }

  /**
   * 添加/修改工作日报
   */
  public function editReview($id = 0) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      if ($id > 0) {
        $data = DailyReview::getById($this->user, $id, 'edit');
        $data->__token__ = $form_token;
        return $this->succeed($data);
      } else {
        return $this->succeed(['__token__' => $form_token]);
      }
    } else {
      $data = input('post.');

      $validate = Validate::make([
        'content'  => 'require|token',
        'review_date' => 'date'
      ],[
        'content.require' => '必须填写批阅内容。',
        'content.token' => '无效请求，请勿重复提交。',
        'review_date.date' => '批阅日期无效。'
      ]);

      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        unset($data['__token__']);
        DailyReview::addUp($this->user, $id, $data);
        return $this->succeed();
      }
    }
  }

  /**
   * 删除批阅
   */
  public function removeReview($id) {
    $result = DailyReview::remove($this->user, $id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }
}