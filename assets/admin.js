jQuery(document).ready(function ($) {
    function updateStats() {
        $.ajax({
            url: wbi_vars.ajax_url, method: 'POST', data: { action: 'wbi_get_stats', nonce: wbi_vars.nonce, },
            success: function(response) {
                if (response.success) {
                    $('.wbi-stat-card .stat-label:contains("Today")').prev('.stat-value').text(response.data.today);
                    $('.wbi-stat-card .stat-label:contains("This Week")').prev('.stat-value').text(response.data.week);
                }
            }
        });
    }

    function updateRecentBooksTable() {
        const tableWrapper = $('#wbi-books-table-wrapper');
        $.ajax({
            url: wbi_vars.ajax_url, method: 'POST', data: { action: 'wbi_refresh_books_table', nonce: wbi_vars.nonce, },
            success: function(response) {
                if (response.trim() !== '') {
                    tableWrapper.html(response);
                }
            }
        });
    }

    $('.wbi-tabs .tab-button').on('click', function () {
        const tab = $(this).data('tab');
        $('.wbi-tabs .tab-button').removeClass('active');
        $(this).addClass('active');
        $('.wbi-tab-content').removeClass('active');
        $('#wbi-' + tab + '-import').addClass('active');
    });

    $('#wbi-import-btn').on('click', function () {
        const btn = $(this), isbn = $('#wbi-isbn-input').val().trim(), resultDiv = $('#wbi-import-result');
        if (!isbn) { resultDiv.html('<p class="wbi-error">Please enter an ISBN.</p>'); return; }
        btn.prop('disabled', true).text(wbi_vars.i18n.importing);
        resultDiv.html('');
        $.ajax({
            url: wbi_vars.ajax_url, method: 'POST', data: { action: 'wbi_import_book', nonce: wbi_vars.nonce, isbn: isbn, },
            success: function (response) {
                if (response.success) {
                    const editLink = `<a href="${response.data.edit_link}" target="_blank">Edit Product</a>`;
                    resultDiv.html(`<p class="wbi-success">${response.data.message} ${editLink}</p>`);
                    $('#wbi-isbn-input').val('');
                    updateStats(); updateRecentBooksTable();
                } else { resultDiv.html(`<p class="wbi-error">Error: ${response.data.message}</p>`); }
            },
            error: function () { resultDiv.html('<p class="wbi-error">An unexpected error occurred.</p>'); },
            complete: function () { btn.prop('disabled', false).text('Import Book'); },
        });
    });

    $('#wbi-bulk-import-btn').on('click', function () {
        const btn = $(this), isbnsText = $('#wbi-bulk-isbns').val().trim(), resultsDiv = $('#wbi-bulk-results');
        const isbns = isbnsText.split('\n').map(isbn => isbn.trim()).filter(isbn => isbn);
        if (isbns.length === 0) { resultsDiv.html('<p class="wbi-error">Please enter at least one ISBN.</p>'); return; }
        btn.prop('disabled', true).text(wbi_vars.i18n.processing_bulk);
        resultsDiv.html('<p class="wbi-importing">Processing... this may take a while.</p>');
        $.ajax({
            url: wbi_vars.ajax_url, method: 'POST', data: { action: 'wbi_bulk_import_books', nonce: wbi_vars.nonce, isbns: isbns.join('\n'), },
            success: function (response) {
                if (response.success) {
                    let resultsHtml = '<h3>Import Results:</h3><ul class="wbi-results-list">';
                    response.data.forEach(function(result) { resultsHtml += `<li>${result}</li>`; });
                    resultsHtml += '</ul>';
                    resultsDiv.html(resultsHtml);
                    updateStats(); updateRecentBooksTable();
                } else { resultsDiv.html(`<p class="wbi-error">Error: ${response.data.message}</p>`); }
            },
            error: function () { resultsDiv.html('<p class="wbi-error">An unexpected error occurred during bulk import.</p>'); },
            complete: function () { btn.prop('disabled', false).text('Import Books'); }
        });
    });
});