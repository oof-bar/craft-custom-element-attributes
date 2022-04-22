<?php

namespace modules\customattributes\behaviors;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\events\ElementIndexTableAttributeEvent;
use craft\events\ModelEvent;
use craft\events\SetEagerLoadedElementsEvent;
use craft\events\SetElementTableAttributeHtmlEvent;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;

use yii\base\Behavior;

/**
 * Custom User Attributes Behavior
 * 
 * @property User $owner
 */
class UserBehavior extends Behavior
{
    /**
     * @var int|null Supervisor ID
     */
    public ?int $supervisorId = null;

    /**
     * @var int|null Employee Badge ID
     */
    public ?int $badgeId = null;

    /**
     * @var User|null Memoized supervisor object
     */
    private ?User $_supervisor = null;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Element::EVENT_AFTER_SAVE => 'afterSave',
            Element::EVENT_SET_TABLE_ATTRIBUTE_HTML => 'renderCustomTableAttribute',
            Element::EVENT_PREP_QUERY_FOR_TABLE_ATTRIBUTE => 'prepQueryForCustomTableAttribute',
            Element::EVENT_SET_EAGER_LOADED_ELEMENTS => 'setEagerLoadedElements',
        ];
    }

    /**
     * Saves additional attributes, in response to the parent User element being saved.
     * 
     * {@see Db::upsert()} is used to simplify the process of inserting *or* updating a record. Also notice the reference to `$this->owner` when getting the ID! This refers to the User element the Behavior is attached to.
     * 
     * @param ModelEvent $event
     */
    public function afterSave(ModelEvent $event)
    {
        Db::upsert('{{%customattributes_users}}', [
            'id' => $this->owner->id,
        ], [
            'supervisorId' => $this->supervisorId,
            'badgeId' => $this->badgeId,
        ], [], false);
    }

    /**
     * Provides a change for the Behavior to influence the output of a table attribute.
     */
    public function renderCustomTableAttribute(SetElementTableAttributeHtmlEvent $event)
    {
        if ($event->attribute === 'supervisorId') {
            $supervisor = $this->getSupervisor();

            if ($supervisor) {
                $event->html = Cp::elementHtml($supervisor);
            }
        }

        if ($event->attribute === 'badgeId') {
            if ($this->badgeId) {
                $event->html = Html::a($this->badgeId, $this->getCrmUrl(), [
                    'target' => '_blank',
                ]);
            }
        }
    }

    /**
     * Customize the query being executed for a User element index.
     * 
     * Useful for avoiding N+1 queries by providing extra eager-loading params.
     */
    public function prepQueryForCustomTableAttribute(ElementIndexTableAttributeEvent $event)
    {
        if ($event->attribute === 'supervisorId') {
            $event->query->andWith([
                ['supervisor'],
            ]);
        }
    }

    /**
     * Allows us to memoize any eager-loaded elements that Craft didn't find a place for on the owner.
     * 
     * @param SetEagerLoadedElementsEvent $event
     */
    public function setEagerLoadedElements(SetEagerLoadedElementsEvent $event)
    {
        if ($event->handle === 'supervisor') {
            // Eager-loaded elements will always come back as an array, but we only want one, so the assignment has to be defensive:
            $this->_supervisor = $event->elements[0] ?? null;
        }
    }

    /**
     * Setter for the Supervisor ID property.
     * 
     * A custom setter method is required to normalize and type-cast incoming data. These methods are tried by Yii, before attempting to set the native properties, directly!
     * 
     * @param int|null $id
     */
    public function setSupervisorId(?int $id): void
    {
        $this->supervisorId = $id;
    }

    /**
     * Returns the Supervising User element, if one exists.
     * 
     * @return User|null
     */
    public function getSupervisor(): ?User
    {
        if ($this->_supervisor === null) {
            if ($this->supervisorId === null) {
                return null;
            }

            $this->_supervisor = User::find()->id($this->supervisorId)->one();
        }

        return $this->_supervisor;
    }

    /**
     * Setter for the Badge ID property.
     * 
     * See above for setter method rationale!
     * 
     * @param int $id
     */
    public function setBadgeId(?int $id): void
    {
        $this->badgeId = $id;
    }

    /**
     * Returns a URL to an imaginary CRM that lets CP users navigate directly to an external resource.
     * 
     * @return string
     */
    public function getCrmUrl(): string
    {
        return "https://app.somecrm.com/staff/{$this->badgeId}";
    }
}
