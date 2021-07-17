<?php

namespace app\api\controller;

use think\Validate;
use app\api\model\Article as modeArticle;
use app\api\controller\Base;

class Article extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['except' => 'index,detail']
  ];

  /**
   * 检索文章列表信息
   */
  public function index()
  {
    $params = input('post.');
    $params['status'] = 1;
    $list = modeArticle::search($this->user, $params);
    return $this->succeed($list);
  }

  /**
   * 我的文章列表
   */
  public function my()
  {
    $params = input('post.');
    if (!$this->user->isAdmin) {
      $params['user_id'] = $this->user->id;
    }
    $list = modeArticle::search($this->user, $params);
    return $this->succeed($list);
  }

  /**
   * 查看单元信息
   */
  public function detail($id = 0)
  {
    if ($id) {
      $data = modeArticle::detail($this->user, $id, 'view');
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 添加/修改单元信息
   */
  public function edit($id = 0)
  {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      if ($id > 0) {
        $data = modeArticle::detail($this->user, $id, 'edit');
        $data->__token__ = $form_token;
        return $this->succeed($data);
      } else {
        return $this->succeed([
          "__token__" => $form_token
        ]);
      }
    } else {
      $validate = Validate::make([
        'title'  => 'require'
      ], [
        'title.require' => '必须填写标题'
      ]);

      $data = input('post.');
      $cover = request()->file('cover');

      if (!$this->checkFormToken($data)) {
        return $this->fail('无效请求，请勿重复提交表单');
      } else if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        $form_token = $this->formToken();
        unset($data['__token__']);
        $result = modeArticle::addUp($this->user, $id, $data, $cover);
        if ($result) {
          return $this->succeed($result);
        } else {
          return $this->fail('系统异常。', $form_token);
        }
      }
    }
  }

  /**
   * 修改顶置
   */
  public function top($id, $top = 0)
  {
    $result = modeArticle::top($this->user, $id, $top);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 修改状态
   */
  public function changeStatus($id, $status = 0)
  {
    $result = modeArticle::changeStatus($this->user, $id, $status);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 上传图片
   */
  public function upload() {
    $image = request()->file('image');
    $result = modeArticle::upload($this->user, $image);
    if ($result) {
      return $this->succeed($result);
    } else {
      return $this->fail('上传失败');
    }
  }

  /**
   * 删除文章
   */
  public function remove($id)
  {
    $result = modeArticle::remove($this->user, $id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }
}
