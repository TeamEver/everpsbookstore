<?php
/**
 * 2019-2020 Team Ever
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 *  @author    Team Ever <https://www.team-ever.com/>
 *  @copyright 2019-2020 Team Ever
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class EverPsBookstoreTools extends ObjectModel
{
    public static function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = Tools::strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public static function removeProductFeatures($id_feature, $id_product)
    {
        $sql = 'DELETE FROM '._DB_PREFIX_.'feature_product
        WHERE id_product = '.(int)$id_product.'
        AND id_feature = '.(int)$id_feature;
        return Db::getInstance()->execute($sql);
    }

    public static function replaceProductFeatures($id_feature, $id_product, $id_feature_value)
    {
        if (!$id_feature_value
            || empty($id_feature_value)
            || (int)$id_feature_value <= 0
        ) {
            return;
        }
        $sql = 'REPLACE INTO '._DB_PREFIX_.'feature_product
        VALUES (
            '.(int)$id_feature.',
            '.(int)$id_product.',
            '.(int)$id_feature_value.'
        )';
        return Db::getInstance()->execute($sql);
    }

    public static function replaceProductAccessories($id_product)
    {
        $sql = new DbQuery;
        $sql->select('id_product');
        $sql->from('product');
        $sql->where('id_product != '.(int)$id_product);
        // $sql->where('active = 1');
        $books = Db::getInstance()->executeS($sql);
        foreach ($books as $accessory) {
            $id_accessory = (int)$accessory['id_product'];
            $sql = 'REPLACE INTO '._DB_PREFIX_.'accessory
            VALUES (
                '.(int)$id_product.',
                '.(int)$id_accessory.'
            )';
            if (Db::getInstance()->execute($sql)) {
                $sql = 'REPLACE INTO '._DB_PREFIX_.'accessory
                VALUES (
                    '.(int)$id_accessory.',
                    '.(int)$id_product.'
                )';
                return Db::getInstance()->execute($sql);
            }
        }
    }

    public static function replaceProductCategories($categories, $id_product)
    {
        $return = false;
        foreach ($categories as $id_category) {
            $sql = 'REPLACE INTO '._DB_PREFIX_.'category_product
            VALUES (
                '.(int)$id_category.',
                '.(int)$id_product.',
                '.(int)$id_product.'
            )';
            $return &= Db::getInstance()->execute($sql);
        }
        return $return;
    }

    /**
     * Get feature value if exists, create new one if not
     * @param int id_feature, string value, int id_shop
     * @return obj feature value
    */
    public static function getFeatureValueByValue($id_feature, $value, $id_shop)
    {
        $sql = new DbQuery;
        $sql->select('id_feature_value');
        $sql->from('feature_value_lang');
        $sql->where('value = "'.pSQL($value).'"');
        // $sql->where('active = 1');
        $id_feature_value = (int)Db::getInstance()->getValue($sql);
        if (!$id_feature_value) {
            $feature_value = new FeatureValue();
            $feature_value->id_shop = (int)$id_shop;
            foreach (Language::getLanguages(false) as $lang) {
                $feature_value->value[(int)$lang['id_lang']] = $value;
            }
            $feature_value->id_feature = $id_feature;
            $feature_value->custom = 0;
            $feature_value->save();
        } else {
            $feature_value = new FeatureValue(
                (int)$id_feature_value
            );
        }
        return $feature_value;
    }

    public static function getProductFeatureValue($id_feature, $id_product)
    {
        $sql = new DbQuery;
        $sql->select('id_feature_value');
        $sql->from('feature_product');
        $sql->where('id_feature = '.(int)$id_feature);
        $sql->where('id_product = '.(int)$id_product);
        // $sql->where('active = 1');
        $id_feature_value = (int)Db::getInstance()->getValue($sql);
        if (!$id_feature_value) {
            return false;
        }
        $feature_value = new FeatureValue(
            (int)$id_feature_value,
            (int)Context::getContext()->language->id
        );
        return $feature_value->value;
    }

    public static function autoDisableRedirectBook($id_product, $id_shop)
    {
        $sql = array();
        // Product table : redirect
        $sql[] = 'UPDATE `'._DB_PREFIX_.'product`
        SET `redirect_type` = "301-category"
        WHERE `id_product` = '.(int)$id_product.';';
        // Product table : active
        $sql[] = 'UPDATE `'._DB_PREFIX_.'product`
        SET `active` = 0
        WHERE `id_product` = '.(int)$id_product.';';
        // Product shop table : redirect
        $sql[] = 'UPDATE `'._DB_PREFIX_.'product_shop`
        SET `redirect_type` = "301-category"
        WHERE `id_product` = '.(int)$id_product.'
        AND `id_shop` = '.(int)$id_shop.';';
        // Product shop table : active
        $sql[] = 'UPDATE `'._DB_PREFIX_.'product_shop`
        SET `active` = 0
        WHERE `id_product` = '.(int)$id_product.'
        AND `id_shop` = '.(int)$id_shop.';';
        foreach ($sql as $s) {
            Db::getInstance()->Execute($s);
        }
    }
}
