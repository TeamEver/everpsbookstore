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

class EverPsBookstoreBookImage extends ObjectModel
{
    public $id_book_image;
    public $id_customer;
    public $uniqid;
    public $filename;

    public static $definition = array(
        'table' => 'everpsbookstore_image',
        'primary' => 'id_book_image',
        'multilang' => false,
        'fields' => array(
            'id_customer' => array(
                'type' => self::TYPE_INT,
                'lang' => false,
                'validate' => 'isunsignedInt',
                'required' => true
            ),
            'uniqid' => array(
                'type' => self::TYPE_STRING,
                'lang' => false,
                'validate' => 'isString',
                'required' => true
            ),
            'filename' => array(
                'type' => self::TYPE_STRING,
                'lang' => false,
                'validate' => 'isString',
                'required' => true
            ),
        )
    );

    public static function getArticleImagesBySellerId($id_customer)
    {
        $sql = new DbQuery;
        $sql->select('id_book_image');
        $sql->from('everpsbookstore_image');
        $sql->where('id_customer = '.(int)$id_customer);
        $book_images = Db::getInstance()->executeS($sql);
        $booksArray = array();
        foreach ($book_images as $book_image) {
            $image = new self(
                (int)$book_image['id_book_image']
            );
            $booksArray[] = $image;
        }
        return $booksArray;
    }

    public static function getArticleImagesByUniqid($uniqid)
    {
        $sql = new DbQuery;
        $sql->select('id_book_image');
        $sql->from('everpsbookstore_image');
        $sql->where('uniqid = '.pSQL($uniqid));
        $book_image = Db::getInstance()->executeS($sql);
        $image = new self(
            (int)$book_image
        );
        return $image;
    }
}
