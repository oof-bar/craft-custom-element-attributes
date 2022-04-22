<?php

namespace modules\customattributes\behaviors;

use craft\base\ElementInterface;
use craft\db\QueryAbortedException;
use craft\elements\db\UserQuery;
use craft\helpers\Db;

use yii\base\Behavior;

class UserQueryBehavior extends Behavior
{
    /**
     * @var int|array|null Supervisor ID constraint
     */
    public $supervisor;

    /**
     * @var int/array/null Badge ID constraint
     */
    public $badgeId;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            UserQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
            UserQuery::EVENT_AFTER_PREPARE => 'afterPrepare',
        ];
    }

    /**
     * Hooks into the parent query during preparation and JOINs the custom table, then applies any custom parameters that were configured
     */
    public function beforePrepare()
    {
        // Join our `users` table:
        $this->owner->query->leftJoin('customattributes_users cau', '[[cau.id]] = [[users.id]]');
        $this->owner->subQuery->leftJoin('customattributes_users cau', '[[cau.id]] = [[users.id]]');

        // Select custom columns—Craft will attempt to assign anything defined here to the User element when populating it! Fortunately, your Behavior can also supply properties.
        $this->owner->query->addSelect([
            'cau.supervisorId',
            'cau.badgeId',
        ]);

        if ($this->supervisor !== null) {
            $supervisorParam = $this->_normalizeRelationalParam($this->supervisor);

            $this->owner->subQuery->andWhere(Db::parseParam('cau.supervisorId', $supervisorParam));
        }
    }

    /**
     * Performs any cleanup after the query has been fully prepared, but not yet executed.
     */
    public function afterPrepare()
    {}

    /**
     * Helper function for recursively resolving a relational query parameter.
     * 
     * @param mixed
     * @return mixed
     */
    private function _normalizeRelationalParam($value)
    {
        // Handle special cases first...
        if ($value instanceof ElementInterface) {
            if (is_null($value->id)) {
                throw new QueryAbortedException('Cannot use an element without an ID as a relational constraint.');
            }

            return $value->id;
        } else if (is_array($value)) {
            // Recursively resolve values to IDs:
            return array_map(function ($v) {
                return $this->_normalizeRelationalIdParam($v);
            }, $value);
        }

        // ...then just return the value if it didn't need coercion:
        return $value;
    }
}
