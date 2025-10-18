jQuery(document).ready(function ($) {

    // آکاردئون
    $('.cpp-accordion-header').on('click', function () {
        $(this).toggleClass('active').next('.cpp-accordion-content').slideToggle(300);
    });
    // بستن آکاردئون‌ها در بارگذاری اولیه صفحه (مگر اینکه خطایی داخلشان باشد)
    if ($('.cpp-accordion-content').length && !$('.cpp-accordion-content').find('.error').length && !$('.cpp-accordion-content').is(':visible')) {
       // $('.cpp-accordion-content').hide(); // Don't hide if it contains server-side rendered errors
       // $('.cpp-accordion-header').removeClass('active');
    }
     // If hash exists, open corresponding accordion (useful for redirects with errors)
    if (window.location.hash) {
        var targetAccordion = $(window.location.hash);
        if (targetAccordion.hasClass('cpp-accordion-content')) {
            targetAccordion.show();
            targetAccordion.prev('.cpp-accordion-header').addClass('active');
        }
    }


    // مدیریت آپلود عکس
    var mediaUploader;
    $(document).on('click', '.cpp-upload-btn', function (e) {
        e.preventDefault();
        var button = $(this);
        // Try finding input by data attribute first, then sibling, then parent structure
        var inputId = button.data("input-id");
        var input_field = inputId ? jQuery("#" + inputId) : button.siblings('input[type="text"]');
        if (!input_field.length) { // Try finding in parent's sibling if structure is different
             input_field = button.closest('td').find('input[type="text"]');
        }

        // Find preview container relative to the button/input
        var preview_img_container = button.closest('td').find(".cpp-image-preview");
        if (!preview_img_container.length) { // Fallback for settings page structure
             preview_img_container = button.closest('.cpp-image-uploader-wrapper').find(".cpp-image-preview");
        }
        if (!preview_img_container.length) { // Fallback if still not found
            preview_img_container = button.parent().find(".cpp-image-preview");
        }

        if (!input_field.length) {
            console.error("Could not find the target input field for the media uploader.");
            return; // Exit if input field not found
        }


        // Reinitialize the media frame each time to avoid state issues
        mediaUploader = wp.media({
            title: 'انتخاب یا آپلود تصویر',
            button: { text: 'استفاده از این تصویر' },
            multiple: false
        });

        // Use a closure to ensure the correct input_field and preview_img_container are used
        (function(target_input, target_preview) {
            mediaUploader.off('select'); // Remove previous select handlers
            mediaUploader.on('select', function () {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                target_input.val(attachment.url).trigger('change'); // Trigger change for potential listeners
                target_preview.html('<img src="' + attachment.url + '" style="max-width: 100px; height: auto; margin-top: 10px;">');
            });
            mediaUploader.open();
        })(input_field, preview_img_container);

    });


    // ویرایش سریع با دبل کلیک
    $(document).on('dblclick', '.cpp-quick-edit, .cpp-quick-edit-select', function () {
        var cell = $(this);
        // Check if the cell itself or its parent TD is already being edited
        if (cell.hasClass('editing') || cell.closest('td').hasClass('editing-td')) return;

        var id = cell.data('id'), field = cell.data('field'), table_type = cell.data('table-type');
        var original_html = cell.html(); // Store original HTML of the span/cell

        // For simple text, get text. For complex HTML (like price range spans), store original.
        var original_text_content = cell.clone().children().remove().end().text().trim();


        var input_element;
        var target_element = cell; // By default, replace the cell itself

        if (cell.hasClass('cpp-quick-edit-select')) {
             cell.data('original-content', original_html).addClass('editing');
            var current_value = cell.data('current');
            input_element = $('<select>').addClass('cpp-quick-edit-input');
            var options_list = (table_type === 'orders') ? cpp_admin_vars.order_statuses : (typeof cppStatusOptions !== 'undefined' ? cppStatusOptions : {});
            $.each(options_list, function (val, text) {
                $('<option>').val(val).text(text).prop('selected', val == current_value).appendTo(input_element);
            });
        } else if (field === 'min_price' || field === 'max_price') {
             // Handle combined price range editing - target the parent TD
             var td = cell.closest('td');
             if (td.hasClass('editing-td')) return; // Already editing this cell group
             td.addClass('editing-td'); // Add editing class to parent TD
             target_element = td; // Replace the entire TD content

             var min_span = td.find('[data-field="min_price"]');
             var max_span = td.find('[data-field="max_price"]');
             td.data('original-content', td.html()); // Store original TD HTML

             var min_val = min_span.text().trim();
             var max_val = max_span.text().trim();

             var container = $('<div>');
             var min_input = $('<input type="text">').addClass('cpp-quick-edit-input small-text').val(min_val).data('field', 'min_price');
             var max_input = $('<input type="text">').addClass('cpp-quick-edit-input small-text').val(max_val).data('field', 'max_price');
             container.append(min_input).append(' - ').append(max_input);
             input_element = container; // Use the container as the main element

             // Adjust cell width if needed
             td.css('width', 'auto'); // Let it expand


        } else {
             cell.data('original-content', original_html).addClass('editing');
            var input_type = (field === 'admin_note' || field === 'description') ? 'textarea' : 'text';
            // Use original_text_content which is just the text
            input_element = $(`<${input_type}>`).addClass('cpp-quick-edit-input').val(original_text_content);
        }

        var save_btn = $('<button>').addClass('button button-primary button-small').text('ذخیره');
        var cancel_btn = $('<button>').addClass('button button-secondary button-small').text('لغو').css('margin-right', '5px');
        var buttons = $('<div>').addClass('cpp-quick-edit-buttons').css('margin-top', '5px').append(save_btn).append(cancel_btn);

        // Clear the target element and append the input/buttons
        target_element.html('').append(input_element).append(buttons);

        // Focus the first input element if it exists
        input_element.find('input, select, textarea').first().focus();

        save_btn.on('click', function () {
             if (field === 'min_price' || field === 'max_price') {
                 performSavePriceRange(td, id, table_type);
             } else {
                 performSave(cell, id, field, table_type);
             }
         });
        cancel_btn.on('click', function () {
             if (field === 'min_price' || field === 'max_price') {
                 td.removeClass('editing-td').html(td.data('original-content'));
             } else {
                 cell.removeClass('editing').html(cell.data('original-content'));
             }
         });
        // Keydown events for inputs within the cell/td
         $(input_element).find('input, select, textarea').on('keydown', function (e) {
            if (e.key === 'Escape') {
                cancel_btn.click();
            } else if (e.key === 'Enter' && !$(this).is('textarea')) { // Enter saves only for non-textarea
                 e.preventDefault();
                 save_btn.click();
             }
        });
    });

    // Separate save function for combined price range
    function performSavePriceRange(td, id, table_type) {
        var min_input = td.find('input[data-field="min_price"]');
        var max_input = td.find('input[data-field="max_price"]');
        var min_value = min_input.val();
        var max_value = max_input.val();
        var original_html = td.data('original-content');

        td.html('در حال ذخیره...'); // Show saving message in the TD

        // Send two separate AJAX requests or modify the backend to accept both
        var promise1 = $.post(cpp_admin_vars.ajax_url, {
            action: 'cpp_quick_update', security: cpp_admin_vars.nonce, id: id, field: 'min_price', value: min_value, table_type: table_type
        });
        var promise2 = $.post(cpp_admin_vars.ajax_url, {
            action: 'cpp_quick_update', security: cpp_admin_vars.nonce, id: id, field: 'max_price', value: max_value, table_type: table_type
        });

        // Wait for both requests to complete
        $.when(promise1, promise2).done(function (res1, res2) {
            td.removeClass('editing-td'); // Remove editing class here
            var response1 = res1[0]; // Response from promise1
            var response2 = res2[0]; // Response from promise2

            if (response1.success && response2.success) {
                // Manually recreate the HTML structure after successful save
                var new_min_span = $('<span>').addClass('cpp-quick-edit').attr('data-id', id).attr('data-field', 'min_price').attr('data-table-type', table_type).text(min_value);
                var new_max_span = $('<span>').addClass('cpp-quick-edit').attr('data-id', id).attr('data-field', 'max_price').attr('data-table-type', table_type).text(max_value);
                td.html('').append(new_min_span).append(' - ').append(new_max_span); // Rebuild content

                // Update last updated time if provided by either response
                if (response1.data.new_time || response2.data.new_time) {
                    td.closest('tr').find('.cpp-last-update').text(response1.data.new_time || response2.data.new_time);
                }

            } else {
                 var errorMsg = 'خطا در ذخیره بازه قیمت:';
                 if (!response1.success && response1.data && response1.data.message) errorMsg += '\nحداقل: ' + response1.data.message;
                 if (!response2.success && response2.data && response2.data.message) errorMsg += '\nحداکثر: ' + response2.data.message;
                 alert(errorMsg);
                 td.html(original_html); // Restore original on error
            }
        }).fail(function (jqXHR1, textStatus1, errorThrown1, jqXHR2, textStatus2, errorThrown2) {
             td.removeClass('editing-td'); // Remove editing class on fail too
             alert('خطای سرور در ذخیره بازه قیمت.');
             td.html(original_html); // Restore original on server error
        });
    }


    // Original save function for single fields
    function performSave(cell, id, field, table_type) {
        var inputField = cell.find('.cpp-quick-edit-input');
        var new_value = inputField.val();
        var original_html = cell.data('original-content'); // Get original HTML

        cell.html('در حال ذخیره...'); // Show saving message in the cell
        $.post(cpp_admin_vars.ajax_url, {
            action: 'cpp_quick_update', security: cpp_admin_vars.nonce, id: id, field: field, value: new_value, table_type: table_type
        }, function (response) {
            cell.removeClass('editing'); // Remove editing class here
            if (response.success) {
                var display_html;
                if (cell.hasClass('cpp-quick-edit-select')) {
                    var options_list = (table_type === 'orders') ? cpp_admin_vars.order_statuses : (typeof cppStatusOptions !== 'undefined' ? cppStatusOptions : {});
                    var display_text = options_list[new_value] || new_value; // Fallback to value if text not found
                    cell.data('current', new_value); // Update current value data attribute
                    cell.html(display_text); // Just update the text
                } else {
                     display_html = new_value.replace(/\n/g, '<br>');
                     cell.html(display_html); // Update cell content for simple text/textarea
                }

                // Update last updated time if applicable
                if (response.data.new_time) {
                    cell.closest('tr').find('.cpp-last-update').text(response.data.new_time);
                }
            } else {
                 // Use message from response if available
                 var errorMsg = (response.data && response.data.message) ? response.data.message : 'خطای نامشخص';
                 alert('خطا: ' + errorMsg);
                 cell.html(original_html); // Restore original HTML on error
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
             cell.removeClass('editing'); // Remove editing class on fail too
             alert('خطای سرور: ' + textStatus);
             cell.html(original_html); // Restore original HTML on server error
        });
    }


    // منطق پاپ‌آپ ویرایش
    $(document).on('click', '.cpp-edit-button, .cpp-edit-cat-button', function () {
        var button = $(this);
        var ajax_data = { security: cpp_admin_vars.nonce }; // Add nonce here
        if (button.hasClass('cpp-edit-button')) {
            ajax_data.action = 'cpp_fetch_product_edit_form';
            ajax_data.id = button.data('product-id');
        } else {
            ajax_data.action = 'cpp_fetch_category_edit_form';
            ajax_data.id = button.data('cat-id');
        }
        openEditModal(ajax_data);
    });

   function openEditModal(ajax_data) {
        // Create modal if it doesn't exist
        if ($('#cpp-edit-modal').length === 0) {
            $('body').append('<div id="cpp-edit-modal" class="cpp-modal-overlay" style="display: none;"><div class="cpp-modal-container"><span class="cpp-close-modal">×</span><div class="cpp-edit-modal-content"></div></div></div>');
        }

        var modal = $('#cpp-edit-modal');
        var modalContent = modal.find('.cpp-edit-modal-content');

        modal.addClass('loading').show();
        modalContent.html('<p style="text-align:center; padding: 20px;">در حال بارگذاری فرم ویرایش...</p>');
        // Nonce is already added to ajax_data

        $.get(cpp_admin_vars.ajax_url, ajax_data) // Use GET for fetching forms
            .done(function (response) {
                modal.removeClass('loading');
                if (response.success && response.data && response.data.html) {
                    modalContent.html(response.data.html);
                    // Initialize media uploader specifically for the modal content
                    if (typeof window.cpp_init_media_uploader === 'function') {
                       window.cpp_init_media_uploader();
                    }
                     // Initialize color pickers if they exist in the modal
                     if (modalContent.find('.cpp-color-picker').length > 0) {
                         modalContent.find('.cpp-color-picker').wpColorPicker();
                     }
                } else {
                     var errorMessage = (response.data && (response.data.message || response.data.html || response.data)) || 'خطای نامشخص در بارگذاری فرم.';
                     modalContent.html('<p style="color:red; text-align:center; padding: 20px;">' + errorMessage + '</p>');
                     console.error("Error loading edit form:", response);
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                 modal.removeClass('loading');
                 modalContent.html('<p style="color:red; text-align:center; padding: 20px;">خطای اتصال سرور: ' + textStatus + ' - ' + errorThrown + '</p>');
                 console.error("AJAX error loading edit form:", textStatus, errorThrown, jqXHR);
            });
    }


    // Close modal logic
    $(document).on('click', '#cpp-edit-modal .cpp-close-modal', function () {
        $('#cpp-edit-modal').hide();
    });
    // Close modal if clicking outside the container
    $(document).on('click', '#cpp-edit-modal', function(e) {
        if ($(e.target).is('#cpp-edit-modal')) {
            $(this).hide();
        }
    });


    // نمایش نمودار
    var chartInstance = null;
    $(document).on('click', '.cpp-show-chart', function (e) {
        e.preventDefault();
        var productId = $(this).data('product-id');

        // Create chart modal if it doesn't exist
        if ($('#cpp-chart-modal').length === 0) {
             $('body').append('<div id="cpp-chart-modal" class="cpp-modal-overlay" style="display:none;"><div class="cpp-modal-container"><span class="cpp-close-modal">×</span><h2>نمودار تغییرات قیمت</h2><div class="cpp-chart-modal-content"><canvas id="cppPriceChart" width="400" height="150"></canvas></div></div></div>');
        }

        var modal = $('#cpp-chart-modal');
        var chartCanvas = modal.find('#cppPriceChart');
        var modalContent = modal.find('.cpp-chart-modal-content');


        modal.show();
        modalContent.find('.chart-error, .chart-loading').remove(); // Remove previous errors/loading
        chartCanvas.show(); // Ensure canvas is visible

        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null; // Reset instance
        }
         // Add a loading indicator
         modalContent.append('<p class="chart-loading" style="text-align:center;">در حال بارگذاری داده...</p>');


        $.get(cpp_admin_vars.ajax_url, { action: 'cpp_get_chart_data', product_id: productId, security: cpp_admin_vars.nonce }, function (response) { // Use GET and add nonce
            modalContent.find('.chart-loading').remove(); // Remove loading indicator
            if (response.success && response.data && response.data.labels && response.data.labels.length > 0) {
                 renderChart(response.data, chartCanvas[0]);
             } else {
                 chartCanvas.hide();
                 var errorMsg = (response.data && typeof response.data === 'string') ? response.data : 'تاریخچه قیمت برای این محصول در دسترس نیست یا خطایی رخ داده.';
                 modalContent.prepend('<p class="chart-error" style="color:red; text-align:center;">' + errorMsg + '</p>');
                 console.error("Chart data error:", response);
             }
        }).fail(function (jqXHR, textStatus, errorThrown) {
             modalContent.find('.chart-loading').remove(); // Remove loading indicator
             chartCanvas.hide();
             modalContent.prepend('<p class="chart-error" style="color:red; text-align:center;">خطا در بارگذاری داده‌های نمودار: ' + textStatus + '</p>');
        });
    });

     // Close chart modal logic
    $(document).on('click', '#cpp-chart-modal .cpp-close-modal', function () {
        $('#cpp-chart-modal').hide();
         if (chartInstance) {
            chartInstance.destroy(); // Destroy chart when closing
            chartInstance = null;
        }
    });
    $(document).on('click', '#cpp-chart-modal', function(e) {
        if ($(e.target).is('#cpp-chart-modal')) {
            $(this).hide();
             if (chartInstance) {
                chartInstance.destroy(); // Destroy chart when closing
                chartInstance = null;
            }
        }
    });


    function renderChart(chartData, ctx) {
        var datasets = [];
        // Add Base Price if available
        if (chartData.prices && chartData.prices.length > 0) {
            datasets.push({
                label: 'قیمت پایه', data: chartData.prices, borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)', tension: 0.3, fill: false, borderWidth: 2 // Slightly thinner line
             });
        }
         // Add Min Price if available
        if (chartData.min_prices && chartData.min_prices.length > 0) {
            datasets.push({
                label: 'حداقل قیمت', data: chartData.min_prices, borderColor: 'rgba(255, 99, 132, 0.7)', // Less opaque
                backgroundColor: 'rgba(255, 99, 132, 0.1)', tension: 0, borderDash: [5, 5], fill: '+1', // Fill to next dataset (max price if exists, or axis)
                 pointRadius: 0, borderWidth: 1
             });
        }
        // Add Max Price if available
        if (chartData.max_prices && chartData.max_prices.length > 0) {
            // Determine fill based on whether min_prices exists
            //var fillTarget = (chartData.min_prices && chartData.min_prices.length > 0) ? '-1' : 'origin'; // Fill to previous dataset (min) or origin
            var fillTarget = false; // Generally better not to fill the top line unless intended as area chart


            datasets.push({
                label: 'حداکثر قیمت', data: chartData.max_prices, borderColor: 'rgba(54, 162, 235, 0.7)', // Less opaque
                backgroundColor: 'rgba(54, 162, 235, 0.1)', tension: 0, borderDash: [5, 5], fill: fillTarget,
                 pointRadius: 0, borderWidth: 1
             });
        }

        // Ensure ctx is a valid canvas context
         if (!ctx || typeof ctx.getContext !== 'function') {
             console.error("Invalid canvas context provided for chart rendering.");
              $('#cpp-chart-modal .cpp-chart-modal-content').prepend('<p class="chart-error" style="color:red; text-align:center;">خطا در آماده سازی نمودار.</p>');
             return;
         }

        try {
            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Allow chart to fill container height
                    scales: {
                        y: {
                            beginAtZero: false, // Don't force y-axis to start at 0
                            title: { display: true, text: 'قیمت' }
                        },
                        x: {
                             title: { display: true, text: 'تاریخ' }
                        }
                    },
                    plugins: {
                        tooltip: {
                             mode: 'index', // Show tooltip for all datasets at that index
                             intersect: false, // Tooltip appears even if not directly hovering point
                        },
                        legend: {
                            position: 'top',
                        },
                    },
                     hover: {
                        mode: 'nearest', // Highlight nearest point
                        intersect: true // Only trigger hover when directly over point
                    }
                }
            });
        } catch (e) {
             console.error("Error creating chart:", e);
              $('#cpp-chart-modal .cpp-chart-modal-content').prepend('<p class="chart-error" style="color:red; text-align:center;">خطا در رسم نمودار.</p>');
               $(ctx).hide();
        }
    }


    // مدیریت ذخیره فرم‌های پاپ آپ ویرایش (محصول و دسته بندی)
    $(document).on('submit', '#cpp-edit-product-form, #cpp-edit-category-form', function (e) {
        e.preventDefault();
        var form = $(this);
        var action = form.attr('id') === 'cpp-edit-product-form' ? 'cpp_handle_edit_product_ajax' : 'cpp_handle_edit_category_ajax';
        var submit_button = form.find('input[type="submit"]');
        var originalButtonText = submit_button.val();

        submit_button.prop('disabled', true).val('در حال ذخیره...');
        form.find('.cpp-form-message').remove(); // Remove previous messages

        // Serialize form data including the action
        var form_data = form.serializeArray();
        form_data.push({ name: 'action', value: action });
        // Nonce should be included in the form's HTML output by wp_nonce_field

        $.ajax({
            url: cpp_admin_vars.ajax_url,
            type: 'POST',
            data: $.param(form_data), // Use $.param to correctly encode array data
            success: function (response) {
                 // Check if response is likely valid JSON containing success status
                if (response && typeof response.success !== 'undefined') {
                    if (response.success) {
                        // Use message from response.data.message if exists, otherwise use response.data
                        var successMsg = (response.data && response.data.message) ? response.data.message : (response.data || 'با موفقیت ذخیره شد.');
                        form.prepend('<div class="cpp-form-message notice notice-success is-dismissible"><p>' + successMsg + '</p></div>');
                        setTimeout(function () {
                            $('#cpp-edit-modal').hide();
                            window.location.reload(); // Reload page to reflect changes in the table
                        }, 1500); // Slightly longer delay
                    } else {
                        submit_button.prop('disabled', false).val(originalButtonText);
                        var errorMessage = (response.data && response.data.message) ? response.data.message : (response.data || 'خطا در به‌روزرسانی.');
                        form.prepend('<div class="cpp-form-message notice notice-error is-dismissible"><p>خطا: ' + errorMessage + '</p></div>');
                    }
                 } else {
                     // Handle unexpected non-JSON or malformed success response
                     submit_button.prop('disabled', false).val(originalButtonText);
                     form.prepend('<div class="cpp-form-message notice notice-error is-dismissible"><p>پاسخ غیرمنتظره از سرور.</p></div>');
                     console.error("Unexpected success response format:", response);
                 }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                 submit_button.prop('disabled', false).val(originalButtonText);
                 var errorDetail = jqXHR.responseText || errorThrown; // Get more detail if possible
                 form.prepend('<div class="cpp-form-message notice notice-error is-dismissible"><p>خطای اتصال سرور: ' + textStatus + '<br><small>' + errorDetail + '</small></p></div>');
                 console.error("AJAX Error saving form:", textStatus, errorThrown, jqXHR);
            }
        });
    });


    // منطق بارگذاری قالب ایمیل در صفحه تنظیمات
    $('#cpp-load-email-template').on('click', function () {
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

    // منطق تست ارسال ایمیل
    $('#cpp-test-email-btn').on('click', function () {
        var button = $(this);
        var logBox = $('#cpp-email-log');
        var originalButtonText = button.text(); // ذخیره متن اصلی

        button.prop('disabled', true).text('در حال ارسال...');
        logBox.val('در حال آماده‌سازی برای ارسال ایمیل آزمایشی...');
        logBox.css('color', 'black'); // Reset color


        $.post(cpp_admin_vars.ajax_url, {
            action: 'cpp_test_email',
            security: cpp_admin_vars.nonce // Ensure nonce is sent
        }, function (response) {
            // اطمینان از اینکه پاسخ ساختار مورد انتظار را دارد
            if (response && typeof response.success !== 'undefined' && response.data && typeof response.data.log !== 'undefined') {
                logBox.val(response.data.log);
                logBox.css('color', response.success ? 'green' : 'red');
            } else {
                logBox.val('پاسخ نامعتبر از سرور دریافت شد.\nResponse:\n' + JSON.stringify(response, null, 2));
                 logBox.css('color', 'red');
                 console.error("Invalid AJAX response structure for email test:", response);
            }
            button.prop('disabled', false).text(originalButtonText); // بازگرداندن متن اصلی
        }).fail(function (jqXHR, textStatus, errorThrown) {
            logBox.val('خطای شدید سرور (AJAX Error): ' + textStatus + ' (' + jqXHR.status + ') - ' + errorThrown + '\n' + jqXHR.responseText + '\nممکن است مشکل از سمت سرور یا تداخل افزونه‌ها باشد.');
            logBox.css('color', 'red');
            button.prop('disabled', false).text(originalButtonText); // بازگرداندن متن اصلی
            console.error("AJAX Error email test:", jqXHR);
        });
    });

    // --- منطق تست ارسال پیامک (نهایی با لاگ در کنسول) ---
    $('#cpp-test-sms-btn').on('click', function () {
        console.log('Test SMS button clicked.'); // Debug Step 1
        var button = $(this);
        var logBox = $('#cpp-sms-log');
        var originalButtonText = button.text(); // ذخیره متن اصلی دکمه

        button.prop('disabled', true).text('در حال ارسال...');
        logBox.val('در حال آماده‌سازی برای ارسال پیامک آزمایشی...');
        logBox.css('color', 'black'); // Reset color
        console.log('Sending AJAX request for cpp_test_sms...'); // Debug Step 2

        $.post(cpp_admin_vars.ajax_url, {
            action: 'cpp_test_sms', // اکشن صحیح برای تست پیامک
            security: cpp_admin_vars.nonce // ارسال Nonce برای امنیت
        }, function (response) {
             console.log('AJAX Success:', response); // Debug Step 3 - Log success response
             // اطمینان از اینکه پاسخ ساختار مورد انتظار را دارد (success و data.log)
            if (response && typeof response.success !== 'undefined' && response.data && typeof response.data.log !== 'undefined') {
                logBox.val(response.data.log); // نمایش لاگ از response.data.log
                logBox.css('color', response.success ? 'green' : 'red'); // سبز برای موفقیت, قرمز برای خطا
            } else {
                 // اگر ساختار پاسخ اشتباه بود
                 logBox.val('پاسخ نامعتبر از سرور دریافت شد (ساختار JSON اشتباه است).\nResponse:\n' + JSON.stringify(response, null, 2));
                 logBox.css('color', 'red');
                 console.error("Invalid AJAX response structure for SMS test:", response);
            }
            button.prop('disabled', false).text(originalButtonText); // بازگرداندن متن اصلی دکمه
        }).fail(function (jqXHR, textStatus, errorThrown) {
            console.error('AJAX Fail:', jqXHR, textStatus, errorThrown); // Debug Step 4 - Log failure response
             // اگر خود درخواست AJAX با خطا مواجه شد (مثلا خطای 500 سرور یا 403)
             var errorDetails = jqXHR.responseText;
             var readableError = 'خطای شدید سرور (AJAX Error): ' + textStatus + ' (' + jqXHR.status + ') - ' + errorThrown;

             // Try to parse JSON error response from wp_send_json_error
             try {
                var errorObj = JSON.parse(jqXHR.responseText);
                // Check if our specific log structure exists in the error data
                if(errorObj && errorObj.data && errorObj.data.log){
                    readableError += '\n--- Server Log ---\n' + errorObj.data.log;
                } else if (errorObj && errorObj.data) {
                     // Show generic data if log isn't there
                     readableError += '\n--- Server Data ---\n' + JSON.stringify(errorObj.data, null, 2);
                } else {
                    readableError += '\n--- Raw Response ---\n' + jqXHR.responseText;
                }
             } catch(e) {
                 // Response wasn't JSON, show raw text but limit length
                 readableError += '\n--- Raw Response (first 500 chars) ---\n' + (jqXHR.responseText ? jqXHR.responseText.substring(0, 500) : 'Empty response');
             }

             readableError += '\n\nممکن است مشکل از سمت سرور (خطای PHP)، تنظیمات نادرست یا تداخل افزونه‌ها باشد.';
             logBox.val(readableError);
             logBox.css('color', 'red');
             button.prop('disabled', false).text(originalButtonText); // بازگرداندن متن اصلی دکمه
             console.error("AJAX Error SMS test:", jqXHR.status, textStatus, errorThrown, jqXHR);
        });
    }); // End SMS Test Button Click Handler


}); // End jQuery ready
