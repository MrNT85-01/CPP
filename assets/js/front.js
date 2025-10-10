jQuery(document).ready(function($) {
    var frontChartInstance = null;

    // --- ۱. مدیریت پاپ‌آپ سفارش ---
    $(document).on('click', '.cpp-order-btn', function() {
        var productId = $(this).data('product-id');
        var productName = $(this).data('product-name');

        var modal = $('#cpp-order-modal');
        modal.find('#cpp-order-product-id').val(productId);
        modal.find('.cpp-modal-product-name').text(productName);
        modal.find('.cpp-form-message').remove();
        modal.find('form')[0].reset();
        modal.show();
    });

    // --- ۲. ارسال فرم سفارش با AJAX ---
    $('#cpp-order-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var button = form.find('button[type="submit"]');
        var formData = form.serialize();

        button.prop('disabled', true).text('در حال ارسال...');
        form.find('.cpp-form-message').remove();

        $.post(cpp_front_vars.ajax_url, formData + '&action=cpp_submit_order&nonce=' + cpp_front_vars.nonce, function(response) {
            if (response.success) {
                form.before('<div class="cpp-form-message cpp-success">' + response.data + '</div>');
                setTimeout(function() {
                    $('#cpp-order-modal').hide();
                    button.prop('disabled', false).text('ثبت درخواست');
                }, 2000);
            } else {
                form.before('<div class="cpp-form-message cpp-error">' + response.data + '</div>');
                button.prop('disabled', false).text('ثبت درخواست');
            }
        }).fail(function() {
            form.before('<div class="cpp-form-message cpp-error">خطای سرور، لطفا دوباره تلاش کنید.</div>');
            button.prop('disabled', false).text('ثبت درخواست');
        });
    });

    // --- ۳. مدیریت پاپ‌آپ نمودار ---
    $(document).on('click', '.cpp-chart-btn', function() {
        var productId = $(this).data('product-id');
        var modal = $('#cpp-front-chart-modal');
        var chartCanvas = modal.find('#cppFrontPriceChart');

        modal.show();
        modal.find('.chart-error').remove();
        chartCanvas.show();

        if (frontChartInstance) {
            frontChartInstance.destroy();
        }

        $.get(cpp_front_vars.ajax_url, {
            action: 'cpp_get_chart_data',
            product_id: productId
        }, function(response) {
            if (response.success) {
                renderFrontChart(response.data, chartCanvas[0]);
            } else {
                 chartCanvas.hide().parent().prepend('<p class="chart-error">تاریخچه قیمت برای این محصول در دسترس نیست.</p>');
            }
        }).fail(function() {
            chartCanvas.hide().parent().prepend('<p class="chart-error">خطا در بارگذاری داده‌های نمودار.</p>');
        });
    });

    function renderFrontChart(chartData, ctx) {
        var datasets = [];

        if (chartData.prices && chartData.prices.length > 0) {
            datasets.push({ 
                label: 'قیمت پایه', data: chartData.prices, borderColor: 'rgb(75, 192, 192)', tension: 0.1, fill: false 
            });
        }
        if (chartData.min_prices && chartData.min_prices.length > 0) {
            datasets.push({ label: 'حداقل قیمت', data: chartData.min_prices, borderColor: 'rgba(255, 99, 132, 0.5)', borderDash: [5, 5], fill: false, pointRadius: 0 });
        }
        if (chartData.max_prices && chartData.max_prices.length > 0) {
            datasets.push({ label: 'حداکثر قیمت', data: chartData.max_prices, borderColor: 'rgba(54, 162, 235, 0.5)', borderDash: [5, 5], fill: false, pointRadius: 0 });
        }
        frontChartInstance = new Chart(ctx, {
            type: 'line', data: { labels: chartData.labels, datasets: datasets }, options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // --- بستن همه پاپ‌آپ‌ها ---
    $(document).on('click', '.cpp-modal-close, .cpp-modal-overlay', function(e) {
        if ($(e.target).is('.cpp-modal-close') || $(e.target).is('.cpp-modal-overlay')) {
            $('.cpp-modal-overlay').hide();
        }
    });
    $('.cpp-modal-container').on('click', function(e) {
        e.stopPropagation();
    });
    
    // --- ۴. مدیریت فیلتر دسته‌بندی در شورت‌کد گرید ---
    $('.cpp-grid-sidebar a').on('click', function(e) {
        e.preventDefault();
        $('.cpp-grid-sidebar li').removeClass('active');
        $(this).parent('li').addClass('active');

        var catId = $(this).data('cat-id');

        if (catId === 'all') {
            $('.cpp-grid-row').show();
        } else {
            $('.cpp-grid-row').hide();
            $('.cpp-grid-row[data-cat-id="' + catId + '"]').show();
        }
    });

    // --- ۵. مدیریت فیلتر دسته‌بندی در شورت‌کد جدید (grid-view) ---
    $('.cpp-grid-view-filters .filter-btn').on('click', function(e){
        e.preventDefault();
        var $this = $(this);
        var catId = $this.data('cat-id');

        // مدیریت کلاس active
        $('.cpp-grid-view-filters .filter-btn').removeClass('active');
        $this.addClass('active');

        // نمایش و پنهان کردن ردیف‌ها
        if (catId === 'all') {
            $('.cpp-grid-view-table .product-row').show();
        } else {
            $('.cpp-grid-view-table .product-row').hide();
            $('.cpp-grid-view-table .product-row[data-cat-id="' + catId + '"]').show();
        }
    });
});
