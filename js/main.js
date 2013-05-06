/**
 * dependency: jQuery, reCAPTCHA, ligo
 */

'use strict';

jQuery(function($) {

    $(window).bind('hashchange', function(){
        var _group = window.location.hash.substring(1);
        if (ligo.group.toLowerCase() != _group.toLowerCase()) {
            ligo.data = null;
            ligo.group = _group;
            ligo.renderStats().renderGraph();
        }
    });

    $(document).ajaxStart(function(){$('.brand').tooltip('show')}).ajaxStop(function(){$('.brand').tooltip('hide')});

    $('#newhandles').on('submit', function(e) {
        e.preventDefault();
        var _action = $(this).prop('action'),
            _data = {
                groups: $('#groups').val(),
                handles: $('#handles').val(),
                comments: $('#comments').val(),
                recaptcha_challenge: Recaptcha.get_challenge(),
                recaptcha_response: Recaptcha.get_response(),
                recaptcha_remoteip: _remote_ip
            },
            _messages = {
                'incorrect-captcha-sol': 'Incorrect answer, try again',
                'captcha-timeout': 'Connection too slow, try again',
                'recaptcha-not-reachable': 'reCAPTCHA service is not available, tray again later'
            };

        if ($.trim(_data.handles) != '' && _data.recaptcha_response != '') {
            $('#recaptcha-error').hide();
            $.post(_action, _data, function(data){
                if(data.success) {
                    // close dropdowns and clear fields
                    $('#newhandles').parents('.dropdown').removeClass('open').tooltip('show');
                    setTimeout(function(){$('#newhandles').parents('.dropdown').tooltip('hide')}, 3000);

                } else {
                    var m = (data.recaptcha.isValid) ? data.message : _messages[data.recaptcha.error] || _messages.incorrect-captcha-sol;
                    $('#recaptcha-error').html(m).show();
                }
                Recaptcha.reload();
            });
        } else {
            $('#recaptcha-error').html('Please indicate Twitter accounts and respective groups').show();
        }

        return false;
    });


});