<?php
namespace app\api\model;

use think\model\concern\SoftDelete;
use app\api\model\Base;
use app\api\model\Log;
use app\api\model\User;
use app\api\model\Company;
use app\api\model\Building;
use app\api\model\Customer;

class Confirm extends Base
{
  use SoftDelete;
  protected $pk = 'id';
  protected $deleteTime = 'delete_time';

  /**
   * 权限检查
   */
  public static function allow($user, $confirm, $operate) {
    if ($user == null) {
      return false;
    }

    $superior_id = Company::getSuperior($confirm->company_id, $confirm->user_id);

    if ($operate == 'view') {
      return $confirm->user_id == $user->id ||
        $confirm->builidng_master_id == $user->id ||
        ($confirm->company_id == $user->company_id && $user->id == $superior_id);
    } else if ($operate == 'edit' || $operate = 'delete') {
      return $confirm->user_id == $user->id &&
        $confirm->company_id == $user->company_id;
    } else {
      return false;
    }
  }

  /**
   * 客户确认列表
   */
  public static function query($user, $customer_id, $building_id) {
    $user_id = 0;
    $company_id = 0;
    if ($user) {
      $user_id = $user->id;
      $company_id = $user->company_id;
    }

    $list = self::alias('a')
      ->join('customer c', 'a.customer_id = c.id')
      ->join('building b', 'a.building_id = b.id')
      ->leftJoin('user_company d', 'a.user_id = d.user_id and a.company_id = d.company_id and d.status = 1')
      ->leftJoin('company e', 'b.company_id = e.id');
    if ($customer_id) {
      $list->where('a.customer_id', $customer_id);
      // ->where('(a.user_id = ' . $user_id .
      //   ' OR (a.company_id > 0 AND a.company_id = ' . $company_id .
      //   ' AND d.superior_id = ' . $user_id . '))');
    } else if ($building_id) {
      $list->where('a.building_id', $building_id);
        //->where('(e.user_id = ' . $user_id . ' OR a.user_id = ' . $user_id . ')');
    } else {
      $list->where('a.user_id', $user_id);
    }

    $result = $list->field('a.id,a.create_time,b.building_name,c.customer_name')
      ->order('a.create_time', 'desc')
      ->select();

    foreach($result as $key => $confirm) {
      if ($building_id) {
        $confirm->title = $confirm->customer_name;
      } else {
        $confirm->title = $confirm->building_name;
      }
      $confirm->desc = $confirm->create_time;
    }

    return $result;
  }

  /**
   * 确认书撞单查询
   */
  private static function clashCheck($id, $customer_id, $customer_name, $building_id)
  {
    $keyword = mb_substr(preg_replace(Customer::$IGNORE_WORDS, '', $customer_name), 0, 3, 'utf-8');

    $clashData = self::alias('a')
      ->join('customer c', 'a.customer_id = c.id')
      ->where('a.building_id', $building_id)
      ->where('a.id', '<>', $id)
      ->where("(a.customer_id = " . $customer_id . " OR c.customer_name like '%" . $keyword . "%')")
      ->find();

    return $clashData;
  }

