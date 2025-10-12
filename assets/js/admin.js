jQuery(document).ready(function($) {

    // آکاردئون
    $('.cpp-accordion-header').on('click', function() {
        $(this).toggleClass('active').next('.cpp-accordion-content').slideToggle(300);
    });
    // بستن آکاردئون‌ها در بارگذاری اولیه صفحه
    if ($('.cpp-accordion-content').length && !$('.cpp-accordion-content').find('.error').length) {
        $('.cpp-accordion-content').hide(); 
        $('.cpp-accordion-header').removeClass('active'); 
    }

    // مدیریت آپلود عکس
    var mediaUploader;
    // به دلیل اینکه فرم‌ها با ایجکس لود می‌شوند، از event delegation استفاده می‌کنیم
    $(document).on('click', '.cpp-upload-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var inputId = button.data("input-id");
        var input_field = jQuery("#" + inputId);
        var preview_img_container = input_field.closest('.cpp-image-uploader-wrapper').find(".cpp-image-preview");

        if (mediaUploader) { mediaUploader.open(); return; }

        mediaUploader = wp.media({ 
            title: 'انتخاب یا آپلود تصویر', 
            button: { text: 'استفاده از این تصویر' }, 
            multiple: false 
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            input_field.val(attachment.url);
            preview_img_container.html('<img src="' + attachment.url + '" style="max-width: 100px; height: auto; margin-top: 10px;">');
        });
        mediaUploader.open();
    });

    // ویرایش سریع با دبل کلیک
    $(document).on('dblclick', '.cpp-quick-edit, .cpp-quick-edit-select', function() {
        var cell = $(this);
        if (cell.hasClass('editing')) return;
        var id = cell.data('id'), field = cell.data('field'), table_type = cell.data('table-type'), original_text = cell.text().trim();
        cell.data('original-content', cell.html()).addClass('editing');
        var input_element;
        if (cell.hasClass('cpp-quick-edit-select')) {
            var current_value = cell.data('current');
            input_element = $('<select>').addClass('cpp-quick-edit-input');
            var options_list = (table_type === 'orders') ? cpp_admin_vars.order_statuses : (typeof cppStatusOptions !== 'undefined' ? cppStatusOptions : {});
            $.each(options_list, function(val, text) {
                $('<option>').val(val).text(text).prop('selected', val == current_value).appendTo(input_element);
            });
        } else {
            var input_type = (field === 'admin_note' || field === 'description') ? 'textarea' : 'text';
            input_element = $(`<${input_type}>`).addClass('cpp-quick-edit-input').val(original_text);
        }
        var save_btn = $('<button>').addClass('button button-primary button-small').text('ذخیره');
        var cancel_btn = $('<button>').addClass('button button-secondary button-small').text('لغو').css('margin-right', '5px');
        var buttons = $('<div>').addClass('cpp-quick-edit-buttons').css('margin-top', '5px').append(save_btn).append(cancel_btn);
        cell.html('').append(input_element).append(buttons);
        input_element.focus();
        save_btn.on('click', function() { performSave(cell, id, field, table_type); });
        cancel_btn.on('click', function() { cell.removeClass('editing').html(cell.data('original-content')); });
        input_element.on('keydown', function(e) {
            if (e.key === 'Escape') cancel_btn.click();
            if (e.key === 'Enter' && input_type === 'text') {
                e.preventDefault();
                save_btn.click();
            }
        });
    });
    
    function performSave(cell, id, field, table_type) {
        var new_value = cell.find('.cpp-quick-edit-input').val();
        cell.removeClass('editing').html('در حال ذخیره...');
        $.post(cpp_admin_vars.ajax_url, {
            action: 'cpp_quick_update', security: cpp_admin_vars.nonce, id: id, field: field, value: new_value, table_type: table_type
        }, function(response) {
            if (response.success) {
                var display_value;
                if (cell.hasClass('cpp-quick-edit-select')) {
                    var options_list = (table_type === 'orders') ? cpp_admin_vars.order_statuses : (typeof cppStatusOptions !== 'undefined' ? cppStatusOptions : {});
                    display_value = options_list[new_value];
                    cell.data('current', new_value);
                } else {
                    display_value = new_value.replace(/\n/g, '<br>');
                }
                cell.html(display_value);
                if (response.data.new_time) { 
                    cell.closest('tr').find('.cpp-last-update').text(response.data.new_time);
                    if (table_type === 'orders') {
                        // برای رفرش شدن عدد کنار منو
                        window.location.reload();
                    }
                }
            } else {
                alert('خطا: ' + (response.data || 'خطای نامشخص'));
                cell.html(cell.data('original-content'));
            }
        }).fail(function() {
            alert('خطای سرور.');
            cell.html(cell.data('original-content'));
        });
    }

    // منطق پاپ‌آپ ویرایش
    $(document).on('click', '.cpp-edit-button, .cpp-edit-cat-button', function() {
        var button = $(this);
        var ajax_data = {};
        if (button.hasClass('cpp-edit-button')) {
            ajax_data = { action: 'cpp_fetch_product_edit_form', id: button.data('product-id') };
        } else {
            ajax_data = { action: 'cpp_fetch_category_edit_form', id: button.data('cat-id') };
        }
        openEditModal(ajax_data);
    });

    function openEditModal(ajax_data) {
        $('#cpp-edit-modal').addClass('loading').show();
        $('.cpp-edit-modal-content').html('<p style="text-align:center; padding: 20px;">در حال بارگذاری فرم ویرایش...</p>');
        ajax_data.security = cpp_admin_vars.nonce;

        $.get(cpp_admin_vars.ajax_url, ajax_data)
            .done(function(response) {
                $('#cpp-edit-modal').removeClass('loading');
                if (response.success) {
                    $('.cpp-edit-modal-content').html(response.data.html);
                    // دوباره فراخوانی می‌کنیم تا آپلودر در مودال هم کار کند
                    if(typeof window.cpp_init_media_uploader === 'function') {
                        window.cpp_init_media_uploader();
                    }
                } else {
                    var errorMessage = response.data ? response.data : 'خطای نامشخص.';
                    $('.cpp-edit-modal-content').html('<p style="color:red; text-align:center; padding: 20px;">خطا در بارگذاری فرم: ' + errorMessage + '</p>');
                }
            })
            .fail(function() {
                $('#cpp-edit-modal').removeClass('loading');
                $('.cpp-edit-modal-content').html('<p style="color:red; text-align:center; padding: 20px;">خطای اتصال سرور.</p>');
            });
    }
    
    $(document).on('click', '.cpp-modal-overlay .cpp-close-modal', function() {
        $(this).closest('.cpp-modal-overlay').hide();
    });

    // نمایش نمودار
    var chartInstance = null;
    $(document).on('click', '.cpp-show-chart', function(e) {
        e.preventDefault();
        var productId = $(this).data('product-id');
        var modal = $('#cpp-chart-modal');
        var chartCanvas = modal.find('#cppPriceChart');
        modal.show();
        modal.find('.chart-error').remove();
        chartCanvas.show();
        if (chartInstance) { chartInstance.destroy(); }
        $.get(cpp_admin_vars.ajax_url, { action: 'cpp_get_chart_data', product_id: productId }, function(response) {
            if (response.success) { renderChart(response.data, chartCanvas[0]); } 
            else { chartCanvas.hide().parent().prepend('<p class="chart-error" style="color:red; text-align:center;">تاریخچه قیمت برای این محصول در دسترس نیست.</p>'); }
        }).fail(function() {
             chartCanvas.hide().parent().prepend('<p class="chart-error" style="color:red; text-align:center;">خطا در بارگذاری داده‌های نمودار.</p>');
        });
    });

    function renderChart(chartData, ctx) {
        var datasets = [];
        if(chartData.prices && chartData.prices.length > 0) { datasets.push({ label: 'قیمت پایه', data: chartData.prices, borderColor: 'rgb(75, 192, 192)', backgroundColor: 'rgba(75, 192, 192, 0.2)', tension: 0.3, fill: false, borderWidth: 3 }); }
        if(chartData.min_prices && chartData.min_prices.length > 0) { datasets.push({ label: 'حداقل قیمت', data: chartData.min_prices, borderColor: 'rgb(255, 99, 132)', backgroundColor: 'rgba(255, 99, 132, 0.1)', tension: 0, borderDash: [5, 5], fill: '+1', pointRadius: 0, borderWidth: 1 }); }
        if(chartData.max_prices && chartData.max_prices.length > 0) { datasets.push({ label: 'حداکثر قیمت', data: chartData.max_prices, borderColor: 'rgb(54, 162, 235)', backgroundColor: 'rgba(54, 162, 235, 0.1)', tension: 0, borderDash: [5, 5], fill: false, pointRadius: 0, borderWidth: 1 }); }
        chartInstance = new Chart(ctx, { type: 'line', data: { labels: chartData.labels, datasets: datasets }, options: { responsive: true, scales: { y: { beginAtZero: false, title: { display: true, text: 'قیمت' }}, x: { title: { display: true, text: 'تاریخ' }}} } });
    }

    // مدیریت ذخیره فرم‌های پاپ آپ
    $(document).on('submit', '#cpp-edit-product-form, #cpp-edit-category-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var action = form.attr('id') === 'cpp-edit-product-form' ? 'cpp_handle_edit_product_ajax' : 'cpp_handle_edit_category_ajax';
        submitEditForm(form, { action: action });
    });

    function submitEditForm(form, ajax_data) {
        var submit_button = form.find('input[type="submit"]');
        submit_button.prop('disabled', true).val('در حال ذخیره...');
        form.find('.cpp-form-message').remove();
        var form_data = form.serialize() + '&' + $.param(ajax_data);
        $.ajax({
            url: cpp_admin_vars.ajax_url,
            type: 'POST',
            data: form_data,
            success: function(response) {
                if (response.success) {
                    form.prepend('<div class="cpp-form-message notice notice-success is-dismissible"><p>' + response.data + '</p></div>');
                    setTimeout(function(){
                         $('#cpp-edit-modal').hide();
                         window.location.reload(); 
                    }, 1000); 
                } else {
                    submit_button.prop('disabled', false).val('ذخیره تغییرات');
                    form.prepend('<div class="cpp-form-message notice notice-error is-dismissible"><p>خطا: ' + (response.data || 'خطا در به‌روزرسانی.') + '</p></div>');
                }
            },
            error: function() {
                submit_button.prop('disabled', false).val('ذخیره تغییرات');
                form.prepend('<div class="cpp-form-message notice notice-error is-dismissible"><p>خطای اتصال سرور.</p></div>');
            }
        });
    }

    // منطق بارگذاری قالب ایمیل در صفحه تنظیمات
    $('#cpp-load-email-template').on('click', function() {
        if (confirm('آیا مطمئنید؟ محتوای فعلی فیلد قالب ایمیل با قالب پیش‌فرض جایگزین خواهد شد.')) {
            var templateHtml = $('#cpp-email-template-html').html();
            // بررسی می‌کنیم که ویرایشگر TinyMCE برای این فیلد فعال است یا نه
            if (typeof tinymce !== 'undefined' && tinymce.get('cpp_email_body_template')) {
                tinymce.get('cpp_email_body_template').setContent(templateHtml.trim());
            } else {
                // اگر ویرایشگر در حالت متنی (HTML) باشد
                $('#cpp_email_body_template').val(templateHtml.trim());
            }
        }
    });
});
