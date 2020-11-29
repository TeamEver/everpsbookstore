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

class EverPsBookstoreSeller extends ObjectModel
{
    public $id_everpsbookstore_seller;
    public $id_customer;
    public $email;
    public $id_shop;

    public static $definition = array(
        'table' => 'everpsbookstore_seller',
        'primary' => 'id_everpsbookstore_seller',
        'multilang' => false,
        'fields' => array(
            'id_customer' => array(
                'type' => self::TYPE_INT,
                'lang' => false,
                'validate' => 'isunsignedInt',
                'required' => true
            ),
            'email' => array(
                'type' => self::TYPE_STRING,
                'lang' => false,
                'validate' => 'isEmail',
                'required' => true
            ),
            'id_shop' => array(
                'type' => self::TYPE_INT,
                'lang' => false,
                'validate' => 'isunsignedInt',
                'required' => false
            ),
        )
    );

    public static function getSellers($id_shop)
    {
        $sql = new DbQuery;
        $sql->select('id_everpsbookstore_seller');
        $sql->from('everpsbookstore_seller');
        $sql->where('id_shop = '.(int)$id_shop);
        $bookstore_sellers = Db::getInstance()->executeS($sql);
        $sellers = array();
        foreach ($bookstore_sellers as $seller) {
            $sellers[] = new self(
                (int)$seller['id_everpsbookstore_seller']
            );
        }
        return $sellers;
    }

    public static function getBookstoreSellerByCustomerId($id_customer)
    {
        $sql = new DbQuery;
        $sql->select('id_everpsbookstore_seller');
        $sql->from('everpsbookstore_seller');
        $sql->where('id_customer = '.(int)$id_customer);

        $seller_id = Db::getInstance()->getValue($sql);
        return new self((int)$seller_id);
    }

    public static function isBookstoreSeller()
    {
        if (!(int)Context::getContext()->customer->isLogged()) {
            return false;
        }
        $sql = new DbQuery;
        $sql->select('id_everpsbookstore_seller');
        $sql->from('everpsbookstore_seller');
        $sql->where('id_customer = '.(int)Context::getContext()->customer->id);

        $seller_id = Db::getInstance()->getValue($sql);
        if (!$seller_id) {
            return false;
        }
        return true;
    }

    public static function getBookstoreSellerByCustomerEmail($email)
    {
        $sql = new DbQuery;
        $sql->select('id_everpsbookstore_seller');
        $sql->from('everpsbookstore_seller');
        $sql->where('email = '.pSQL($email));

        $seller_id = Db::getInstance()->getValue($sql);
        return new self(
            (int)$seller_id
        );
    }

    public static function cleanBookstoreSellers($id_shop)
    {
        $configuration_sellers = json_decode(
            Configuration::get('EVERPSBOOKSTORE_CUSTOMERS_IDS')
        );
        Db::getInstance()->execute('TRUNCATE TABLE '._DB_PREFIX_.'everpsbookstore_seller');
        foreach ($configuration_sellers as $id_customer) {
            $customer = new Customer(
                (int)$id_customer
            );
            $bookstore_seller = new self();
            $bookstore_seller->id_customer = $customer->id;
            $bookstore_seller->email = $customer->email;
            $bookstore_seller->id_shop = (int)$id_shop;
            $bookstore_seller->save();
        }
    }
}
