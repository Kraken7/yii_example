<?php

namespace app\models\cubes\scores;

use app\models\BaseModel;
use app\models\User;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\ActiveQuery;

/**
 * Поиск по кубу по блокам
 */
class CubeVisitBlockFieldScoreSearch extends BaseModel
{
    /**
     * Данные точек по фильтру
     *
     * @param array $params
     * @param string|null $paramsGroup
     * @param bool $all
     * @param array $selectedFilterItem
     *
     * @return ArrayDataProvider
     */
    public function searchPlaces(array $params, ?string $paramsGroup = null, bool $all = true, array $selectedFilterItem = []): ArrayDataProvider
    {
        $this->load($params);
        if (!$this->validate()) {
            return new ArrayDataProvider(['allModels' => []]);
        }

        $block = $this->getParamValue($params, 'block');
        $field = $this->getParamValue($params, 'field');

        $query = $this->getQuery($params, $paramsGroup);
        $query->andFilterWhere(['in', 'block_title', $block]);
        $query->andFilterWhere(['in', 'field_title', $field]);

        if (!$all) {
            $query->andWhere(['>', static::tableName().'.negative_score_count', 0]);
        }

        if (!empty($selectedFilterItem)) {
            $query->andWhere([CubeFiltersInterface::FIELD_NAMES_TO_COLUMNS[$selectedFilterItem['filterName']]['name'] => $selectedFilterItem['filterValue']]);
        }

        $query->select([
            static::tableName().'.place_id',
            static::tableName().'.place_id as id',
            'visit_id',
            'template.id as template_id',
            'place.filial as filial',
            'place.city as city',
            'place.address as address',
            'count(DISTINCT(visit_id)) as count',
            'sum(negative_score_count) as sum_violations',
            'round(100 * sum(negative_score_count) / sum(filled_score_count)) as percent_violations',
            'round(sum(negative_score_count) / count(DISTINCT(visit_id))) as avg_violations',
            'round(sum(score) / sum(max_score) * 100) as avg_score',
        ]);
        $query->groupBy([static::tableName().'.place_id']);

        return new ArrayDataProvider([
            'allModels' => $query->asArray()->all(),
            'sort' => [
                'attributes' => $this->prepareSortAttributes([
                    'filial', 'city', 'address', 'count', 'sum_violations', 'avg_violations', 'avg_score', 'percent_violations'
                ]),
                'defaultOrder' => [
                    'avg_score' => SORT_ASC
                ]
            ],
            'key' => 'id',
        ]);
    }

    /**
     * Получить запрос с фильтрацией по параметрам
     *
     * @param array $params Параметры поиска
     * @param string|null $paramsGroup Группа параметров
     * @param bool $useSubFilter Использовать фильтр по легенде
     *
     * @return ActiveQuery
     */
    protected function getQuery(
        array $params,
        ?string $paramsGroup = null,
        bool $useSubFilter = true
    ): ActiveQuery
    {
        $query = static::find()
            ->joinWith('place')
            ->joinWith('template')
            ->joinWith('visit');

        /** @var User $currentUser */
        $currentUser = Yii::$app->user->identity;

        $startTime = is_int($this->dateRangeStart) ? $this->dateRangeStart : strtotime($this->dateRangeStart);
        $endTime = is_int($this->dateRangeEnd) ? $this->dateRangeEnd : strtotime($this->dateRangeEnd);

        $query->andWhere([static::tableName().'.account_id' => $currentUser->account_id])
            ->andFilterWhere(['>=', static::tableName().'.finished_at', gmdate('Y-m-d 00:00:00', $startTime)])
            ->andFilterWhere(['<=', static::tableName().'.finished_at', gmdate('Y-m-d 23:59:59', $endTime)]);

        $this->applyFilters($query, $params, $paramsGroup, $useSubFilter);

        return $query;
    }
}