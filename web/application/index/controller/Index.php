<?php
namespace app\index\controller;

use think\Db;
use think\Controller;

class Index extends Controller
{
    public function import($file = 'buildings.txt') {
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
                    if ($fieldsCount >= 8) {
                        $id = Db::name('building_tmp')->insertGetId([
                            'name' => $arrData[0],
                            'address' => $arrData[1],
                            'area' => floatval($arrData[2]),
                            'location' => $arrData[3],
                            'subway' => $arrData[4],
                            'street' => $arrData[5],
                            'district' => $arrData[6],
                            'count' => intval($arrData[7])
                        ]);

                        $dataCount++;

                        if ($fieldsCount >= 13) {
                            $linkmanCount = ($fieldsCount - 8) / 5;
                            for($i = 1; $i <= $linkmanCount; $i++) {
                                Db::name('linkman_tmp')->insert([
                                    'building_id' => $id,
                                    'name' => $arrData[7 + $i * 5 - 4],
                                    'nickname' => $arrData[7 + $i * 5 - 3],
                                    'title' => $arrData[7 + $i * 5 - 2],
                                    'company' => $arrData[7 + $i * 5 - 1],
                                    'tel' => $arrData[7 + $i * 5]
                                ]);
                            }
                        }
                    }
                }
            }
        });
        fclose($open);

        echo $dataCount . '条数据已导入';
    }
}