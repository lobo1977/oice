<?php

namespace app\common;

use PHPExcel_IOFactory;
use PHPExcel;

class Excel {
  private $error;

  /**
   * 构造函数
   */
  function __construct($gatway = '') {
    $this->error = "";
  }

  public function getError() {
    return $this->error;
  }

  public function getData($file) {
    if (!$file) {
      $this->error = '缺少数据文件。';
      return null;
    }

    try {
      $fileName = $file->getInfo('tmp_name');
      $fileType = PHPExcel_IOFactory::identify($fileName);
      $objReader = PHPExcel_IOFactory::createReader($fileType);
      $objExcel = $objReader->load($fileName);
    } catch(Exception $e) {
      $this->error = $e->getMessage();
      return null;
    }

    $sheet = $objExcel->getSheet(0);
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();

    if ($highestRow < 2) {
      $this->error = '导入数据为空。';
      return null;
    }

    return $sheet->rangeToArray('A2:' . $highestColumn . $highestRow, NULL, TRUE, FALSE);
  }

  public function export($title, $headers, $list, $pk = '') {
    $objExcel = new \PHPExcel();
    $c = '';
    $asc = 65;
    $minWidth = 12;

    $sheet = $objExcel->getActiveSheet();

    // 标题
    if ($title) {
      $sheet->setTitle($title);
    }

    $sheet->getStyle('1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    // 填充表头
    if ($headers) {
      foreach($headers as $k => $v) {
        $n = ceil($k/26);
        if ($n > 1) {
          $c = chr($asc + $n - 2);
        }
        $column = $c . chr($asc + ($k % 26));
        $width = strlen($v);
        if ($width < $minWidth) {
          $width = $minWidth; 
        }
        $sheet->setCellValue($column . '1', $v);
        $sheet->getColumnDimension($column)->setWidth($width);
      }
    }

    // 填充数据
    $row = 2;
    $pkv = '';
    foreach ($list as $r => $d) {
      if ($pk == '' || $d[$pk] != $pkv) {
        if (isset($d[$pk])) {
          $pkv = $d[$pk];
        }
        $i = $row + $r;
        $c = '';
        $f = 0;
        foreach($d as $k => $v) {
          if ($k != $pk) {
            $n = ceil($f/26);
            if ($n > 1) {
              $c = chr($asc + $n - 2);
            }
            $sheet->setCellValue($c . chr($asc + ($f % 26)) . $i, $v . '');
            $f++;
          }
        }
      }
    }

    ob_clean();
    header('pragma: public');
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename='. $title .'.xls');
    header('Cache-Control: max-age=0');
    $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
    $objWriter->save('php://output');
  }
}