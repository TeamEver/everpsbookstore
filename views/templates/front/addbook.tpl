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
{extends file='page.tpl'}

{block name="page_content"}
<div class="container">
    <div class="row">
        {if isset($errors)}
        {foreach from=$errors item=message}
        <div class="alert alert-danger">
            {$message|escape:'htmlall':'UTF-8'}
        </div>
        {/foreach}
        {/if}
        {* Success message *}
        {if isset($successes)}
        {foreach from=$successes item=message}
        <div class="alert alert-success">
            {$message|escape:'htmlall':'UTF-8'}
        </div>
        {/foreach}
        {/if}
        <h1 class="text-center">{l s='Add a book' mod='everpsbookstore'}</h1>
        <form method="POST" id="everaddbook">
            <input type="hidden" class="form-control" id="id_seller" name="id_bookstore_seller" value="{$bookstore_seller->id|escape:'htmlall':'UTF-8'}">
            <input type="hidden" name="bookmedia_url" id="bookmedia_url" value="">
            <div class="form-group col-md-12">
                <label for="isbn">{l s='Book ISBN' mod='everpsbookstore'} <span class="everrequired">*</span></label>
                <input type="text" class="form-control" id="isbn" name="isbn" aria-describedby="isbnHelp" placeholder="{l s='Enter book ISBN' mod='everpsbookstore'}" required>
            </div>
            <div class="form-group col-md-12">
                <label for="name">{l s='Book name' mod='everpsbookstore'} <span class="everrequired">*</span></label>
                <input type="text" class="form-control" id="name" name="name" aria-describedby="nameHelp" placeholder="{l s='Enter book name' mod='everpsbookstore'}" required>
            </div>
            <div class="form-group col-md-12">
                <label for="author">{l s='Book author' mod='everpsbookstore'} <span class="everrequired">*</span></label>
                <input type="text" class="form-control" id="author" name="author" aria-describedby="authorHelp" placeholder="{l s='Enter book author' mod='everpsbookstore'}" required>
            </div>
            <div class="form-group col-md-12">
                <label for="book_date">{l s='Book edition year date' mod='everpsbookstore'} <span class="everrequired">*</span></label>
                <input type="number" class="form-control" id="book_date" name="book_date" aria-describedby="authorHelp" placeholder="{l s='Enter book edition year date' mod='everpsbookstore'}" required>
            </div>
            <div class="form-group col-md-12">
                <label for="book_editor">{l s='Book editor' mod='everpsbookstore'}</label>
                <select class="form-control" id="book_editor" name="book_editor">
                {foreach from=$editors item=editor}
                    <option value="{$editor.value|escape:'htmlall':'UTF-8'}">{$editor.value|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
                </select>
            </div>
            <div class="form-group col-md-12">
                <p>{l s='Book category' mod='everpsbookstore'}</p>
                {foreach from=$categories item=category}
                {if $category.id_category != 1}
                    <div class="col-md-3 book-cat">
                        <input type="checkbox" class="form-check-input" id="{$category.id_category}" name="cat-{$category.id_category}" value="{$category.id_category}">
                        <label class="form-check-label" for="{$category.id_category}">{$category.name}</label>
                    </div>
                {/if}
                {/foreach}
            </div>
            <div class="form-group col-md-12">
                <label for="book_condition">{l s='Book condition' mod='everpsbookstore'}</label>
                <select class="form-control" id="book_condition" name="book_condition">
                {foreach from=$conditions item=condition}
                    <option value="{$condition.value|escape:'htmlall':'UTF-8'}">{$condition.value|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
                </select>
            </div>
            <div class="form-group col-md-12 product_image">
                <label for="product_image">{l s='Book image(s)' mod='everpsbookstore'}</label>
                <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
                <input id="product_image" name="product_image" type="file" accept="image/*" capture="camera" data-url="{$link->getModuleLink('everpsbookstore','ajaxUploadBookFile')|escape:'htmlall':'UTF-8'}" data-seller_id="{$bookstore_seller->id|escape:'htmlall':'UTF-8'}"/>
                <img src="{$loadinggif|escape:'htmlall':'UTF-8'}" id="bookloading" class="nodisplay">
            </div>
            <div class="form-group col-md-12">
                <label for="description_short">{l s='Book short description' mod='everpsbookstore'}</label>
                <textarea class="form-control" id="description_short" name="description_short" rows="3"></textarea>
            </div>
            <div class="form-group col-md-12">
                <label for="description">{l s='Book description' mod='everpsbookstore'}</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <div class="form-group col-md-12">
                <label for="quantity">{l s='Book quantity' mod='everpsbookstore'} <span class="everrequired">*</span></label>
                <input type="number" class="form-control" id="quantity" name="quantity" aria-describedby="nameHelp" placeholder="{l s='Enter quantity' mod='everpsbookstore'}" required>
            </div>
            <div class="form-group col-md-12">
                <label for="weight">{l s='Book weight' mod='everpsbookstore'} (<span class="dimension_unit">{$weight_unit|escape:'htmlall':'UTF-8'}</span>)</label>
                <input type="text" class="form-control" id="weight" name="weight" aria-describedby="nameHelp" placeholder="{l s='Enter weight' mod='everpsbookstore'}">
            </div>
            <div class="form-group col-md-12">
                <label for="price">{l s='Book price' mod='everpsbookstore'} <span class="everrequired">*</span></label>
                <input type="text" class="form-control" id="price" name="price" aria-describedby="nameHelp" placeholder="{l s='Enter price' mod='everpsbookstore'}" required>
            </div>
            <button type="submit" class="btn btn-primary" id="everaddbook" name="everaddbook">{l s='Submit' mod='everpsbookstore'}</button>
        </form>
    </div>
</div>
{/block}
