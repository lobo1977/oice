<?php
namespace app\index\controller;

use app\api\controller\Base;
use app\api\model\Recommend as modelRecommend;

class Recommend extends Base
{
  /**
   * 打印版
   */
  public function index($id, $mode = 1) {
    $data = modelRecommend::detail($id);

    $process = array(
      ['title' => '形成TEAM', 'step' => array(1)],
      ['title' => '排除法', 'step' => array(1)],
      ['title' => '考察楼盘', 'step' => array(1)],
      ['title' => '确定可选楼盘', 'step' => array(2)],
      ['title' => '确定谈判对象', 'step' => array(2)],
      ['title' => '出意向书', 'step' => array(3)],
      ['title' => '评估意向书', 'step' => array(3)],
      ['title' => '财务分析', 'step' => array(2,3,4)],
      ['title' => '初步洽谈', 'step' => array(2,3,4)],
      ['title' => '确定楼盘', 'step' => array(4)],
      ['title' => '装修方案设计', 'step' => array(2,3,4)],
      ['title' => '准备谈判合同', 'step' => array(3,4)],
      ['title' => '租赁谈判', 'step' => array(3,4)],
      ['title' => '装修方案', 'step' => array(4,5)],
      ['title' => '签约', 'step' => array(4,5)],
      ['title' => '招标', 'step' => array(5,6)],
      ['title' => '装修', 'step' => array(7,8,9,10,11,12,13,14)],
      ['title' => '入住', 'step' => array(15)],
    );

    $this->assign('process', $process);
    $this->assign('data', $data);

    if ($mode == 4) {
      return view('print_compare');
    } else if ($mode == 3) {
      return view('print_hor');
    } else if ($mode == 2) {
      return view('print_b');
    } else {
      return view('print_a');
    }
  }
}