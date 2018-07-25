<?php
namespace app\api\controller;

use think\Validate;
use app\api\controller\Base;
use app\api\model\Building as modelBuilding;
use app\api\model\Customer;
use app\api\model\Confirm;
use app\api\model\File;

class Building extends Base
{
  protected $beforeActionList = [
    'getUser',
    'checkAuth' => ['except'=>'index,detail,images'],
  ];

  /**
   * 检索房源信息
   */
  public function index() {
    $params = input('post.');
    if ($params) {
      $list = modelBuilding::search($params, $this->user_id, $this->company_id);
      return $this->succeed($list);
    } else {
      return;
    }
  }

  /**
   * 查看房源信息
   */
  public function detail($id = 0) {
    if ($id) {
      $data = modelBuilding::detail($id, $this->user_id, $this->company_id);
      if ($data != null) {
        $data->customer = Customer::search(['status' => '0,1,2', 'clash' => false], $this->user_id, $this->company_id);
      }
      return $this->succeed($data);
    } else {
      return;
    }
  }

  /**
   * 获取图片列表
   */
  public function images($id) {
    $images = File::getList($id, 'building');
    return $this->succeed($images);
  }


  /**
   * 添加/修改房源信息
   */
  public function edit($id) {
    if ($this->request->isGet()) {
      $form_token = $this->formToken();
      $companyList = \app\api\model\Company::my($this->user_id);
      if ($id > 0) {
        $data = modelBuilding::detail($id, $this->user_id, $this->company_id);
        $data->__token__ = $form_token;
        $data->companyList = $companyList;
        return $this->succeed($data);
      } else {
        return $this->succeed([
          "__token__" => $form_token,
          'companyList' => $companyList
        ]);
      }
    } else {
      $validate = Validate::make([
        'building_name'  => 'require|token'
      ],[
        'building_name.require' => '必须填写项目名称。',
        'building_name.token' => '无效请求，请勿重复提交。'
      ]);

      $data = input('post.');
      
      if (!$validate->check($data)) {
        $form_token = $this->formToken();
        return $this->fail($validate->getError(), $form_token);
      } else {
        $form_token = $this->formToken();
        unset($data['__token__']);
        $result = modelBuilding::addUp($id, $data, $this->user_id, $this->company_id);
        if ($result) {
          return $this->succeed($result);
        } else {
          return $this->fail('系统异常。', $form_token);
        }
      }
    }
  }

  /**
   * 添加修改楼盘英文信息
   */
  public function saveEngInfo($id) {
    $validate = Validate::make([
      'name'  => 'token'
    ],[
      'name.token' => '无效请求，请勿重复提交。'
    ]);

    $data = input('post.');

    if (!$validate->check($data)) {
      $form_token = $this->formToken();
      return $this->fail($validate->getError(), $form_token);
    } else {
      $form_token = $this->formToken();
      unset($data['__token__']);
      $result = modelBuilding::addUpEngInfo($id, $data, $this->user_id, $this->company_id);
      if ($result) {
        return $this->succeed($form_token);
      } else {
        return $this->fail('系统异常。', $form_token);
      }
    }
  }

  /**
   * 上传房源图片
   */
  public function uploadImage($id) {
    $files = request()->file('images');
    if ($files) {
      $result = File::uploadImage('building', $id, $files, 
        $this->user_id, $this->company_id);
      if ($result >= 1) {
        $images = File::getList($id, 'building');
        return $this->succeed($images);
      } else {
        return $this->fail();
      }
    } else {
      return $this->fail('请选择要上传的图片。');
    }
  }

  /**
   * 上传单元图片
   */
  public function uploadUnitImage($id) {
    $files = request()->file('images');
    if ($files) {
      $result = File::uploadImage('unit', $id, $files, 
        $this->user_id, $this->company_id);
      if ($result >= 1) {
        $images = File::getList($id, 'unit');
        return $this->succeed($images);
      } else {
        return $this->fail();
      }
    } else {
      return $this->fail('请选择要上传的图片。');
    }
  }

