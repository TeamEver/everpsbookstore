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
 
$( document ).ready(function() {
    $('#everaddbook #isbn, #evereditbook #isbn').change(function(e){
        $.ajax({
            url: 'https://www.googleapis.com/books/v1/volumes',
            method: 'GET', 
            data: {
                'q' : 'isbn:'+$(this).val()//, Si on a une clé, la mettre sur la ligne suivante
                //'key' : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
            },
            dataType: 'JSON',
            success: function(data) {
                if (data.totalItems > 0) {
                    var book_infos = data.items[0].volumeInfo;
                    $('#everaddbook #name, #evereditbook #name').val(book_infos.title);
                    $('#everaddbook #description_short, #evereditbook #description_short').val(book_infos.description);
                    $('#everaddbook #date, #evereditbook #date').val(book_infos.publishedDate);
                    $('#everaddbook #publisher, #evereditbook #publisher').val(book_infos.publisher);
                    $('#everaddbook #author, #evereditbook #author').val(book_infos.authors[0]);
                    $('#everaddbook #bookmedia_url, #evereditbook #bookmedia_url').val(book_infos.imageLinks.thumbnail);
                    
                    console.log(book_infos.imageLinks.thumbnail);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus + ' ' + errorThrown + ' ' + jqXHR);
            }
        });
        // console.log(bookData);
    });
    $('.product_image input[type=file]').change(function(e){
        var formData = new FormData();
        var sellerInput = $(this);
        var parentBlock = sellerInput.parent();
        var sellerId = sellerInput.data('seller_id');
        var ever_url = sellerInput.data('url');
        formData.append('file', $(this)[0].files[0]);
        formData.append('id_bookstore_seller', sellerId);
        $.ajax({
            type: 'POST',
            url: ever_url,
            cache: false,
            processData: false,
            contentType: 'application/json',
            dataType: 'JSON',
            data: formData,
            contentType: false,
            beforeSend: function(){
                $('#bookloading').show();
            },
            complete: function(){
                $('#bookloading').hide();
            },
            success: function(data) {
                if (data.return) {
                    console.log(data.message);
                    var sellerBlock = '<div class="seller-filename"> <img src="' + data.message + '" style="max-width:50px;"/> </div>';
                    var inputId = '<input type="hidden" name="bookmedia-' + data.mediaid + '" class="mediaid ' + data.mediaid + '" value="' + data.mediaid + '"/>';
                    parentBlock.append(sellerBlock);
                    parentBlock.append(inputId);
                    // setTimeout(function(){ location.reload(); }, 3000);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus + ' ' + errorThrown + ' ' + jqXHR);
            }
        });
    });
});
function getGoogleBook(isbn) {
    return $.ajax({
      url: "https://www.googleapis.com/books/v1/volumes",
      method: "GET", 
      data: {'q' : 'isbn:'+isbn//, Si on a une clé, la mettre sur la ligne suivante
      //'key' : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
      },
      dataType: "json"});
}
