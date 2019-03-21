<?php


class DatabaseSchemaTest extends \Codeception\Test\Unit
{
    public function testModels()
    {
        $modelSchema = (new ObjectModelSchemaBuilder())->getSchema();
        $realSchema = (new InformationSchemaBuilder())->getSchema();

        // ignore tables created by default modules
        $comparator = new DatabaseSchemaComparator([
            'ignoreTables' => [
                'cms_block',
                'cms_block_lang',
                'cms_block_page',
                'cms_block_shop',
                'homeslider',
                'homeslider_slides',
                'homeslider_slides_lang',
                'info',
                'info_lang',
                'layered_category',
                'layered_filter',
                'layered_filter_shop',
                'layered_friendly_url',
                'layered_indexable_attribute_group',
                'layered_indexable_attribute_group_lang_value',
                'layered_indexable_attribute_lang_value',
                'layered_indexable_feature',
                'layered_indexable_feature_lang_value',
                'layered_indexable_feature_value_lang_value',
                'layered_price_index',
                'layered_product_attribute',
                'linksmenutop',
                'linksmenutop_lang',
                'newsletter',
                'pagenotfound',
                'sekeyword',
                'statssearch',
                'themeconfigurator',
            ]
        ]);

        $differences = $comparator->getDifferences($realSchema, $modelSchema);
        $errors = implode("\n", array_map(function($difference) {
            return "  - " . $difference->describe();
        }, $differences));
        if ($errors) {
            self::fail("Database differences:\n$errors\nTotal problems: " . count($differences));
        }
    }
}
