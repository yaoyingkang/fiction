<?php

namespace console\controllers;

use common\models\Fiction;
use yii\console\Controller;

class FictionController extends Controller
{
    //定时任务，更新所有分类的小说信息，将小说信息缓存
    public function actionUpdateFiction()
    {
        @ini_set('memory_limit', '256M');
        Fiction::updateCategoryFictionList();
    }

    //更新所有小说的章节列表
    public function actionUpdateFictionChapterList()
    {
        @ini_set('memory_limit', '256M');
        $fictions = Fiction::find()->where(['status' => 1])->andWhere(['fictionKey' => null])->orderBy(['id' => SORT_ASC])->limit(25)->all();
        if (count($fictions) > 0) {
            foreach ($fictions as $fiction) {
                if (!$fiction->fictionKey) {
                    $fiction->updateFictionDetail();
                }
            }
        }
    }

    //更新指定小说的章节信息
    public function updateFictionChapterListById($id)
    {
        $fiction = Fiction::findOne($id);
        if (!$fiction->fictionKey) {
            $fiction->updateFictionDetail();
        }
    }
}
