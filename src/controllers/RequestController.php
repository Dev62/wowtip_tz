<?php

namespace app\controllers;

use Yii;
use app\models\Request;
use app\models\Manager;
use app\models\RequestSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class RequestController extends Controller
{
    public function actionIndex()
    {
        $searchModel = new RequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Request();

        if ($model->load(Yii::$app->request->post())) {

            $previousRequest = Request::getPreviousRequestFromUser($model);

            $manager_id = 0;
            // Если пользователь уже оставлял заявки
            if (!is_null($previousRequest)) {

                $previousManager = Manager::findOne($previousRequest->manager_id);
                if ($previousManager->is_works)
                    $manager_id = $previousManager->id;
            }

            // Если не найден заявок от пользователя не было или менеджер не активен,
            // назначим наименее занятого менеджера
            $manager_id = $manager_id > 0 ? $manager_id : Manager::getManagerWithMinimalRequestsCount()->id;

            if ($manager_id > 0) {
                $model->manager_id = $manager_id;
                if ($model->save()) {
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }

        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = Request::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
