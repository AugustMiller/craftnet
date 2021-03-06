<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craftnet\cms\CmsEdition;
use craftnet\cms\CmsRenewal;
use craftnet\plugins\PluginEdition;
use craftnet\plugins\PluginRenewal;
use yii\console\Exception;

/**
 * m180318_222158_create_license_tables migration.
 */
class m180318_222158_create_license_tables extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->_createCmsTables();
        $this->_createPluginTables();
        $this->_createCmsEditions();
        $this->_createPluginEditions();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180318_222158_create_license_tables cannot be reverted.\n";
        return false;
    }

    private function _createCmsTables()
    {
        // fix plugins table ---------------------------------------------------

        $this->alterColumn('craftnet_plugins', 'price', $this->decimal(14, 4)->unsigned());
        $this->alterColumn('craftnet_plugins', 'renewalPrice', $this->decimal(14, 4)->unsigned());

        // cmseditions ---------------------------------------------------------

        $this->createTable('craftnet_cmseditions', [
            'id' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'price' => $this->decimal(14, 4)->unsigned()->notNull(),
            'renewalPrice' => $this->decimal(14, 4)->unsigned()->notNull(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createIndex(null, 'craftnet_cmseditions', ['name'], true);
        $this->createIndex(null, 'craftnet_cmseditions', ['handle'], true);
        $this->createIndex(null, 'craftnet_cmseditions', ['price']);

        $this->addForeignKey(null, 'craftnet_cmseditions', ['id'], 'elements', ['id'], 'CASCADE');

        // cmsrenewals ---------------------------------------------------------

        $this->createTable('craftnet_cmsrenewals', [
            'id' => $this->integer()->notNull(),
            'editionId' => $this->integer()->notNull(),
            'price' => $this->decimal(14, 4)->unsigned()->notNull(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->addForeignKey(null, 'craftnet_cmsrenewals', ['id'], 'elements', ['id'], 'CASCADE');
        $this->addForeignKey(null, 'craftnet_cmsrenewals', ['editionId'], 'craftnet_cmseditions', ['id'], 'CASCADE');

        // cmslicenses ---------------------------------------------------------

        $this->createTable('craftnet_cmslicenses', [
            'id' => $this->primaryKey(),
            'editionId' => $this->integer()->notNull(),
            'ownerId' => $this->integer()->null(),
            'expirable' => $this->boolean()->notNull(),
            'expired' => $this->boolean()->notNull(),
            'autoRenew' => $this->boolean()->notNull(),
            'edition' => $this->string()->notNull(),
            'email' => $this->string()->notNull(),
            'domain' => $this->string()->null(),
            'key' => $this->string(250)->notNull(),
            'notes' => $this->text()->null(),
            'privateNotes' => $this->text()->null(),
            'lastEdition' => $this->smallInteger()->null(),
            'lastVersion' => $this->string()->null(),
            'lastAllowedVersion' => $this->string()->null(),
            'lastActivityOn' => $this->dateTime()->null(),
            'lastRenewedOn' => $this->dateTime()->null(),
            'expiresOn' => $this->dateTime()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, 'craftnet_cmslicenses', ['key'], true);
        $this->createIndex($this->db->getIndexName('craftnet_cmslicenses', ['ownerId', 'email']), 'craftnet_cmslicenses', ['ownerId', 'lower([[email]])']);

        $this->addForeignKey(null, 'craftnet_cmslicenses', ['editionId'], 'craftnet_cmseditions', ['id']);
        $this->addForeignKey(null, 'craftnet_cmslicenses', ['ownerId'], 'users', ['id'], 'SET NULL');

        // cmslicensehistory ---------------------------------------------------

        $this->createTable('craftnet_cmslicensehistory', [
            'id' => $this->bigPrimaryKey(),
            'licenseId' => $this->integer(),
            'note' => $this->string()->notNull(),
            'timestamp' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(null, 'craftnet_cmslicensehistory', ['licenseId'], 'craftnet_cmslicenses', ['id'], 'CASCADE');

        // cmslicenses_lineitems -----------------------------------------------

        $this->createTable('craftnet_cmslicenses_lineitems', [
            'licenseId' => $this->integer()->notNull(),
            'lineItemId' => $this->integer()->notNull(),
            'PRIMARY KEY([[licenseId]], [[lineItemId]])',
        ]);

        $this->addForeignKey(null, 'craftnet_cmslicenses_lineitems', ['licenseId'], 'craftnet_cmslicenses', ['id'], 'CASCADE');
        $this->addForeignKey(null, 'craftnet_cmslicenses_lineitems', ['lineItemId'], 'commerce_lineitems', ['id'], 'CASCADE');

        // inactivecmslicenses -------------------------------------------------

        $this->createTable('craftnet_inactivecmslicenses', [
            'key' => $this->string(250)->notNull(),
            'data' => $this->text(),
            'PRIMARY KEY([[key]])',
        ]);
    }

    private function _createPluginTables()
    {
        // plugineditions ------------------------------------------------------

        $this->createTable('craftnet_plugineditions', [
            'id' => $this->integer()->notNull(),
            'pluginId' => $this->integer()->notNull(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'price' => $this->decimal(14, 4)->unsigned()->notNull(),
            'renewalPrice' => $this->decimal(14, 4)->unsigned()->notNull(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->createIndex(null, 'craftnet_plugineditions', ['pluginId', 'name'], true);
        $this->createIndex(null, 'craftnet_plugineditions', ['pluginId', 'handle'], true);
        $this->createIndex(null, 'craftnet_plugineditions', ['pluginId', 'price']);

        $this->addForeignKey(null, 'craftnet_plugineditions', ['id'], 'elements', ['id'], 'CASCADE');
        $this->addForeignKey(null, 'craftnet_plugineditions', ['pluginId'], 'craftnet_plugins', ['id'], 'CASCADE');

        // cmsrenewals ---------------------------------------------------------

        $this->createTable('craftnet_pluginrenewals', [
            'id' => $this->integer()->notNull(),
            'pluginId' => $this->integer()->notNull(),
            'editionId' => $this->integer()->notNull(),
            'price' => $this->decimal(14, 4)->unsigned()->notNull(),
            'PRIMARY KEY([[id]])',
        ]);

        $this->addForeignKey(null, 'craftnet_pluginrenewals', ['id'], 'elements', ['id'], 'CASCADE');
        $this->addForeignKey(null, 'craftnet_pluginrenewals', ['pluginId'], 'craftnet_plugins', ['id'], 'CASCADE');
        $this->addForeignKey(null, 'craftnet_pluginrenewals', ['editionId'], 'craftnet_plugineditions', ['id'], 'CASCADE');

        // pluginlicenses ------------------------------------------------------

        $this->createTable('craftnet_pluginlicenses', [
            'id' => $this->primaryKey(),
            'pluginId' => $this->integer()->notNull(),
            'editionId' => $this->integer()->notNull(),
            'cmsLicenseId' => $this->integer()->null(),
            'ownerId' => $this->integer()->null(),
            'expirable' => $this->boolean()->notNull(),
            'expired' => $this->boolean()->notNull(),
            'autoRenew' => $this->boolean()->notNull(),
            'plugin' => $this->string()->notNull(),
            'edition' => $this->string()->notNull(),
            'email' => $this->string()->notNull(),
            'key' => $this->string(24)->notNull(),
            'notes' => $this->text()->null(),
            'privateNotes' => $this->text()->null(),
            'lastVersion' => $this->string()->null(),
            'lastAllowedVersion' => $this->string()->null(),
            'lastActivityOn' => $this->dateTime()->null(),
            'lastRenewedOn' => $this->dateTime()->null(),
            'expiresOn' => $this->dateTime()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, 'craftnet_pluginlicenses', ['key'], true);
        $this->createIndex($this->db->getIndexName('craftnet_pluginlicenses', ['ownerId', 'email']), 'craftnet_pluginlicenses', ['ownerId', 'lower([[email]])']);

        $this->addForeignKey(null, 'craftnet_pluginlicenses', ['pluginId'], 'craftnet_plugins', ['id']);
        $this->addForeignKey(null, 'craftnet_pluginlicenses', ['editionId'], 'craftnet_plugineditions', ['id']);
        $this->addForeignKey(null, 'craftnet_pluginlicenses', ['cmsLicenseId'], 'craftnet_cmslicenses', ['id'], 'SET NULL');
        $this->addForeignKey(null, 'craftnet_pluginlicenses', ['ownerId'], 'users', ['id'], 'SET NULL');

        // pluginlicensehistory ------------------------------------------------

        $this->createTable('craftnet_pluginlicensehistory', [
            'id' => $this->bigPrimaryKey(),
            'licenseId' => $this->integer(),
            'note' => $this->string()->notNull(),
            'timestamp' => $this->dateTime()->notNull(),
        ]);

        $this->addForeignKey(null, 'craftnet_pluginlicensehistory', ['licenseId'], 'craftnet_pluginlicenses', ['id'], 'CASCADE');

        // pluginlicenses_lineitems --------------------------------------------

        $this->createTable('craftnet_pluginlicenses_lineitems', [
            'licenseId' => $this->integer()->notNull(),
            'lineItemId' => $this->integer()->notNull(),
            'PRIMARY KEY([[licenseId]], [[lineItemId]])',
        ]);

        $this->addForeignKey(null, 'craftnet_pluginlicenses_lineitems', ['licenseId'], 'craftnet_pluginlicenses', ['id'], 'CASCADE');
        $this->addForeignKey(null, 'craftnet_pluginlicenses_lineitems', ['lineItemId'], 'commerce_lineitems', ['id'], 'CASCADE');
    }

    private function _createCmsEditions()
    {
        $elementsService = Craft::$app->getElements();

        /** @var CmsEdition[] $editions */
        $editions = [
            new CmsEdition([
                'name' => 'Personal',
                'handle' => 'personal',
                'price' => 0,
                'renewalPrice' => 0,
            ]),
            new CmsEdition([
                'name' => 'Client',
                'handle' => 'client',
                'price' => 199,
                'renewalPrice' => 39,
            ]),
            new CmsEdition([
                'name' => 'Pro',
                'handle' => 'pro',
                'price' => 299,
                'renewalPrice' => 59,
            ]),
        ];

        foreach ($editions as $edition) {
            // Save the edition
            if (!$elementsService->saveElement($edition)) {
                throw new Exception("Couldn't save Craft {$edition->name} edition: ".implode(', ', $edition->getFirstErrors()));
            }

            // Save the renewal
            $renewal = new CmsRenewal([
                'editionId' => $edition->id,
                'price' => $edition->renewalPrice,
            ]);

            if (!$elementsService->saveElement($renewal)) {
                throw new Exception("Couldn't save Craft {$edition->name} renewal: ".implode(', ', $renewal->getFirstErrors()));
            }
        }
    }

    private function _createPluginEditions()
    {
        $elementsService = Craft::$app->getElements();

        $plugins = (new Query())
            ->select(['id', 'name', 'price', 'renewalPrice'])
            ->from('craftnet_plugins')
            ->all();

        foreach ($plugins as $plugin) {
            // Save the edition
            $edition = new PluginEdition([
                'pluginId' => $plugin['id'],
                'name' => 'Standard',
                'handle' => 'standard',
                'price' => $plugin['price'] ?? 0,
                'renewalPrice' => $plugin['renewalPrice'] ?? 0,
            ]);

            if (!$elementsService->saveElement($edition)) {
                throw new Exception("Couldn't save {$plugin['name']} edition: ".implode(', ', $edition->getFirstErrors()));
            }

            // Save the renewal
            $renewal = new PluginRenewal([
                'pluginId' => $plugin['id'],
                'editionId' => $edition->id,
                'price' => $edition->renewalPrice,
            ]);

            if (!$elementsService->saveElement($renewal)) {
                throw new Exception("Couldn't save {$plugin['name']} renewal: ".implode(', ', $renewal->getFirstErrors()));
            }
        }
    }
}
