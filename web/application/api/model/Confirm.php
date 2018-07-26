<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\User;
use app\api\model\Customer;
use app\api\model\Company;
use app\api\model\Building;
use app\api\model\Unit;
use app\api\model\File;

class Confirm extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  /**
   * 客户确认列表
   */
  public static function query($customer_id, $building_id, $user_id) {
    $list = self::alias('a')
      ->join('customer c', 'a.customer_id = c.id')
      ->leftJoin('unit u', 'a.unit_id = u.id AND a.unit_id > 0')
      ->join('building b', 'a.building_id = b.id OR u.building_id = b.id');

    if ($customer_id) {
      $list->where('a.customer_id', $customer_id);
    }

    if ($building_id) {
      $list->where('a.building_id', $building_id);
    }

    if ($user_id) {
      $list->where('a.user_id', $user_id);
    }

    $result = $list->field('a.*,b.building_name,u.building_no,u.floor,u.room,c.customer_name')
      ->order('a.create_time', 'desc')
      ->select();

    foreach($result as $key => $confirm) {
      if ($building_id) {
        $confirm->title = $confirm->customer_name;
      } else {
        $confirm->title = $confirm->building_name;
        Unit::formatInfo($confirm);
      }
      $confirm->desc = $confirm->create_time;
    }

    return $result;
  }

  /**
   * 客户确认书详情
   */
  public static function detail($id, $building_id, $customer_id, $user_id, $company_id) {
    if ($id) {
      $confirm = self::alias('a')
        ->join('building b', ' a.building_id = b.id')
        ->join('customer c', 'a.customer_id = c.id')
        ->leftJoin('company o', 'a.company_id = o.id')
        ->leftJoin('company m', 'b.company_id = m.id')
        ->where('a.id', $id)
        ->field('a.*,b.building_name as building,b.developer,b.user_id as building_user_id,' .
          'c.customer_name as customer,o.full_name as company,o.enable_stamp,o.stamp,' .
          'm.id as building_company_id,m.full_name as building_company,' .
          'm.enable_stamp as building_enable_stamp,m.stamp as building_stamp')
        ->find();
      if ($confirm) {
        if ($user_id != $confirm->user_id && 
          $confirm->building_user_id != $user_id &&
          $company_id != $confirm->building_company_id) {
          self::exception('您没有权限查看这个确认书。');
        } else {
          if ($confirm->confirm_date && $confirm->period) {
            $confirm->end_date = date('Y-m-d', strtotime('+' . $confirm->period . ' months', strtotime($confirm->confirm_date)));
          }
          if ($confirm->building_company) {
            $confirm->developer = $confirm->building_company;
          }
          return $confirm;
        }
      } else {
        self::exception('确认书不存在。');
      }
    } else if ($building_id && $customer_id) {
      $customer = Customer::get($customer_id);
      if ($customer == null) {
        self::exception('客户不存在。');
      }
      $building = Building::get($building_id);
      if ($building == null) {
        self::exception('项目不存在。');
      }

      if ($building->company_id) {
        $company = Company::get($building->company_id);
        if ($company && $company->full_name) {
          $building->developer = $company->full_name;
        }
      }

      $confirm = new Confirm();
      $confirm->building_id = $building_id;
      $confirm->customer_id = $customer_id;
      $confirm->building = $building->building_name;
      $confirm->developer = $building->developer;
      $confirm->customer = $customer->customer_name;
      $confirm->user_id = $user_id;
      $confirm->rent_sell = '出租';
      $confirm->confirm_date = date("Y-m-d");

      return $confirm;
    } else {
      self::exception('确认书不存在。');
    }
  }
  
  /**
   * 添加/修改客户确认书
   */
  public static function addup($id, $data, $user_id) {
    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('确认书不存在。');
      } else if ($oldData->user_id != $user_id) {
        self::exception('您没有权限查看此确认书。');
      }

      $summary = '';

      if ($data['acreage'] != $oldData->acreage) {
        if ($oldData->acreage) {
          $summary = $summary . '面积：' . $oldData->acreage . '平米 -> ' . $data['acreage'] . '平米\n';
        } else {
          $summary = $summary . '面积：' . $data['acreage'] . '平米\n';
        }
      }

      if ($data['rent_sell'] != $oldData->rent_sell) {
        if ($oldData->rent_sell) {
          $summary = $summary . '租售：' . $oldData->rent_sell . ' -> ' . $data['rent_sell'] . '\n';
        } else {
          $summary = $summary . '租售：' . $data['rent_sell'] . '\n';
        }
      }

      if ($oldData->confirm_date) {
        $oldData->confirm_date = date_format(date_create($oldData->confirm_date), 'Y-m-d');
      }
      if ($data['confirm_date'] != $oldData->confirm_date) {
        if ($oldData->confirm_date) {
          $summary = $summary . '确认日期：' . $oldData->confirm_date . ' -> ' . $data['confirm_date'] . '\n';
        } else {
          $summary = $summary . '确认日期：' . $data['confirm_date'] . '\n';
        }
      }

      if ($data['period'] != $oldData->period) {
        if ($oldData->rent_sell) {
          $summary = $summary . '有效期：' . $oldData->period . '个月 -> ' . $data['period'] . '个月\n';
        } else {
          $summary = $summary . '有效期：' . $data['period'] . '个月\n';
        }
      }

      if ($data['rem'] != $oldData->rem) {
        if ($oldData->rem) {
          $summary = $summary . '备注：' . $oldData->rem . ' -> ' . $data['rem'] . '\n';
        } else {
          $summary = $summary . '备注：' . $data['rem'] . '\n';
        }
      }

      if (isset($data['building_id'])) {
        unset($data['building_id']);
      }

      if (isset($data['customer_id'])) {
        unset($data['customer_id']);
      }

      if (isset($data['user_id'])) {
        unset($data['user_id']);
      }

      $result =  $oldData->save($data);
      if ($result && $summary) {
        $confirmData = self::detail($oldData->id, 0, 0, $user_id, 0);

        $log = [
          "table" => 'customer',
          "owner_id" => $oldData->customer_id,
          "title" => '修改确认书',
          "summary" => $confirmData->building . '\n' .$summary,
          "user_id" => $user_id
        ];

        Log::add($log);

        //生成PDF
        $oldData->file = self::toPdf($confirmData);
        $oldData->save();
      }
      return $id;
    } else {
      $customer = Customer::get($data['customer_id']);
      if ($customer == null) {
        self::exception('客户不存在。');
      }
      $building = Building::get($data['building_id']);
      if ($building == null) {
        self::exception('项目不存在。');
      }
      $company = Company::get($data['company_id']);
      if ($company == null) {
        self::exception('代理方不存在。');
      }

      $data['user_id'] = $user_id;
      $confirm = new Confirm($data);
      $result = $confirm->save();

      if ($result) {
        $summary = $building->building_name;
        Log::add([
          "table" => "customer",
          "owner_id" => $customer->id,
          "title" => '生成客户确认书',
          "summary" => $summary,
          "user_id" => $user_id
        ]);

        //生成PDF
        $confirmData = self::detail($confirm->id, 0, 0, $user_id, 0);
        $confirm->file = self::toPdf($confirmData);
        $confirm->save();

        // 更改客户为确认状态
        Customer::changeStatus($customer->id, 3, $user_id);
        
        return $confirm->id;
      } else {
        self::exception('系统异常。');
      }
    }
  }

  public static function toPdf($data) {
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $fontname = \TCPDF_FONTS::addTTFfont('../extend/fonts/DroidSansFallback.ttf', 'TrueTypeUnicode', '', 32);
    $pdf->SetFont($fontname, '', 14);
    $pdf->SetAuthor(config('app_name'));
    $pdf->SetTitle('客户确认书');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->AddPage();
    $pdf->writeHTML('<h1 style="text-align:center;">客户确认书</h1><p></p>' .
      '<p>委托方：' . $data->developer . '</p>' .
      '<p>电话： <span style="text-decoration:underline">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span> &nbsp; &nbsp;' .
      '传真： <span style="text-decoration:underline">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p>' .
      '<p>代理方：' . $data->company . '</p>' .
      '<p>电话： <span style="text-decoration:underline">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span> &nbsp; &nbsp;' .
      '传真： <span style="text-decoration:underline">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p>' .
      '<p>确认日期：' . date_format(date_create($data->confirm_date), 'Y年m月d日') . '</p><p></p>' . 
      '<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;我公司代理客户' . $data->customer . '到委托方所属物业(' . $data->building . ')考察，所需面积约 ' . $data->acreage . ' 平方米(以最终签约面积为准)。' .
      '此客户系代理方与委托方确认之客户，此客户租赁事宜委托方不得与其它中介机构达成任何协议。' . 
      '双方应积极促成此客户成交，并做好相关的保密工作。上述客户（总公司或其所属子公司）与贵物业成交，' .
      '委托方于收到客户首付款后7个工作日内支付代理方全部佣金，佣金数额为' .
      ($data->rent_sell == '出租' ? '相当于客户壹个月的租金。' : '购房款总额的 1% □ &nbsp;2% □ &nbsp;3% □ &nbsp;。') .
      '</p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;此确认书有效期为' . $data->period . '个月，若洽谈期超过' . $data->period . '个月，则本确认书有效期相应顺延。</p>' .
      '<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;谢谢合作！</p>' . 
      '<p></p><p></p><table style="width:100%"><tr><td><p>委托方：' . $data->developer . '</p><p></p>' .
      '<p>代表签字：<span style="text-decoration:underline">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p></td>' .
      '<td><p>代理方：' . $data->company . '</p><p></p>' .
      '<p>代表签字：<span style="text-decoration:underline">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</span></p></td></tr></table>' ,
        true, false, true, false, '');

    if ($data->building_stamp) {
      $pdf->Image($_SERVER['DOCUMENT_ROOT'] . '/upload/company/images/200/' . $data->building_stamp, 50, 190, 50, 50, '', '', '', false, 300, '', false, false, 0, false, false, false);
    }
    
    if ($data->stamp) {
      $pdf->Image($_SERVER['DOCUMENT_ROOT'] . '/upload/company/images/200/' . $data->stamp, 140, 190, 50, 50, '', '', '', false, 300, '', false, false, 0, false, false, false);
    }

    $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/confirm';
    if (!is_dir($path)) {
      if (!mkdir($path, 0755, true)) {
        self::exception('创建目录失败。');
      }
    }
    $fileName = uniqid($data->id) . '.pdf';
    
    $pdf->Output($path . '/' . $fileName, 'F'); //I：在浏览器中打开，D：下载，F：在服务器生成pdf ，S：只返回pdf的字符串

    return $fileName;
  }
}