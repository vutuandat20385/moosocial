/* Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) { 
        // AMD
        define(['jquery', 'mooOverlay', 'mooBehavior', 'mooResponsive', 'mooShare', 'mooNotification', 'mooPhoto', 'mooUser',
            'tipsy', 'autogrow', 'spinner'], factory);
    } else if (typeof exports === 'object') {
        // Node, CommonJS-like
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals (root is window)
        root.ServerJS = factory(root.jQuery);
    }
}(this, function ($, mooOverlay, mooBehavior, mooResponsive, mooShare, mooNotification, mooPhoto, mooUser) {
    
    var init = function () {
        
        $('textarea:not(.no-grow)').autogrow();

        
        $(".tip").tipsy({html: true, gravity: $.fn.tipsy.autoNS,follow: 'x'});
        
        $('.truncate').each(function () {
            if (parseInt($(this).css('height')) >= 145){
                var element = $('<a href="javascript:void(0)" class="show-more">' + $(this).data('more-text') + '</a>');
                $(this).after(element);
                element.click(function(e){
                    showMore(this);
                });
            }
        });

        $('.comment-truncate').each(function () {
            if (parseInt($(this).css('height')) >= 45){
                var element = $('<a href="javascript:void(0)" class="show-more">' + $(this).data('more-text') + '</a>');
                $(this).after(element);
                element.click(function(e){
                    showMore(this);
                });
            }
        });

        mooOverlay.registerOverlay();

        mooBehavior.registerImageComment();

        $('#keyword').keyup(function (event) {
            if (event.keyCode == '13') {
                $('#browse_all').spin('tiny');
                $('#browse .current').removeClass('current');
                $('#browse_all').addClass('current');

                var ajax_browse = 'ajax_browse';
                var ext = '';
                if ($(this).hasClass('json-view'))
                {
                    ajax_browse = 'browse';
                }
                var type = $(this).hasClass('json-view');

                var contentId = ''
                if ($(this).attr('rel') == 'albums') {
                    contentId = '#album-list-content';
                } else {
                    contentId = '#list-content';
                }

                $(contentId).load(mooConfig.url.base + '/' + $(this).attr('rel') + '/' + ajax_browse + '/search/' + encodeURI($(this).val() + ext), {noCache: 1}, function (response) {

                    $('#browse_all').spin(false);
                    $('#keyword').val('');

                    $('body').trigger('afterAjaxSearchServerJSCallback',[]);
                });
            }
        });
        
        // init cookie
        initCookieAccept();

        initSearch();
        
        // init template responsive
        mooResponsive.init();
        
        // init notification
        mooNotification.init();
        
        // init share action
        mooShare.init();
        
        // init moreResults
        mooBehavior.initMoreResults();
        
        // init photo theater
        mooPhoto.init();
        
        // init auto loadmore
        mooBehavior.initAutoLoadMore();
        
        $('#browse a:not(.overlay):not(.no-ajax)').unbind('click');
        $('#browse a:not(.overlay):not(.no-ajax)').click(function () {
            $(this).children('.badge_counter').hide();
            $(this).spin('tiny');

            $('#browse .current').removeClass('current');
            $(this).parent().addClass('current');

            var div = $(this).attr('rel');
            if (div == undefined){
                div = 'list-content';
            }

            var el = $(this);

            $('#' + div).load($(this).attr('data-url') + '?' + $.now(), function (response) {

                var res = '';
                try {
                    res = $.parseJSON(response).data;
                } catch (error) {
                    res = response
                }

                el.children('.badge_counter').fadeIn();
                el.spin(false);

                // reattach events
                $('textarea:not(.no-grow)').autogrow();
                $(".tip").tipsy({html: true, gravity: 's'});

                mooOverlay.registerOverlay();
                
                $('.truncate').each(function () {
                    if (parseInt($(this).css('height')) >= 145){
                        var element = $('<a href="javascript:void(0)" class="show-more">' + $(this).data('more-text') + '</a>');
                        $(this).after(element);
                        element.click(function(e){
                            showMore(this);
                        });
                    }
                });

                window.history.pushState({}, "", el.attr('href'));
                if ($(window).width() < 992) {
                    console.log('222');
                         $('#leftnav').modal('hide');
                        $('body').scrollTop(0);
                     }

                $('body').trigger('afterAjaxMenuServerJSCallback',[]);
            });

            return false;
        });
        
        // init resend_validation_link
        mooUser.resendValidationLink();

        //custom scroll to comment use this when ajax change the url
        $('body').append('<input type="hidden" id="url_path" value="'+window.location.pathname+'">');

    };
    
    var initCookieAccept = function(){
        
        $('.accept-cookie').unbind('click');
        $('.accept-cookie').on('click',function(){
            
            var answer = $(this).data('answer');
            var $this = $(this);
            $.post(mooConfig.url.base+'/users/accept_cookie',{answer:answer},function(data){
                data = JSON.parse(data);
                if (data.result) {
                    $('.cookies-warning').remove();
                    $('body').removeClass('page_has_cookies');
                }
                else {
                    location.href = data.url;
                }
            })
        });
        
        $('.delete-warning-cookies').unbind('click');
        $('.delete-warning-cookies').on('click',function(){
            $('.cookies-warning').remove();
            $('body').removeClass('page_has_cookies');
        });
    }
    
    var showMore = function(obj){
        
        $(obj).prev().css('max-height', 'none');
        var element = $('<a href="javascript:void(0)" class="show-more">' + $(obj).prev().data('less-text') + '</a>');
        $(obj).replaceWith(element);
        element.click(function(e){
            showLess(this);
        });
        $('body').trigger('afterShowMoreServerJSCallback',[]);
    }

    var showLess = function(obj){
        
        $(obj).prev().css('max-height', '');
        var element = $('<a href="javascript:void(0)" class="show-more">' + $(obj).prev().data('more-text') + '</a>');
        $(obj).replaceWith(element);
        element.click(function(e){
            showMore(this);
        });
        $('body').trigger('afterShowMoreServerJSCallback',[]);
    }
    
    var showFeedVideo = function(source, source_id, activity_id ){
        
        $('#video_teaser_' + activity_id + ' .vid_thumb').spin('small');
        $('#video_teaser_' + activity_id).load(mooConfig.url.base + '/videos/embed', { source: source, source_id: source_id }, function(){
            $('#video_teaser_' + activity_id + ' > .vid_thumb').spin(false);
        });
    }
    
    var initSearch = function () {
        if ($('.suggestionInitSlimScroll').height() > 500) {
            $('.suggestionInitSlimScroll').slimScroll({height: '500px'});
        }

        $('#global-search').keyup(function (event) {
            var searchVal = $(this).val();
            if (searchVal != '') {
                $.post(mooConfig.url.base + "/search/suggestion/all", {searchVal: searchVal}, function (data) {
                    $('.global-search .slimScrollDiv').show();
                    $('#display-suggestion').html(data).show();
                });
            }

            if (event.keyCode == '13') {
                if ($(this).val() != '') {
                    var searchStr = $(this).val().replace('#', '');
                    if ($(this).val().indexOf('#') > -1) {
                        window.location = mooConfig.url.base + '/search/hashtags?q=' + encodeURIComponent(searchStr);
                    } else {
                        window.location = mooConfig.url.base + '/search/index?q=' + encodeURIComponent(searchStr);
                    }
                }
            }
        });

        $('#global-search').focusout(function (event) {
            if ($('#display-suggestion').is(":hover") == false) {
                $('#display-suggestion').html('').hide();
                $('.global-search .slimScrollDiv').hide();
            }
        });

        $('#global-search').focus(function (event) {

            $('#global-search').trigger('keyup');

        });
    };
    

    //    exposed public method
    return {
        init: init,
        
    };
}));