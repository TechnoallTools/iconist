jQuery(document).ready(function ($) {

    $('.ic_nav li').on('click', function () {
        var tab = $(this).data('tab');

        $('.ic_nav li').removeClass('active');
        $(this).addClass('active');

        $('.ic_tab_content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    var $overlay = $('#iconist_modal_overlay');
    var $msg     = $('#iconist_modal_msg');

    function showModal(message, onConfirm) {
        $msg.text(message);
        $overlay.fadeIn(200);

        $('#iconist_modal_confirm').off('click').one('click', function () {
            $overlay.fadeOut(200);
            onConfirm();
        });

        $('#iconist_modal_cancel').off('click').one('click', function () {
            $overlay.fadeOut(200);
        });
    }

    $overlay.on('click', function (e) {
        if ($(e.target).is($overlay)) {
            $overlay.fadeOut(200);
        }
    });

    
    var i18n          = iconistAjax.i18n;
    var isConverting  = false;
    var totalSVGs     = 0;
    var converted     = 0;
    var failCount     = 0;
    var logEntries    = [];

    var $bulkBtn      = $('#iconist_bulk_btn');
    var $progressWrap = $('#iconist_progress_wrap');
    var $progressText = $('#iconist_progress_text');
    var $progressPct  = $('#iconist_progress_pct');
    var $progressFill = $('#iconist_progress_fill');
    var $logWrap      = $('#iconist_log');
    var $logBody      = $('#iconist_log_body');
    var $doneMsg      = $('#iconist_done_msg');

    $bulkBtn.on('click', function (e) {
        e.preventDefault();
        if (isConverting) return;
        showModal(i18n.confirm_msg, startBulkConvert);
    });

    function startBulkConvert() {
        isConverting = true;
        converted    = 0;
        failCount    = 0;
        logEntries   = [];

        $bulkBtn.prop('disabled', true).html(
            '<svg style="animation:ic_spin 1s linear infinite;width:16px;height:16px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> ' +
            i18n.processing
        );

        $progressWrap.slideDown(200);
        $progressFill.css('width', '0%');
        $progressText.text(i18n.calculating);
        $progressPct.text('');
        $doneMsg.hide();
        $logBody.html('');
        $logWrap.slideDown(200);

        $.ajax({
            url:  iconistAjax.ajaxurl,
            type: 'POST',
            data: {
                action:   'iconist_get_svg_count',
                security: iconistAjax.security,
            },
            success: function (res) {
                if (res.success) {
                    totalSVGs = parseInt(res.data.count, 10);
                    if (totalSVGs === 0) {
                        addLog('info', i18n.no_files);
                        finishConversion();
                    } else {
                        addLog('info', totalSVGs + ' ' + i18n.found);
                        updateProgress();
                        convertNext(0);
                    }
                } else {
                    handleError(res.data || 'Error counting SVGs');
                }
            },
            error: function (xhr, status, err) {
                handleError('Connection failed: ' + err);
            }
        });
    }

    function convertNext(offset) {
        if (!isConverting) return;

        $.ajax({
            url:  iconistAjax.ajaxurl,
            type: 'POST',
            data: {
                action:   'iconist_bulk_convert_single',
                security: iconistAjax.security,
                offset:   offset,
            },
            success: function (res) {
                failCount = 0;

                if (res.success) {
                    if (res.data.log && res.data.log.length > 0) {
                        $.each(res.data.log, function (i, entry) {
                            addLog(entry.type, entry.text);
                        });
                    }

                    if (res.data.more_images) {
                        converted++;
                        updateProgress();
                        convertNext(offset + 1);
                    } else {
                        converted = totalSVGs;
                        updateProgress();
                        finishConversion();
                    }
                } else {
                    handleError(res.data || 'Unknown error');
                }
            },
            error: function (xhr, status, err) {
                failCount++;
                if (failCount <= 3) {
                    addLog('error', i18n.net_error + ' (' + failCount + '/3). ' + i18n.retry);
                    setTimeout(function () { convertNext(offset); }, 2000);
                } else {
                    handleError('Stopped after 3 repeated network errors.');
                }
            }
        });
    }

    function updateProgress() {
        if (totalSVGs <= 0) return;
        var pct = Math.min(Math.round((converted / totalSVGs) * 100), 100);
        $progressFill.css('width', pct + '%');
        $progressText.text(i18n.converting + ' ' + converted + ' / ' + totalSVGs);
        $progressPct.text(pct + '%');
    }

    function addLog(type, text) {
        var icons = {
            success: '<svg class="ic_log_icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
            error:   '<svg class="ic_log_icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
            info:    '<svg class="ic_log_icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>',
        };

        logEntries.push({ type: type, text: text });

        var show = logEntries.slice(-150);
        var html = '';
        $.each(show, function (i, e) {
            html += '<div class="ic_log_line ' + e.type + '">' + (icons[e.type] || '') + '<span>' + e.text + '</span></div>';
        });
        $logBody.html(html);
        $logBody.scrollTop($logBody[0].scrollHeight);
    }

    function finishConversion() {
        isConverting = false;
        $progressFill.css('width', '100%');
        $progressText.text(i18n.done);
        $progressPct.text('100%');
        $doneMsg.slideDown(200);
        $bulkBtn.prop('disabled', false).html(
            '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.57996 5.15991H17.42C19.08 5.15991 20.42 6.49991 20.42 8.15991V11.4799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M6.73996 2L3.57996 5.15997L6.73996 8.32001" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M20.42 18.84H6.57996C4.91996 18.84 3.57996 17.5 3.57996 15.84V12.52" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M17.26 21.9999L20.42 18.84L17.26 15.6799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path></svg> ' +
            i18n.convert_all
        );
    }

    function handleError(msg) {
        isConverting = false;
        addLog('error', msg);
        $bulkBtn.prop('disabled', false).html(
            '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.57996 5.15991H17.42C19.08 5.15991 20.42 6.49991 20.42 8.15991V11.4799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M6.73996 2L3.57996 5.15997L6.73996 8.32001" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M20.42 18.84H6.57996C4.91996 18.84 3.57996 17.5 3.57996 15.84V12.52" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M17.26 21.9999L20.42 18.84L17.26 15.6799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path></svg> ' +
            i18n.convert_all
        );
    }

    $('#iconist_clear_log').on('click', function () {
        logEntries = [];
        $logBody.html('');
    });

    var spinStyle = document.createElement('style');
    spinStyle.innerHTML = '@keyframes ic_spin { to { transform: rotate(360deg); } }';
    document.head.appendChild(spinStyle);

var mediaFrame;

function buildExceptionRow( id, filename ) {
    return '<div class="ic_exception_row" data-id="' + id + '">' +
        '<div class="ic_exception_info">' +
        '<span class="ic_exception_icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.67 14.3L21.27 19.3C21.12 20.83 21 22 18.29 22H5.71001C3.00001 22 2.88001 20.83 2.73001 19.3L2.33001 14.3C2.25001 13.47 2.51001 12.7 2.98001 12.11C2.99001 12.1 2.99001 12.1 3.00001 12.09C3.55001 11.42 4.38001 11 5.31001 11H18.69C19.62 11 20.44 11.42 20.98 12.07C20.99 12.08 21 12.09 21 12.1C21.49 12.69 21.76 13.46 21.67 14.3Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"></path><path d="M3.5 11.43V6.28003C3.5 2.88003 4.35 2.03003 7.75 2.03003H9.02C10.29 2.03003 10.58 2.41003 11.06 3.05003L12.33 4.75003C12.65 5.17003 12.84 5.43003 13.69 5.43003H16.24C19.64 5.43003 20.49 6.28003 20.49 9.68003V11.47" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.42993 17H14.5699" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>' +
        '<span class="ic_exception_name">' + filename + '</span>' +
        '</div>' +
        '<button class="ic_exception_remove_btn ic_btn ic_btn_ghost" data-id="' + id + '">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 5.97998C17.67 5.64998 14.32 5.47998 10.98 5.47998C9 5.47998 7.02 5.57998 5.04 5.77998L3 5.97998" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M8.5 4.97L8.72 3.66C8.88 2.71 9 2 10.69 2H13.31C15 2 15.13 2.75 15.28 3.67L15.5 4.97" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M18.85 9.14001L18.2 19.21C18.09 20.78 18 22 15.21 22H8.79002C6.00002 22 5.91002 20.78 5.80002 19.21L5.15002 9.14001" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M10.33 16.5H13.66" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path><path d="M9.5 12.5H14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>' +
        ' حذف</button></div>';
}

$('#iconist_add_exception_btn').on('click', function(e) {
    e.preventDefault();

    if ( mediaFrame ) {
        mediaFrame.open();
        return;
    }

    mediaFrame = wp.media({
        title:    'انتخاب فایل SVG برای مستثنی کردن از تبدیل',
        button:   { text: 'افزودن به استثناها' },
        library:  { type: 'image/svg+xml' },
        multiple: false,
    });

    mediaFrame.on('open', function() {
        document.cookie = 'iconist_exception_upload=' + iconistAjax.exception_nonce + '; path=/; SameSite=Strict';

        wp.Uploader.prototype.success = function( attachment ) {
            $.ajax({
                url:  iconistAjax.ajaxurl,
                type: 'POST',
                data: {
                    action:        'iconist_add_exception',
                    security:      iconistAjax.security,
                    attachment_id: attachment.get('id'),
                },
                success: function( res ) {
                    if ( res.success ) {
                        var f = attachment.get('filename') || attachment.get('url').split('/').pop();
                        var row = buildExceptionRow( attachment.get('id'), f );
                        $('#iconist_exceptions_list .ic_empty_msg').remove();
                        $('#iconist_exceptions_list').append(row);
                    }
                }
            });
        };
    });

    mediaFrame.on('close', function() {
        document.cookie = 'iconist_exception_upload=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Strict';
        wp.Uploader.prototype.success = wp.Uploader.prototype._originalSuccess || function(){};
    });

    mediaFrame.on('select', function() {
        var attachment = mediaFrame.state().get('selection').first().toJSON();

        $.ajax({
            url:  iconistAjax.ajaxurl,
            type: 'POST',
            data: {
                action:        'iconist_add_exception',
                security:      iconistAjax.security,
                attachment_id: attachment.id,
            },
            success: function(res) {
                if ( res.success ) {
                    var row = buildExceptionRow( attachment.id, attachment.filename );
                    $('#iconist_exceptions_list .ic_empty_msg').remove();
                    $('#iconist_exceptions_list').append(row);
                }
            }
        });
    });

    mediaFrame.open();
});

$(document).on('click', '.ic_exception_remove_btn', function() {
    var $row = $(this).closest('.ic_exception_row');
    var id   = $(this).data('id');

    $.ajax({
        url:  iconistAjax.ajaxurl,
        type: 'POST',
        data: {
            action:        'iconist_remove_exception',
            security:      iconistAjax.security,
            attachment_id: id,
        },
        success: function(res) {
            if ( res.success ) {
                $row.fadeOut(200, function() {
                    $(this).remove();
                    if ( $('#iconist_exceptions_list .ic_exception_row').length === 0 ) {
                        $('#iconist_exceptions_list').html('<p class="ic_empty_msg">هیچ استثنایی تعریف نشده.</p>');
                    }
                });
            }
        }
    });
});

});
