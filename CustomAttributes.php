<?php

namespace modules\customattributes;

use modules\customattributes\behaviors\UserBehavior;
use modules\customattributes\behaviors\UserQueryBehavior;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterElementTableAttributesEvent;

use yii\base\Event;

/**
 * Custom Attributes Module
 * 
 * Injects two custom properties onto the native User element, and attaches some related features that make it easy to work with the data.
 */
class CustomAttributes extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        // Attach User Behavior
        Event::on(
            User::class,
            Model::EVENT_DEFINE_BEHAVIORS,
            function  (DefineBehaviorsEvent $e) {
                $e->behaviors['custom-attributes.user'] = UserBehavior::class;
            });

        // Attach User Query Behavior
        Event::on(
            UserQuery::class,
            Model::EVENT_DEFINE_BEHAVIORS,
            function  (DefineBehaviorsEvent $e) {
                $e->behaviors['custom-attributes.user-query'] = UserQueryBehavior::class;
            });

        // Declare custom element index table attributes—we have to do this outside the Behavior, because the event is emitted statically, not when a behavior is attached.
        Event::on(
            User::class,
            Element::EVENT_REGISTER_TABLE_ATTRIBUTES,
            function (RegisterElementTableAttributesEvent $e) {
                // These attributes don't actually have to match "real" attributes on the element! As long as they match up with a “handled” attribute while rendering the row's HTML (or the value can be safely cast to a string), the names here are inconsequential.
                $e->tableAttributes['supervisorId'] = Craft::t('site', 'Supervisor');
                $e->tableAttributes['badgeId'] = Craft::t('site', 'Badge ID');
            });
    }
}
