<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $created_at
 * @property string $updated_at
 * @property string $email
 * @property string $phone
 * @property string|null $text
 * @property int|null $manager_id
 *
 * @property Manager|null $manager
 */
class Request extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'requests';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    public function rules()
    {
        return [
            [['email', 'phone'], 'required'],
            ['email', 'email'],
            ['manager_id', 'integer'],
            ['manager_id', 'exist', 'targetClass' => Manager::class, 'targetAttribute' => 'id'],
            [['email', 'phone'], 'string', 'max' => 255],
            ['text', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_at' => 'Добавлен',
            'updated_at' => 'Изменен',
            'email' => 'Email',
            'phone' => 'Номер телефона',
            'manager_id' => 'Ответственный менеджер',
            'text' => 'Текст заявки',
        ];
    }

    public function getManager()
    {
        return $this->hasOne(Manager::class, ['id' => 'manager_id']);
    }

    /**
     * Получаем предыдущую по отношению к запрашиваемой заявку
     * @param Request $item
     * @return array|\yii\db\ActiveRecord|null
     */
    public static function getPreviousRequestFromUser(Request $item)
    {
        $mainQuery = Request::find()->alias('main');

        $nestedQuery = Request::find()
            ->alias('nested')
            ->select(['id'])
            ->where(['<', 'DATEDIFF(CURRENT_DATE(), nested.created_at)', 30])
            ->andWhere(['OR', 'main.email = :email', 'main.phone = :phone'], ['email' => $item->email, 'phone' => $item->phone])
            ->andWhere(['OR', 'main.email = nested.email', 'main.phone = nested.phone'])
            ->orderBy(['id' => SORT_DESC])
            ->limit(1);


        // Если заявка не новая, получим предыдущую по отношению к текущей.
        // Иначе получим самую последнюю заявку пользователя
        if ($item->id > 0) {
            $nestedQuery->andWhere(['<', 'nested.id', $item->id]);
        }

        $mainQuery->andWhere(['=', 'main.id', $nestedQuery]);

        return $mainQuery->one();
    }
}
