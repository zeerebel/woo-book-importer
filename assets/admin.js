jQuery(document).ready(function($) {
    // Single Importer
    $('#wbi-import-form').on('submit', function(e) {
        e.preventDefault();
        var $button = $(this).find('button');
        var isbn = $('#wbi-isbn').val();
        var $results = $('#wbi-single-results');
        $button.prop('disabled', true).text('Importing...');
        $results.empty();
        $.post(wbi_ajax.ajax_url, { action: 'wbi_import_isbn', nonce: wbi_ajax.nonce, isbn: isbn })
            .done(function(response) {
                if(response.success) {
                     $results.html('<div class="notice notice-success is-dismissible"><p>' + response.data.message + ' | <a href="'+response.data.edit_url+'" target="_blank">Edit Product</a></p></div>');
                     setTimeout(function(){ location.reload(); }, 1500);
                } else {
                     $results.html('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                }
            }).fail(function() {
                $results.html('<div class="notice notice-error is-dismissible"><p>An unexpected server error occurred.</p></div>');
            }).always(function() {
                $button.prop('disabled', false).text('Import');
                $('#wbi-isbn').val('');
            });
    });

    // Fetch Keepa Data
    $('.wbi-fetch-keepa').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $row = $button.closest('tr');
        var isbn = $row.data('isbn');
        var $resultsDiv = $row.find('.wbi-keepa-results');
        $button.text('Fetching...').prop('disabled', true);
        $resultsDiv.hide().empty();
        $.post(wbi_ajax.ajax_url, { action: 'wbi_fetch_keepa_data', nonce: wbi_ajax.nonce, isbn: isbn })
            .done(function(response) {
                if(response.success) {
                    var html = '<div><strong>Current Price:</strong> ' + response.data.current_new_price + '</div>';
                    html += '<div><strong>180-Day Avg:</strong> ' + response.data.avg_new_price + '</div>';
                    html += '<div><strong>Sales Rank:</strong> ' + response.data.sales_rank + '</div>';
                    $resultsDiv.html(html).show();
                } else {
                    $resultsDiv.html('<span style="color:red;">' + response.data.message + '</span>').show();
                }
            }).fail(function() {
                 $resultsDiv.html('<span style="color:red;">Server error.</span>').show();
            }).always(function() {
                 $button.hide();
            });
    });

    // Update Product Data
    $('.wbi-update-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $button = $form.find('button');
        var $row = $form.closest('tr');
        var productId = $row.data('product-id');
        var price = $form.find('input[name="price"]').val();
        var stock = $form.find('input[name="stock"]').val();
        $button.text('Saving...').prop('disabled', true);
        $.post(wbi_ajax.ajax_url, { action: 'wbi_update_product_data', nonce: wbi_ajax.nonce, product_id: productId, price: price, stock: stock })
            .done(function(response) {
                if(response.success) {
                     $button.text('Saved!');
                     setTimeout(function() { $button.text('Save'); }, 2000);
                } else {
                    alert(response.data.message);
                }
            }).fail(function() {
                alert('An error occurred while saving.');
            }).always(function() {
                $button.prop('disabled', false);
            });
    });
});