  /**
   * 客户确认书详情
   */
  public static function detail($user, $id, $building_id = 0, $customer_id = 0, $operate = 'view') {
    if ($id) {
      $confirm = self::alias('a')
        ->join('building b', 'a.building_id = b.id')
        ->join('customer c', 'a.customer_id = c.id')
        ->leftJoin('company o', 'a.company_id = o.id')
        ->leftJoin('company m', 'b.company_id = m.id')
        ->join('user u','u.id = a.user_id')
        ->where('a.id', $id)
        ->field('a.id,a.customer_id,a.building_id,a.acreage,a.rent_sell,' .
          'a.confirm_date,a.period,a.rem,a.file,a.user_id,a.company_id,' .
          'b.building_name as building,b.developer,' .
          'c.customer_name as customer,' .
          'o.full_name as company,o.enable_stamp,o.stamp,' .
          'b.company_id as building_company_id,m.full_name as building_company,' .
          'm.enable_stamp as building_enable_stamp,m.stamp as building_stamp,' .
          'u.title as manager,u.avatar,u.mobile,m.user_id as builidng_master_id')
        ->find();
      if ($confirm == null) {
        self::exception('确认书不存在。');
      } else if (!self::allow($user, $confirm, $operate)) {
        self::exception('您没有权限' . ($operate == 'view' ? '查看' : '修改') . '这个确认书。');
      } else {
        if ($confirm->confirm_date && $confirm->period) {
          $confirm->end_date = date('Y-m-d', strtotime('+' . $confirm->period . ' months', strtotime($confirm->confirm_date)));
        }
        if ($confirm->building_company) {
          $confirm->developer = $confirm->building_company;
        }
        if ($operate == 'view') {
          User::formatData($confirm);
          $confirm->allowEdit = self::allow($user, $confirm, 'edit');
          $confirm->allowDelete = self::allow($user, $confirm, 'delete');
        }
        return $confirm;
      }
    } else if ($building_id && $customer_id) {
      $building = Building::getById($user, $building_id);
      if ($building == null) {
        self::exception('项目不存在。');
      }
      $customer = Customer::getById($user, $customer_id);
      if ($customer == null) {
        self::exception('客户不存在。');
      } else if (!Customer::allow($user, $customer, 'confirm')) {
        self::exception('您没有权限添加确认书。');
      }

      if (self::clashCheck(0, $customer->id, $customer->customer_name, $building->id)) {
        self::exception('该确认客户和项目已有客户发生撞单，不能添加。');
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
  public static function addup($user, $id, $data) {
    $user_id = 0;
    if ($user) {
      $user_id = $user->id;
    }

    if ($id) {
      $oldData = self::get($id);
      if ($oldData == null) {
        self::exception('确认书不存在。');
      } else if (!self::allow($user, $oldData, 'edit')) {
        self::exception('您没有权限修改此确认书。');
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
        $confirmData = self::detail($user, $oldData->id);

        $log = [
          "table" => 'customer',
          "owner_id" => $oldData->customer_id,
          "title" => '修改确认书',
          "summary" => $confirmData->building . '\n' .$summary
        ];

        Log::add($user, $log);

        //生成PDF
        $oldData->file = self::toPdf($confirmData);
        $oldData->save();
      }
      return $id;
    } else {
      $building = Building::getById($user, $data['building_id']);
      if ($building == null) {
        self::exception('项目不存在。');
      }
      $customer = Customer::getById($user, $data['customer_id']);
      if ($customer == null) {
        self::exception('客户不存在。');
      } else if (!Customer::allow($user, $customer, 'confirm')) {
        self::exception('您没有权限添加确认书。');
      }
      $company = Company::get($data['company_id']);
      if ($company == null) {
        self::exception('代理方不存在。');
      }

      if (self::clashCheck(0, $customer->id, $customer->customer_name, $building->id)) {
        self::exception('该确认客户和项目已有客户发生撞单，不能添加。');
      }

      $data['user_id'] = $user_id;
      $confirm = new Confirm($data);
      $result = $confirm->save();

      if ($result) {
        $summary = $building->building_name;
        Log::add($user, [
          "table" => "customer",
          "owner_id" => $customer->id,
          "title" => '生成客户确认书',
          "summary" => $summary
        ]);

        // 生成PDF
        $confirmData = self::detail($user, $confirm->id);
        $confirm->file = self::toPdf($confirmData);
        $confirm->save();

        // 更改客户为确认状态
        Customer::changeStatus($user, $customer->id, 3);
        
        return $confirm->id;
      } else {
        self::exception('系统异常。');
      }
    }
  }

  public static function toPdf($data) {
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, true);
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