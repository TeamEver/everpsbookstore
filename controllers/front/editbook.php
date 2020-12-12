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

class EverpsbookstoreEditbookModuleFrontController extends ModuleFrontController
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
        parent::initContent();
        $cart = $this->context->cart;
        $id_shop = (int)Context::getContext()->shop->id;
        $link = new Link();

        if (!(bool)Context::getContext()->customer->isLogged()) {
            Tools::redirect(
                $link->getPageLink('authentication', true)
            );
        }

        if ($cart->id_customer == 0
            || !$this->module->active) {
            Tools::redirect(
                $link->getPageLink('my-account', true)
            );
        }
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            Tools::redirect(
                $link->getPageLink('my-account', true)
            );
        }
        $bookstore_seller = EverPsBookstoreSeller::getBookstoreSellerByCustomerId(
            (int)Context::getContext()->customer->id
        );
        if (!Validate::isLoadedObject($bookstore_seller)
            || (int)$bookstore_seller->id <= 0
        ) {
            Tools::redirect(
                $link->getPageLink('my-account', true)
            );
        }
        $categories = Category::getAllCategoriesName(
            null,
            (int)Context::getContext()->language->id
        );
        $manufacturers = Manufacturer::getManufacturers(
            false,
            (int)(int)Context::getContext()->language->id
        );
        $product = new Product(
            (int)Tools::getValue('id_book'),
            false,
            (int)Context::getContext()->language->id,
            (int)Context::getContext()->shop->id
        );
        $author = new Manufacturer(
            (int)$product->id_manufacturer
        );
        $product->author = $author->name;
        $product->quantity = StockAvailable::getQuantityAvailableByProduct(
            (int)$product->id
        );
        $product->categories = $product->getCategories();
        // Todo
        $product->book_date = EverPsBookstoreTools::getProductFeatureValue(
            (int)$this->date_feature_id,
            (int)$product->id
        );
        $product->book_condition = EverPsBookstoreTools::getProductFeatureValue(
            (int)$this->condition_feature_id,
            (int)$product->id
        );
        $product->book_editor = EverPsBookstoreTools::getProductFeatureValue(
            (int)$this->editor_feature_id,
            (int)$product->id
        );
        $conditions = FeatureValue::getFeatureValuesWithLang(
            (int)Context::getContext()->language->id,
            $this->condition_feature_id
        );
        $loadinggif = _PS_BASE_URL_
        ._MODULE_DIR_
        .'everpsbookstore/views/img/ajax-loader.gif';
        $this->context->smarty->assign(array(
            'product' => $product,
            'errors' => $this->postErrors,
            'successes' => $this->postSuccess,
            'loadinggif' => $loadinggif,
            'categories' => $categories,
            'conditions' => $conditions,
            'bookstore_seller' => $bookstore_seller,
            'manufacturers' => $manufacturers,
            'weight_unit' => $this->weight_unit,
            'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, (int)$id_shop),
            'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, (int)$id_shop),
        ));
        $this->setTemplate('module:everpsbookstore/views/templates/front/editbook.tpl');
    }

    public function postProcess()
    {
        $link = new Link();
        if ((bool)EverPsBookstoreSeller::isBookstoreSeller() === false) {
            Tools::redirect(
                $link->getPageLink('my-account', true)
            );
        }
        if (Tools::isSubmit('evereditbooksubmit')) {
            if (!Tools::getValue('id_bookstore_seller')
                || !Validate::isUnsignedInt(Tools::getValue('id_bookstore_seller'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [ID seller] is not valid'
                );
            }
            // Secure also on submit AND getting customer id
            $bookstore_seller = EverPsBookstoreSeller::getBookstoreSellerByCustomerId(
                (int)Context::getContext()->customer->id
            );
            if (!Validate::isLoadedObject($bookstore_seller)
                || (int)$bookstore_seller->id <= 0
            ) {
                Tools::redirect(
                    $link->getPageLink('my-account', true)
                );
            }
            if (!Tools::getValue('name')
                || !Validate::isCatalogName(Tools::getValue('name'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [name] is not valid'
                );
            }
            if (Tools::getValue('description_short')
                && !Validate::isCleanHtml(Tools::getValue('description_short'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [Short description] is not valid'
                );
            }
            if (Tools::getValue('description')
                && !Validate::isCleanHtml(Tools::getValue('description'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [Description] is not valid'
                );
            }
            if (!Tools::getValue('quantity')
                || !Validate::isInt(Tools::getValue('quantity'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [Quantity] is not valid'
                );
            }
            if (!Tools::getValue('price')
                || !Validate::isPrice(Tools::getValue('price'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [Price] is not valid'
                );
            }
            if (Tools::getValue('book_date')
                && !Validate::isInt(Tools::getValue('book_date'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [Book date] is not valid'
                );
            }
            if (Tools::getValue('book_condition')
                && !Validate::isGenericName(Tools::getValue('book_condition'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [Book condition] is not valid'
                );
            }
            if (Tools::getValue('book_editor')
                && !Validate::isGenericName(Tools::getValue('book_editor'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [Book editor] is not valid'
                );
            }
            if (Tools::getValue('author')
                && !Validate::isGenericName(Tools::getValue('author'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [author] is not valid'
                );
            }
            if (!Tools::getValue('isbn')
                || !Validate::isIsbn(Tools::getValue('isbn'))
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [Book ISBN] is not valid'
                );
            }
            // Product categories
            $categories = array();
            foreach (Tools::getAllValues() as $input => $value) {
                if (strpos($input, 'cat-') === 0) {
                    $categories[] = (int)$value;
                }
            }
            if (count($categories) <= 0
                || !Validate::isArrayWithIds($categories)
            ) {
                $this->postErrors[] = $this->l(
                    'Error : [categories] is not valid'
                );
            }
            if (count($this->postErrors)) {
                return array(
                    'error' => true,
                    'return' => $this->postErrors
                );
            }
            $book = new Product(
                (int)Tools::getValue('id_product')
            );
            $book->id_category_default = end($categories);
            $book->quantity = Tools::getValue('quantity');
            // Todo : as we surely get price with taxes, calculate price without taxes
            $book->price = Tools::getValue('price');
            $book->weight = (float)Tools::getValue('weight');
            $book->isbn = Tools::getValue('isbn');
            $book->reference = Tools::getValue('isbn');
            $author_id = Manufacturer::getIdByName(
                Tools::getValue('author')
            );
            if (!$author_id) {
                $author_manufacturer = new Manufacturer();
                $author_manufacturer->name = Tools::getValue('author');
                $author_manufacturer->active = true;
                $author_manufacturer->save();
                $author_id = $author_manufacturer->id;
            }
            $book->id_manufacturer = $author_id;
            if (Tools::getValue('book_condition')) {
                $condition_feature_value = EverPsBookstoreTools::getFeatureValueByValue(
                    (int)$this->condition_feature_id,
                    Tools::getValue('book_condition'),
                    (int)Context::getContext()->shop->id
                );
            }
            if (Tools::getValue('book_date')) {
                $date_feature_value = EverPsBookstoreTools::getFeatureValueByValue(
                    (int)$this->date_feature_id,
                    Tools::getValue('book_date'),
                    (int)Context::getContext()->shop->id
                );
            }
            if (Tools::getValue('book_editor')) {
                $editor_feature_value = EverPsBookstoreTools::getFeatureValueByValue(
                    (int)$this->editor_feature_id,
                    Tools::getValue('book_editor'),
                    (int)Context::getContext()->shop->id
                );
            }
            foreach (Language::getLanguages(false) as $lang) {
                $book->name[(int)$lang['id_lang']] = Tools::getValue('name');
                $truncated_short_desc = Tools::substr(
                    Tools::getValue('description_short'),
                    0,
                    $this->text_limit
                );
                $book->description_short[(int)$lang['id_lang']] = $truncated_short_desc;
                $book->description[(int)$lang['id_lang']] = Tools::getValue('description');
            }
            $link_rewrite = EverPsBookstoreTools::slugify(
                $book->name[(int)Configuration::get('PS_LANG_DEFAULT')]
            );
            foreach (Language::getLanguages(false) as $lang) {
                $book->link_rewrite[(int)$lang['id_lang']] = $link_rewrite;
            }

            if ($book->save()) {
                StockAvailable::setQuantity($book->id, 0, (int)Tools::getValue('quantity'));
                EverPsBookstoreTools::replaceProductAccessories(
                    (int)$book->id
                );
                EverPsBookstoreTools::replaceProductCategories(
                    (array)$categories,
                    (int)$book->id
                );
                if (Tools::getValue('book_date')) {
                    // Remove default date feature values
                    EverPsBookstoreTools::removeProductFeatures(
                        (int)$this->date_feature_id,
                        (int)$book->id
                    );
                    // Update date feature value
                    EverPsBookstoreTools::replaceProductFeatures(
                        (int)$this->date_feature_id,
                        (int)$book->id,
                        (int)$date_feature_value->id
                    );
                }
                if (Tools::getValue('book_editor')) {
                    // Remove default date feature values
                    EverPsBookstoreTools::removeProductFeatures(
                        (int)$this->editor_feature_id,
                        (int)$book->id
                    );
                    // Update date feature value
                    EverPsBookstoreTools::replaceProductFeatures(
                        (int)$this->editor_feature_id,
                        (int)$book->id,
                        (int)$editor_feature_value->id
                    );
                }
                if (Tools::getValue('book_condition')) {
                    // Remove default condition feature values
                    EverPsBookstoreTools::removeProductFeatures(
                        (int)$this->condition_feature_id,
                        (int)$book->id
                    );
                    // Update condition feature value
                    EverPsBookstoreTools::replaceProductFeatures(
                        (int)$this->condition_feature_id,
                        (int)$book->id,
                        (int)$condition_feature_value->id
                    );
                }
                // $media is registered on custom folder
                foreach (Tools::getAllValues() as $input => $value) {
                    if (strpos($input, 'bookmedia-') === 0) {
                        $media = new EverPsBookstoreBookImage(
                            (int)$value
                        );
                        $this->addImageImport(
                            $this->book_medias.$media->filename,
                            $book->id
                        );
                    }
                }
                $book_link = $link->getProductLink(
                    (int)$book->id,
                    null,
                    null,
                    null,
                    (int)Context::getContext()->language->id,
                    (int)Context::getContext()->shop->id
                );
                Tools::redirect($book_link);
            }
        }
    }

    /**
     * Import and attach  files to product
     * @param string filename, string name
     */
    private function addImageImport($file, $id_product)
    {
        $product = new Product(
            (int)$id_product
        );
        // Create image
        $image = new Image();
        $image->id_product = (int)$product->id;
        if ((bool)Image::getCover((int)$product->id)) {
            $image->cover =  false;
        } else {
            $image->cover =  true;
        }
        $image->position = Image::getHighestPosition((int)$product->id) + 1;
        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $image->legend[$lang['id_lang']] = $product->name[$lang['id_lang']];
        }

        $image->add();

        if (!$this->copyImgImport(
            (int)$product->id,
            (int)$image->id,
            $file,
            'products',
            !Tools::getValue('regenerate')
        )) {
            $image->delete();
        } else {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    private function copyImgImport($id_entity, $id_image, $url, $entity = 'products', $regenerate = true)
    {
        $tmpfile = tempnam(_PS_TMP_IMG_DIR_, 'ps_import');
        $watermark_types = explode(',', Configuration::get('WATERMARK_TYPES'));
        switch ($entity) {
            default:
            case 'products':
                $image_obj = new Image(
                    (int)$id_image
                );
                $path = $image_obj->getPathForCreation();
                break;
            case 'categories':
                $path = _PS_CAT_IMG_DIR_ . (int)$id_entity;
                break;
            case 'manufacturers':
                $path = _PS_MANU_IMG_DIR_ . (int)$id_entity;
                break;
            case 'suppliers':
                $path = _PS_SUPP_IMG_DIR_ . (int)$id_entity;
                break;
        }
        $url = str_replace(' ', '%20', trim($url));

        // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
        if (!ImageManager::checkImageMemoryLimit($url)) {
            return false;
        }

        // 'file_exists' doesn't work on distant file, and getimagesize makes the import slower.
        // Just hide the warning, the processing will be the same.
        if (Tools::copy($url, $tmpfile)) {
            ImageManager::resize($tmpfile, $path . '.jpg');
            $images_types = ImageType::getImagesTypes($entity);

            if ($regenerate) {
                foreach ($images_types as $image_type) {
                    ImageManager::resize(
                        $tmpfile,
                        $path . '-' . Tools::stripslashes($image_type['name']) . '.jpg',
                        $image_type['width'],
                        $image_type['height']
                    );
                    if (in_array($image_type['id_image_type'], $watermark_types)) {
                        Hook::exec('actionWatermark', array('id_image' => $id_image, 'id_product' => $id_entity));
                    }
                }
            }
            unlink($url);
        } else {
            unlink($tmpfile);
            return false;
        }
        unlink($tmpfile);
        return true;
    }

    public function getBreadcrumbLinks()
    {
        $book = new Product(
            (int)Tools::getValue('id_book'),
            false,
            (int)$this->context->language->id,
            (int)$this->context->shop->id
        );
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = array(
            'title' => $this->l('Edit book ').$book->name,
            'url' => $this->context->link->getModuleLink(
                'everpsbookstore',
                'editbook',
                array(
                    'id_book' => (int)Tools::getValue('id_book')
                )
            ),
        );
        return $breadcrumb;
    }
}
