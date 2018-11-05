<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\User;
use app\api\model\Log;

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
   * 添加/修改工作日报
   */
  public function log($id = 0) {
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
        'start_time' => 'date'
      ],[
        'title.require' => '必须填写摘要。',
        'title.token' => '无效请求，请勿重复提交。',
        'start_time.date' => '时间无效。'
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
}