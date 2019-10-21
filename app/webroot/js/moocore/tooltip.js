/* Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['jquery','mooPhrase','qtip'], factory);
    } else if (typeof exports === 'object') {
        // Node, CommonJS-like
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals (root is window)
        root.mooTooltip = factory();
    }
}(this, function ($,mooPhrase) {
         
     var init = function(){
      if ($('#page_share-ajax_share').length == 0 && mooConfig.profile_popup == 1 && !mooConfig.isMobile) {
    	  $('.qtip').qtip('hide');
          if(!mooConfig.rtl) {
            $('.moocore_tooltip_link').qtip({
                content: {
                    text: function(event, api) {
                       var data1 = $(this).data();
                       $.post(mooConfig.url.base + '/users/ajax_load_tooltip',{item_id: data1.item_id, item_type : data1.item_type}, function(data) {
                                api.set('content.text', data);
                                if ($('.response_friend_request').length) {
                                    $('.response_friend_request').unbind('click');
                                    $('.response_friend_request').on('click', function(){
                                        $( ".response_request" ).show();
                                    });
                                }
                                if ($('.respondRequest').length) {
                                    $('.respondRequest').unbind('click');
                                        $('.respondRequest').on('click', function(){
                                        var data = $(this).data();
                                        $.post(mooConfig.url.base + '/friends/ajax_respond', {id: data.id, status: data.status}, function(response){
                                            location.reload();
                                        });
                                    });
                                }

                                $('.usertip_action_follow').unbind('click');
                                $('.usertip_action_follow').click(function() {
                                    element = $(this);
                                    $.ajax({
                                        type: 'POST',
                                        url: mooConfig.url.base + '/follows/ajax_update_follow',
                                        data: {user_id: $(this).data('uid')},
                                        success: function (data) {
                                            if (element.data('follow')) {
                                                element.data('follow', 0);
                                                element.find('.hidden-xs').html(mooPhrase.__('text_follow'));
                                                element.find('.visible-xs').html('rss_feed');
                                            }
                                            else {
                                                element.data('follow', 1);
                                                element.find('.hidden-xs').html(mooPhrase.__('text_unfollow'));
                                                element.find('.visible-xs').html('check');
                                            }
                                        }
                                    });
                                });
                        });

                        return mooPhrase.__('loading');
                    }
                },
                hide: {
                    fixed: true,
                    delay: 50,
                    event: 'mouseleave click'
                },
                position: {
                    target: 'event', // Use the triggering element as the positioning target
                    my: 'top left',
                    at: 'right center',
                    adjust: { y:0, },
                    viewport: $(window)
                },
                style: {
                    // classes: 'websnapr qtip-blue'
                }
            });
          }
          else {
            $('.moocore_tooltip_link').qtip({
                content: {
                    text: function(event, api) {
                       var data1 = $(this).data();
                       $.post(mooConfig.url.base + '/users/ajax_load_tooltip',{item_id: data1.item_id, item_type : data1.item_type}, function(data) {
                                api.set('content.text', data);
                                if ($('.response_friend_request').length) {
                                    $('.response_friend_request').unbind('click');
                                    $('.response_friend_request').on('click', function(){
                                        $( ".response_request" ).show();
                                    });
                                }
                                if ($('.respondRequest').length) {
                                    $('.respondRequest').unbind('click');
                                        $('.respondRequest').on('click', function(){
                                        var data = $(this).data();
                                        $.post(mooConfig.url.base + '/friends/ajax_respond', {id: data.id, status: data.status}, function(response){
                                            location.reload();
                                        });
                                    });
                                }

                                $('.usertip_action_follow').unbind('click');
                                $('.usertip_action_follow').click(function() {
                                    element = $(this);
                                    $.ajax({
                                        type: 'POST',
                                        url: mooConfig.url.base + '/follows/ajax_update_follow',
                                        data: {user_id: $(this).data('uid')},
                                        success: function (data) {
                                            if (element.data('follow')) {
                                                element.data('follow', 0);
                                                element.find('.hidden-xs').html(mooPhrase.__('text_follow'));
                                                element.find('.visible-xs').html('rss_feed');
                                            }
                                            else {
                                                element.data('follow', 1);
                                                element.find('.hidden-xs').html(mooPhrase.__('text_unfollow'));
                                                element.find('.visible-xs').html('check');
                                            }
                                        }
                                    });
                                });
                        });

                        return 'Loading...';
                    }
                },
                hide: {
                    fixed: true,
                    delay: 50,
                    event: 'mouseleave click'
                },
                position: {
                    target: 'event', // Use the triggering element as the positioning target
                    my: 'top right',
                    at: 'left center',
                    adjust: { y:0, },
                    viewport: $(window)
                },
                style: {
                    // classes: 'websnapr qtip-blue'
                }
            });
          }
        }
    }
    
    //    exposed public method
    return {
        init:init
    };
}));