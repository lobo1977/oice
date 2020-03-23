<?php
namespace app\index\controller;

use think\Controller;
use app\common\Utils;
use app\common\Wechat;
use app\api\model\Building;
use app\api\model\Unit;
use app\api\model\Recommend as modelRecommend;

define ('K_PATH_IMAGES', $_SERVER['DOCUMENT_ROOT']);

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends \TCPDF {

	//Page header
	public function Header() {
    $this->writeHTML('<table style="width:100%;">' .
    '<tr><td style="text-align:left;">' . $this->header_title . 
    '<br/>' . $this->header_string . 
    '</td><td style="text-align:right;"><img src="' .
      $this->header_logo . '" height="32"></td></tr><table>');
    $this->setY(16);
    $this->SetLineStyle(array(0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');
	}

	// Page footer
	public function Footer() {
    $this->SetY(-15);
    $this->Setx($this->original_lMargin);
    $this->SetLineStyle(array(0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0));
    $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 10, 
      '第' . $this->getAliasNumPage() . '页，共' . $this->getAliasNbPages() . '页', 'T', false, 'R', 0, '', 0);
	}
}

class Recommend extends Controller
{
  /**
   * 打印版
   */
  public function index($id, $mode = 1) {
    set_time_limit(0);
    
    $data = modelRecommend::detail(null, $id);

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

    $title = $data['customer']->customer_name . '_项目推荐';

    $paf_page_ori = 'P';

    if ($mode == 3 || $mode == 4) {
      $paf_page_ori = 'L';
      if ($mode == 4) {
        $title = $data['customer']->customer_name . '_物业对比表';
      }
    }

    $pdf = new MYPDF($paf_page_ori, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, true);
    $fontname = \TCPDF_FONTS::addTTFfont('../extend/fonts/DroidSansFallback.ttf', 'TrueTypeUnicode', '', 32);
    $pdf->SetAuthor(config('app_name'));
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle($title);
    
    $pdf->SetFont($fontname, '', 12);
    $pdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetAutoPageBreak(false, 25);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    if ($paf_page_ori == 'L') {
      $pdf->writeHTML($this->fetch('home_hor'));
    } else {
      $pdf->writeHTML($this->fetch('home'));
    }

    $pdf->setPrintHeader(true);
    $pdf->setHeaderFont(Array($fontname, '', '11'));
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetHeaderData($data['company']->logo, 60, 
      '呈送：' . $data['customer']->customer_name, $data['company']->full_name);

    $pdf->setPrintFooter(true);
    $pdf->setFooterFont(Array($fontname, '', '9'));
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    
    if ($mode == 4) {
      $this->pdfCompare($pdf, $data);
    } else if ($mode == 3) {
      $this->pdfHor($pdf, $data);
    } else if ($mode == 2) {
      $this->pdfB($pdf, $data);
    } else {
      $this->pdfA($pdf, $data);
    }

    ob_clean();
    $pdf->Output($title . '.pdf', 'D');
  }

  private function pdfA($pdf, $data) {
    // if ($data['company']->rem) {
    //   $pdf->AddPage();
    //   $pdf->writeHTML($this->fetch('company'));
    // }
    $pdf->AddPage();
    $pdf->writeHTML($this->fetch('area'));
    $pdf->AddPage();
    $pdf->writeHTML($this->fetch('budget'));
    $pdf->AddPage();
    $pdf->writeHTML($this->fetch('expend'));
    $pdf->AddPage();
    $pdf->writeHTML($this->fetch('process'));

    $pageSize = 15;
    $offset = 0;
    $sliceData = array_slice($data['list'], $offset, $pageSize);

    while(!empty($sliceData)) {
      $pdf->AddPage();
      $pdf->writeHTML($this->fetch('unit_list', ['list' => $sliceData, 'base' => $offset]));
      $offset += $pageSize;
      $sliceData = array_slice($data['list'], $offset, $pageSize);
    }

    foreach($data['list'] as $key=>$building) {
      $pdf->AddPage();
      $pdf->writeHTML($this->fetch('building_a', ['vo' => $building]));
    }
  }

  private function pdfB($pdf, $data) {
    // if ($data['company']->rem) {
    //   $pdf->AddPage();
    //   $pdf->writeHTML($this->fetch('company'));
    // }
    $pdf->AddPage();
    $pdf->writeHTML($this->fetch('area'));
    $pdf->AddPage();
    $pdf->writeHTML($this->fetch('budget'));
    $pdf->AddPage();
    $pdf->writeHTML($this->fetch('expend'));
    $pdf->AddPage();
    $pdf->writeHTML($this->fetch('process'));

    $pageSize = 15;
    $offset = 0;
    $sliceData = array_slice($data['list'], $offset, $pageSize);

    while(!empty($sliceData)) {
      $pdf->AddPage();
      $pdf->writeHTML($this->fetch('unit_list', ['list' => $sliceData, 'base' => $offset]));
      $offset += $pageSize;
      $sliceData = array_slice($data['list'], $offset, $pageSize);
    }

    foreach($data['list'] as $key=>$building) {
      $pdf->AddPage();
      $pdf->writeHTML($this->fetch('building_b', ['vo' => $building]));
    }
  }

  private function pdfHor($pdf, $data) {
    $pdf->AddPage();
    $pdf->writeHTML($this->fetch('unit_list_hor'));

    foreach($data['list'] as $key=>$building) {
      $pdf->AddPage();
      $pdf->writeHTML($this->fetch('building_hor', ['vo' => $building]));
    }
  }

  private function pdfCompare($pdf, $data) {
    foreach($data['list'] as $key=>$building) {
      if ($key % 3 == 0) {
        $building2 = null;
        $building3 = null;
        if (count($data['list']) > $key + 1) {
          $building2 = $data['list'][$key + 1];
        }
        if (count($data['list']) > $key + 2) {
          $building3 = $data['list'][$key + 2];
        }
        $pdf->AddPage();
        $pdf->writeHTML($this->fetch('building_compare', [
          'vo' => $building,
          'vo2' => $building2,
          'vo3' => $building3]));
      }
    }
  }

  /**
   * 项目笔记
   */
  public function building($id) {
    $building = Building::detail(null, $id, 'notes');
    if (Utils::isWechat()) {
      $share = $this->wechatConfig();
      $this->assign('wechat', $share);
    }
    $this->assign('vo', $building);
    if(!$building->images->isEmpty()) {
      $this->assign('share_image', 'https://' . config('app_host') . $building->images[0]['src']);
    } else {
      $this->assign('share_image', '');
    }
    echo $this->fetch();
  }

  /**
   * 单元笔记
   */
  public function unit($id) {
    $unit = Unit::detail(null, $id, 'notes');
    if (Utils::isWechat()) {
      $share = $this->wechatConfig();
      $this->assign('wechat', $share);
    }
    $this->assign('vo', $unit);
    if(!$unit->images->isEmpty()) {
      $this->assign('share_image', 'https://' . config('app_host') . $unit->images[0]['src']);
    } else {
      $this->assign('share_image', '');
    }

    echo $this->fetch();
  }

  /**
   * 微信分享配置
   */
  private function wechatConfig() {
    $wechat = new Wechat();

    $url = htmlspecialchars_decode($_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]);
    $randStr = Utils::getRandChar(32);
    $timestamp = time();
    $signature = $wechat->getJssdkSign($randStr, $timestamp, $url);

    $data = [
      "appId" => config('wechat.app_id'),
      "timestamp" => $timestamp,
      "nonceStr" => $randStr,
      "signature" => $signature
    ];

    return $data;
  }
}