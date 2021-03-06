<?php

namespace craftnet\plugins;

use Craft;
use craft\elements\db\ElementQueryInterface;
use craftnet\base\PluginPurchasable;
use yii\base\InvalidConfigException;

/**
 * @property-read PluginEdition $edition
 */
class PluginRenewal extends PluginPurchasable
{
    // Static
    // =========================================================================

    /**
     * @return string
     */
    public static function displayName(): string
    {
        return 'Plugin Renewal';
    }

    /**
     * @return PluginRenewalQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new PluginRenewalQuery(static::class);
    }

    // Properties
    // =========================================================================

    /**
     * @var int The plugin edition ID
     */
    public $editionId;

    /**
     * @var float The renewal price
     */
    public $price;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return 'plugin-renewal';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['editionId', 'price'], 'required'];
        $rules[] = [['editionId'], 'number', 'integerOnly' => true];
        $rules[] = [['price'], 'number'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        $data = [
            'id' => $this->id,
            'pluginId' => $this->pluginId,
            'editionId' => $this->editionId,
            'price' => $this->price,
        ];

        if ($isNew) {
            Craft::$app->getDb()->createCommand()
                ->insert('craftnet_pluginrenewals', $data, false)
                ->execute();
        } else {
            Craft::$app->getDb()->createCommand()
                ->update('craftnet_pluginrenewals', $data, ['id' => $this->id], [], false)
                ->execute();
        }

        parent::afterSave($isNew);
    }

    /**
     * Returns the plugin edition associated with the renewal.
     *
     * @return PluginEdition
     * @throws InvalidConfigException if [[editionId]] is invalid
     */
    public function getEdition(): PluginEdition
    {
        if ($this->editionId === null) {
            throw new InvalidConfigException('Plugin renewal is missing its edition ID');
        }
        if (($edition = PluginEdition::findOne($this->editionId)) === null) {
            throw new InvalidConfigException('Invalid edition ID: ' . $this->editionId);
        };
        return $edition;
    }

    /**
     * @inheritdoc
     */
    public function getIsAvailable(): bool
    {
        return $this->price;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        // todo: include the edition name when we start supporting editions
        return $this->getPlugin()->name . ' Renewal';
    }

    /**
     * @inheritdoc
     */
    public function getPrice(): float
    {
        return (float)$this->price;
    }

    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return $this->getEdition()->getSku() . '-RENEWAL';
    }
}
