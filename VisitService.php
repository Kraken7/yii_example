<?php

namespace app\service\visit;

use Yii;
use yii\db\Exception;

/**
 * Сервис по работе с маршрутами
 */
class VisitService
{
    /**
     * Поставить метку отчета маршрутам
     *
     * @param array $visitsId Массив идентификаторов
     *
     * @return int
     * @throws Exception
     */
    public function setReported(array $visitsId): int
    {
        return Yii::$app->db->createCommand()
            ->update('visit', ['is_reported' => 1], ['in', 'id', $visitsId])
            ->execute();
    }
}