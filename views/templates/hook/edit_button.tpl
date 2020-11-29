{*
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
*}
<a class="btn btn-info w-100" href="{$link->getModuleLink('everpsbookstore','editbook', ['id_book' => $product->id])|escape:'htmlall':'UTF-8'}" title="{l s='Edit book' mod='everpsbookstore'}" rel="nofollow">
    <span class="link-item">
        <i class="material-icons">library_add</i>
        {l s='Edit book' mod='everpsbookstore'}
    </span>
</a>
<form>
	<input type="hidden" name="ever_id_product" value="{$product->id|escape:'htmlall':'UTF-8'}">
	<input class="btn btn-success w-100" type="submit" name="everbookstore_validate_cart" id="everbookstore_validate_cart" value="{l s='Pay in shop' mod='everpsbookstore'}">
</form>