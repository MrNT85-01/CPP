jQuery(document).ready(function($) {
    var frontChartInstance = null;

    // --- تابع درخواست کد کپچا جدید ---
    function refreshCaptcha() {
        var captchaElement = $('.cpp-captcha-code');
        var captchaInput = $('#captcha_input');
        if (!captchaElement.length) return; // Exit if captcha element not found

        captchaElement.text('...'); // Show loading
        captchaInput.val(''); // Clear input

        $.post(cpp_front_vars.ajax_url, {
            action: 'cpp_get_captcha', // New AJAX action
            nonce: cpp_front_vars.nonce // Send nonce
        }, function(response) {
            if (response.success && response.data.code) {
                captchaElement.text(response.data.code);
            } else {
                captchaElement.text('خطا');
                console.error("Error fetching CAPTCHA:", response);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            captchaElement.text('خطا');
            console.error("AJAX Error fetching CAPTCHA:", textStatus, errorThrown, jqXHR);
        });
    }

    // --- ۱. مدیریت پاپ‌آپ سفارش ---
    $(document).on('click', '.cpp-order-btn', function() {
        var productId = $(this).data('product-id');
        var productName = $(this).data('product-name');
        var modal = $('#cpp-order-modal');
        modal.find('#cpp-order-product-id').val(productId);
        modal.find('.cpp-modal-product-name').text(productName);
        modal.find('.cpp-form-message').remove();
        modal.find('form')[0].reset();
        refreshCaptcha(); // Load captcha when modal opens
        modal.show();
    });

    // --- دکمه رفرش کپچا ---
    $(document).on('click', '.cpp-refresh-captcha', refreshCaptcha);

    // --- ۲. ارسال فرم سفارش با AJAX ---
    $('#cpp-order-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var button = form.find('button[type="submit"]');
        var formData = form.serialize(); // Includes captcha_input
        var originalButtonText = button.text();

        button.prop('disabled', true).text(cpp_front_vars.i18n.sending || 'در حال ارسال...'); // Use localized text
        form.find('.cpp-form-message').remove();

        $.post(cpp_front_vars.ajax_url, formData + '&action=cpp_submit_order&nonce=' + cpp_front_vars.nonce, function(response) {
            if (response.success) {
                form.before('<div class="cpp-form-message cpp-success">' + response.data.message + '</div>');
                setTimeout(function() {
                    $('#cpp-order-modal').hide();
                    button.prop('disabled', false).text(originalButtonText);
                }, 3000); // Longer delay to read message
            } else {
                // Display specific error message from server
                form.before('<div class="cpp-form-message cpp-error">' + response.data.message + '</div>');
                button.prop('disabled', false).text(originalButtonText);
                // Refresh captcha on error, especially if it's a captcha error
                if (response.data.code === 'captcha_error') {
                    refreshCaptcha();
                }
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            form.before('<div class="cpp-form-message cpp-error">' + (cpp_front_vars.i18n.server_error || 'خطای سرور، لطفا دوباره تلاش کنید.') + ' (' + textStatus + ')</div>');
            button.prop('disabled', false).text(originalButtonText);
            console.error("AJAX Error submitting order:", textStatus, errorThrown, jqXHR);
            refreshCaptcha(); // Refresh captcha on server error too
        });
    });

    // --- ۳. مدیریت پاپ‌آپ نمودار ---
    // ... (کد نمودار بدون تغییر) ...
     $(document).on('click', '.cpp-chart-btn', function() {
        var productId = $(this).data('product-id');
        var modal = $('#cpp-front-chart-modal');
        var chartCanvas = modal.find('#cppFrontPriceChart');
        modal.show();
        modal.find('.chart-error').remove();
        chartCanvas.show();
        if (frontChartInstance) { frontChartInstance.destroy(); }
         // Add loading indicator
        chartCanvas.parent().append('<p class="chart-loading" style="text-align:center;">در حال بارگذاری داده...</p>');

        $.get(cpp_front_vars.ajax_url, { action: 'cpp_get_chart_data', product_id: productId, nonce: cpp_front_vars.nonce }, function(response) { // Added nonce
            chartCanvas.parent().find('.chart-loading').remove(); // Remove loading
            if (response.success && response.data && response.data.labels && response.data.labels.length > 0) {
                 renderFrontChart(response.data, chartCanvas[0]);
             }
            else {
                 var errorMsg = (response.data && typeof response.data === 'string') ? response.data : 'تاریخچه قیمت برای این محصول در دسترس نیست.';
                 chartCanvas.hide().parent().prepend('<p class="chart-error" style="color:red; text-align:center;">'+errorMsg+'</p>');
             }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            chartCanvas.parent().find('.chart-loading').remove(); // Remove loading
            chartCanvas.hide().parent().prepend('<p class="chart-error" style="color:red; text-align:center;">خطا در بارگذاری داده‌های نمودار: '+textStatus+'</p>');
             console.error("AJAX Error loading chart data:", textStatus, errorThrown, jqXHR);
        });
    });

    function renderFrontChart(chartData, ctx) {
        var datasets = [];
        if (chartData.prices && chartData.prices.length > 0) {
            datasets.push({ label: 'قیمت پایه', data: chartData.prices, borderColor: 'rgb(75, 192, 192)', tension: 0.1, fill: false, borderWidth: 2 });
        }
        if (chartData.min_prices && chartData.min_prices.length > 0) {
            datasets.push({ label: 'حداقل قیمت', data: chartData.min_prices, borderColor: 'rgba(255, 99, 132, 0.7)', borderDash: [5, 5], fill: '+1', pointRadius: 0, borderWidth: 1 });
        }
        if (chartData.max_prices && chartData.max_prices.length > 0) {
            datasets.push({ label: 'حداکثر قیمت', data: chartData.max_prices, borderColor: 'rgba(54, 162, 235, 0.7)', borderDash: [5, 5], fill: false, pointRadius: 0, borderWidth: 1 });
        }
        if (!ctx || typeof ctx.getContext !== 'function') {
            console.error("Invalid canvas context for front chart.");
            return;
        }
        try {
            frontChartInstance = new Chart(ctx, {
                type: 'line',
                data: { labels: chartData.labels, datasets: datasets },
                options: {
                     responsive: true,
                     maintainAspectRatio: false,
                      plugins: { legend: { display: true } },
                      scales: { y: { beginAtZero: false } }
                }
             });
        } catch(e) {
            console.error("Error creating front chart:", e);
            $(ctx).parent().prepend('<p class="chart-error" style="color:red; text-align:center;">خطا در رسم نمودار.</p>');
        }
    }


    // --- ۴. منطق بستن پاپ‌آپ‌ها ---
    $(document).on('click', '.cpp-modal-close', function() {
        $(this).closest('.cpp-modal-overlay').hide();
         // Destroy chart if closing chart modal
         if ($(this).closest('#cpp-front-chart-modal').length && frontChartInstance) {
            frontChartInstance.destroy();
            frontChartInstance = null;
        }
    });
    $(document).on('click', '.cpp-modal-overlay', function(e) {
        if ($(e.target).is('.cpp-modal-overlay')) {
            $(this).hide();
            // Destroy chart if closing chart modal
             if ($(this).is('#cpp-front-chart-modal') && frontChartInstance) {
                frontChartInstance.destroy();
                frontChartInstance = null;
            }
        }
    });

    // --- ۵. فیلتر دسته‌بندی‌ها برای شورت‌کدهای گرید ---
    $('.cpp-grid-view-filters .filter-btn').on('click', function(e){
        e.preventDefault();
        var $this = $(this);
        var catId = $this.data('cat-id');
        var wrapper = $this.closest('.cpp-grid-view-wrapper');
        wrapper.find('.cpp-grid-view-filters .filter-btn').removeClass('active');
        $this.addClass('active');
        if (catId === 'all') {
            wrapper.find('.cpp-grid-view-table .product-row').show();
        } else {
            wrapper.find('.cpp-grid-view-table .product-row').hide();
            wrapper.find('.cpp-grid-view-table .product-row[data-cat-id="' + catId + '"]').show();
        }
        wrapper.find('.cpp-grid-view-footer').toggle(catId === 'all');
    });

    // --- ۶. منطق بارگذاری بیشتر محصولات (مشاهده بیشتر) ---
    $(document).on('click', '.cpp-view-more-btn', function() {
        var button = $(this);
        var wrapper = button.closest('.cpp-grid-view-wrapper');
        var page = button.data('page') + 1;
        // Correctly determine shortcode type from button's data attribute
        var shortcode_type = button.data('shortcode-type'); // Should be 'with_date' or 'no_date'
        var original_text = cpp_front_vars.i18n.view_more;

        button.prop('disabled', true).text(cpp_front_vars.i18n.loading);

        $.post(cpp_front_vars.ajax_url, {
            action: 'cpp_load_more_products',
            nonce: cpp_front_vars.nonce,
            page: page,
            shortcode_type: shortcode_type // Pass the correct type
        }, function(response) {
            if (response.success && response.data.html) {
                wrapper.find('.cpp-grid-view-table tbody').append(response.data.html);
                button.data('page', page); // Update page number
                 // Check if there are more products to load
                if (!response.data.has_more) {
                    button.text(cpp_front_vars.i18n.no_more_products).prop('disabled', true);
                     // Optionally hide the button completely
                     // button.parent().hide();
                } else {
                    button.prop('disabled', false).text(original_text);
                }

            } else {
                // Handle case where success is false or html is missing
                button.text(cpp_front_vars.i18n.no_more_products).prop('disabled', true);
                 // button.parent().hide();
                 console.log("Load more response error or no more products:", response);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            alert(cpp_front_vars.i18n.server_error || 'خطای سرور.');
            button.prop('disabled', false).text(original_text);
             console.error("AJAX Error loading more products:", textStatus, errorThrown, jqXHR);
        });
    });

     // Add localization strings to cpp_front_vars if not already present
     if (!cpp_front_vars.i18n) {
         cpp_front_vars.i18n = {};
     }
     cpp_front_vars.i18n.sending = cpp_front_vars.i18n.sending || 'در حال ارسال...';
     cpp_front_vars.i18n.server_error = cpp_front_vars.i18n.server_error || 'خطای سرور، لطفا دوباره تلاش کنید.';
     cpp_front_vars.i18n.view_more = cpp_front_vars.i18n.view_more || 'مشاهده بیشتر';
     cpp_front_vars.i18n.loading = cpp_front_vars.i18n.loading || 'در حال بارگذاری...';
     cpp_front_vars.i18n.no_more_products = cpp_front_vars.i18n.no_more_products || 'محصول دیگری برای نمایش وجود ندارد.';


}); // End jQuery ready
