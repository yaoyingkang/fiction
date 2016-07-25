<?php

namespace frontend\controllers;

use common\models\Fiction;
use Goutte\Client;
use yii\base\Exception;
use yii\helpers\Html;
use Yii;

class FicController extends BaseController
{
    public function actionIndex($id)
    {
        $fiction = Fiction::findOne($id);
        if (!$fiction) {
            $this->err404('没有找到指定小说');
        }
        if (!$fiction->author || !$fiction->description || !$fiction->fictionKey) {
            $fiction = $fiction->getFunctionDetail();
        }
        if (!$fiction->author || !$fiction->description || !$fiction->fictionKey) {
            //todo 记录日志 获取指定小说信息失败
        }
        $chapterList = $fiction->getChapterList();
        return $this->render('index', [
            'fiction' => $fiction,
            'chapterList' => $chapterList,
        ]);
    }
    //小说章节目录页
    public function actionList()
    {
        $dk = $this->get('dk');
        $dk = $dk ?: $this->ditch_key;
        $url = base64_decode($this->get('url'));
        $fictionDetail = Category::getFictionDetail($url, $dk);
        if ($fictionDetail) {
            return $this->render('list', [
                'fictionDetail' => $fictionDetail,
                'dk' => $dk,
            ]);
        } else {
            $this->err404();
        }
    }

    //小说详情页
    public function actionDetail()
    {
        $dk = $this->get('dk');
        $dk = $dk ?: $this->ditch_key;
        $fk = $this->get('fk');
        $url = base64_decode($this->get('url'));//解密url
        $data = Fiction::getFictionTitleAndNum($dk, $fk, $url);
        $current = $data['current'];
        $chapterName = $data['title'];
        $fiction = Fiction::getFiction($dk, $fk);
        if ($fiction) {
            $cache = Yii::$app->cache;
            $fictionDetail = $cache->get('ditch_'.$dk.'_fiction_'.$fk.'_url_'.$url.'_detail');
            if (!$fictionDetail) {
                $client = new Client();
                $crawler = $client->request('GET', $url);
                $content = '';
                try {
                    if ($crawler) {
                        $detail = $crawler->filter(Yii::$app->params['ditch'][$dk]['fiction_rule']['fiction_detail_rule']['fiction_detail_rule']);
                        if ($detail) {
                            global $content;
                            $detail->each(function ($node) use ($content) {
                                global $content;
                                $text = $node->html();
                                $text = preg_replace('/<script.*?>.*?<\/script>/', '', $text);
                                $text = preg_replace('/(<br\s?\/?>){1,}/', '<br/><br/>', $text);
                                $text = strip_tags($text, '<p><div><br>');
                                $content = $content.$text;
                            });
                        }
                    }
                } catch (Exception $e) {
                    //todo 处理查找失败
                }
                if ($content) {
                    $cache->set('ditch_'.$dk.'_fiction_'.$fk.'_url_'.$url.'_detail', $content, Yii::$app->params['fiction_chapter_detail']);
                }
            } else {
                $content = $fictionDetail;
            }

            $content = isset($content) ? $content : '未获取到指定章节';

            return $this->render('detail', [
                'content' => $content,
                'fiction' => $fiction,
                'chapterName' => $chapterName,
                'dk' => $dk,
                'fk' => $fk,
                'url' => $url,
                'current' => $current,
            ]);
        } else {
            $this->err404('页面未找到');
        }
    }

    //ajax获取上一章、下一章
    public function actionPn()
    {
        $dk = $this->get('dk');
        $fk = $this->get('fk');
        $url = base64_decode($this->get('url'));
        if (Yii::$app->request->isAjax) {
            $res = Fiction::getPrevAndNext($dk, $fk, $url);

            return $res;
        } else {
            $this->err404();
        }
    }
}
