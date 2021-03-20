<?php
namespace app\index\controller;

use think\Db;
use think\Controller;

class Index extends Controller
{
  /**
   * 短链接重定向
   */
  public function short($id = '') {
    $url = config('app_host');
    if ($id) {
      $find = db('short_url')
        ->where('id', $id)
        ->where(function ($query) {
          $query->whereOr([['end_time', 'null', ''], ['end_time', '>', date('Y-m-d H:i:s', time())]]);
        })->find();
      if ($find) {
        $url = $find['url'];
      }
    }
    $this->redirect($url, 302);
  }

    public function import($file = 'buildings_rent.txt') {
        set_time_limit(0);

        $dataCount = 0;
        $filePath = '../public/upload/temp/' . $file;
        $open = fopen($filePath, "r") or die("Unable to open file!");
        Db::transaction(function () use($open, &$dataCount) {
            while(!feof($open)) {
                $data = fgets($open);
                if ($data) {
                    $arrData = explode('|', $data);
                    $fieldsCount = count($arrData);
                    if ($fieldsCount >= 11) {
                        $id = Db::name('building')
                            ->where('building_name', $arrData[0])
                            ->value('id');

                        if (!$id) {
                            $unitCount = ($fieldsCount - 8) / 3;
                            for($i = 1; $i <= $unitCount; $i++) {
                                Db::name('unit_tmp')->insert([
                                    'id' => 0,
                                    'building_name' => $arrData[0],
                                    'name' => $arrData[7 + $i * 3 - 2],
                                    'area' => $arrData[7 + $i * 3 - 1],
                                    'price' => $arrData[7 + $i * 3]
                                ]);

                                $dataCount++;
                            }
                        }

                        // $id = Db::name('building_tmp')->insertGetId([
                        //     'name' => $arrData[0],
                        //     'address' => $arrData[1],
                        //     'area' => floatval($arrData[2]),
                        //     'location' => $arrData[3],
                        //     'subway' => $arrData[4],
                        //     'street' => $arrData[5],
                        //     'district' => $arrData[6],
                        //     'count' => intval($arrData[7])
                        // ]);

                        //$dataCount++;

                        // if ($fieldsCount >= 13) {
                        //     $linkmanCount = ($fieldsCount - 8) / 5;
                        //     for($i = 1; $i <= $linkmanCount; $i++) {
                        //         Db::name('linkman_tmp')->insert([
                        //             'building_id' => $id,
                        //             'name' => $arrData[7 + $i * 5 - 4],
                        //             'nickname' => $arrData[7 + $i * 5 - 3],
                        //             'title' => $arrData[7 + $i * 5 - 2],
                        //             'company' => $arrData[7 + $i * 5 - 1],
                        //             'tel' => $arrData[7 + $i * 5]
                        //         ]);
                        //     }
                        // }
                    }
                }
            }
        });
        fclose($open);

        echo $dataCount . '条数据已导入';
    }
}