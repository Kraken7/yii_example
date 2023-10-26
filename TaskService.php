<?php

namespace app\service\task;

use app\models\task\Task;
use Yii;
use yii\db\Exception;

/**
 * Сервис по работе с задачами
 */
class TaskService
{
    /**
     * Пометить просроченные задачи
     *
     * @return int
     * @throws Exception
     */
    public function expires(): int
    {
        return Yii::$app->db->createCommand("UPDATE task SET state = :new_state WHERE state IN (:state_progress, :state_rejected, :state_reported) AND deadline_date <= :date")
            ->bindValue(':state_progress', Task::STATE_IN_PROGRESS)
            ->bindValue(':state_rejected', Task::STATE_REJECTED)
            ->bindValue(':state_reported', Task::STATE_REPORTED)
            ->bindValue(':new_state', Task::STATE_EXPIRED)
            ->bindValue(':date', date('Y-m-d'))
            ->execute();
    }
}