  /**
   * 设置默认图片
   */
  public function setDefaultImage($image_id) {
    $result = File::setDefault($image_id, $this->user_id, $this->company_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 移除图片
   */
  public function removeImage($image_id) {
    $result = File::removeImage($image_id, $this->user_id, $this->company_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 添加到收藏夹
   */
  public function favorite($id) {
    $result = modelBuilding::favorite($id, 0, $this->user_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 从收藏夹删除
   */
  public function unFavorite($id) {
    $result = modelBuilding::unFavorite($id, 0, $this->user_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 批量添加收藏(单元)
   */
  public function batchFavorite($ids) {
    $result = 0;
    foreach ($ids as $id) {
      $result += modelBuilding::favorite(0, $id, $this->user_id);
    }
    if ($result > 0) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 批量移除收藏(单元)
   */
  public function batchUnFavorite($ids) {
    $result = 0;
    foreach ($ids as $id) {
      $arrIds = explode(',', $id);
      if (count($arrIds) == 2) {
        $result += modelBuilding::unFavorite(intval($arrIds[0]), intval($arrIds[1]), $this->user_id);
      }
    }
    if ($result > 0) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 删除项目
   */
  public function remove($id) {
    $result = modelBuilding::remove($id, $this->user_id);
    if ($result == 1) {
      return $this->succeed();
    } else {
      return $this->fail();
    }
  }

  /**
   * 生成客户确认书
   */
  public function addConfirm($cid, $bid, $uid) {
    $result = Confirm::addNew($cid, $bid, $uid, $this->user_id);
    if ($result > 0) {
      $confirm = Confirm::query(0, $bid, $this->user_id);
      return $this->succeed($confirm);
    } else {
      return $this->fail();
    }
  }

  private function cleanImage() {
    $path = '../public/upload/building/images/original';
    $dir = @ dir($path);
    while (($file = $dir->read()) !== false) {
      if ($file != "." && $file != "..") {
        $filePath = "$path/$file";
        if (!is_dir($filePath)) {
          // $file = iconv('GB2312', 'UTF-8', $file);
          $find = File::where('file', $file)->find();
          if ($find) {
            try {
              // $ext = substr($file, -4);
              // $newName = uniqid() . $ext;
              // copy($filePath, "$path/$newName");
              // $find->file = $newName;
              // $find->save();
              // echo $file . ' -> <span style="color:red">' . $newName . '</span><br />';

              // $image = new \SplFileInfo($filePath);
              // File::thumbImage($image, [900, 300], '../public/upload/building/images');
              echo $file . ' -> thumbed!<br />';
            } catch(\Exception $e) {
              // echo $file. ' -> <span style="color:red">rename error! ' . $e->getMessage() . '</span><br />';
              echo $file . ' -> <span style="color:red">thumb error! ' . $e->getMessage() . '</span><br />';
            }
          } else {
            // try {
            //   unlink($filePath);
            //   echo $file . ' -> <span style="color:red">deleted!</span><br />';
            // } catch(\Exception $e) {
            //   echo $file . ' -> <span style="color:red">delete error! ' . $e->getMessage() . '</span><br />';
            // }
          }
        }
        ob_flush();
        flush();
      }
    }
    $dir->close();
  }

  private function cleanImageData() {
    $path = '../public/upload/building/images/original';
    $list = File::where('type', 'building')->order('id', 'asc')->select();
    foreach($list as $key => $file) {
      try {
        $filePath = $path . '/' . $file['file'];
        if (!file_exists($filePath)) {
          echo $file['file'] . ' <span style="color:red">deleted!</span><br />';
          // $file->delete();
        }
      } catch(\Exception $e) {
        echo $file['file'] . ' <span style="color:red">delete error! ' . $e->getMessage() . '</span><br />';
      }
    }
  }
}