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

require_once _PS_MODULE_DIR_.'everpsbookstore/models/EverPsBookstoreSeller.php';
require_once _PS_MODULE_DIR_.'everpsbookstore/models/EverPsBookstoreBookImage.php';
require_once _PS_MODULE_DIR_.'everpsbookstore/models/EverPsBookstoreTools.php';

class EverpsbookstoreajaxUploadBookFileModuleFrontController extends ModuleFrontController
{
    private $html;
    private $postErrors = array();
    private $postSuccess = array();

    public function init()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
        $this->isSeven = Tools::version_compare(_PS_VERSION_, '1.7', '>=') ? true : false;
        $this->weight_unit = Configuration::get('PS_WEIGHT_UNIT');
        $this->date_feature_id = Configuration::get('EVERPSBOOKSTORE_DATE_FEATURE');
        $this->condition_feature_id = Configuration::get('EVERPSBOOKSTORE_CONDITION_FEATURE');
        $this->editor_feature_id = Configuration::get('EVERPSBOOKSTORE_EDITOR_FEATURE');
        $this->book_medias = _PS_ROOT_DIR_._MODULE_DIR_.'everpsbookstore/views/uploads/';
        $link = new Link();
        $this->shopLink = $link->getBaseLink((int)Context::getContext()->shop->id);
        $this->text_limit = (int)Configuration::get('PS_PRODUCT_SHORT_DESC_LIMIT');
        parent::init();
    }
    
    protected function l($string, $specific = false, $class = null, $addslashes = false, $htmlentities = true)
    {
        if ($this->isSeven) {
            return Context::getContext()->getTranslator()->trans($string);
        }

        return parent::l($string, $specific, $class, $addslashes, $htmlentities);
    }
    public function initContent()
    {
        $this->ajax = true;

        $link = new Link();
        parent::initContent();
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            Tools::redirect(
                $link->getPageLink('my-account', true)
            );
        }

        $bookstore_seller = new EverPsBookstoreSeller(
            (int)Tools::getValue('id_bookstore_seller')
        );
        if (!Validate::isLoadedObject($bookstore_seller)
            || (int)$bookstore_seller->id <= 0
        ) {
            Tools::redirect(
                $link->getPageLink('my-account', true)
            );
        }
    }

    /**
     * Ajax Process
     */
    public function displayAjax()
    {
        $link = new Link();
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            Tools::redirect(
                $link->getPageLink('my-account', true)
            );
        }
        if (!Tools::getIsset('id_bookstore_seller')
            || !Validate::isUnsignedInt(Tools::getValue('id_bookstore_seller'))
        ) {
            die(Tools::jsonEncode(array(
                'return' => false,
                'error' => $this->module->l('id seller is empty or is not valid.')
            )));
        }

        $bookstore_seller = new EverPsBookstoreSeller(
            (int)Tools::getValue('id_bookstore_seller')
        );
        // First let's see if file exists, regardless of extension
        $type = Tools::strtolower(Tools::substr(strrchr($_FILES['file']['name'], '.'), 1));
        $uniqid = uniqid();
        $seller_filename = $uniqid.'.'.$type;
        $articleImage = new EverPsBookstoreBookImage();
        $articleImage->uniqid = uniqid();
        $articleImage->id_customer = $bookstore_seller->id_customer;
        $articleImage->filename = $seller_filename;
        /* Uploads image */
        $imagesize = @getimagesize($_FILES['file']['tmp_name']);
        if (isset($_FILES['file']) &&
            isset($_FILES['file']['tmp_name']) &&
            !empty($_FILES['file']['tmp_name']) &&
            !empty($imagesize) &&
            in_array(
                Tools::strtolower(Tools::substr(strrchr($imagesize['mime'], '/'), 1)),
                array(
                    'jpg',
                    'gif',
                    'jpeg',
                    'png'
                )
            ) &&
            in_array(
                $type,
                array(
                    'jpg',
                    'gif',
                    'jpeg',
                    'png'
                )
            )
        ) {
            $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
            if ($error = ImageManager::validateUpload($_FILES['file'])) {
                die(Tools::jsonEncode(array(
                    'return' => false,
                    'error' => $error
                )));
            } elseif (!$temp_name
                || !move_uploaded_file($_FILES['file']['tmp_name'], $temp_name)
            ) {
                die(Tools::jsonEncode(array(
                    'return' => false,
                    'error' => $this->module->l('An error occurred during the image upload process.')
                )));
            } elseif (!ImageManager::resize(
                $temp_name,
                _PS_MODULE_DIR_.'everpsbookstore/views/uploads/'.$seller_filename,
                null,
                null,
                $type
            )) {
                die(Tools::jsonEncode(array(
                    'return' => false,
                    'error' => $this->module->l('An error occurred during the image upload process.')
                )));
            }

            if (isset($temp_name)) {
                @unlink($temp_name);
            }
        }

        if ($articleImage->save()) {
            die(Tools::jsonEncode(array(
                'return' => true,
                'message' => _PS_BASE_URL_._MODULE_DIR_.'everpsbookstore/views/uploads/'.$seller_filename,
                'uniqid' => $uniqid,
                'mediaid' => $articleImage->id,
            )));
        } else {
            die(Tools::jsonEncode(array(
                'return' => false,
                'error' => $this->module->l('An error occurred while saving object.')
            )));
        }
    }
}
