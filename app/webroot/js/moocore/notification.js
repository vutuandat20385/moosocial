/* Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['jquery', 'mooAjax', 'mooAlert', 'mooButton', 'mooPhrase','mooBehavior', 'tinycon', 'slimScroll', 'spinner'], factory);
    } else if (typeof exports === 'object') {
        // Node, CommonJS-like
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals (root is window)
        root.mooNotification = factory();
    }
}(this, function ($, mooAjax, mooAlert, mooButton, mooPhrase,mooBehavior) {
    var url = {};
    var active = true;
    var interval = 10000; // seconds

    var initLoadNotification = function() {
        if ($('#notificationDropdown')) {
            $('#notificationDropdown').unbind('click');
            $('#notificationDropdown').click(function() {
                var show_notification_url = url.show_notification;
                if (typeof(show_notification_url) != 'undefined'){
                    $(this).next('ul:first').spin('tiny');
                    
                    mooAjax.get({
                        url : show_notification_url,
                    }, function(data){
                        $('#notifications_list').html(data);
                        $('#notificationDropdown').next('ul:first').spin(false);
                        $('.initSlimScroll').slimScroll({ height: '500px' });
                        //binding hover delete icon
                        $("#notifications_list li").hover(
                            function () {
                                $(this).contents().find('.delete-icon').show();
                            },
                            function () {
                                $(this).contents().find('.delete-icon').hide();
                            }
                        );
                    });
                    
                }
            });
        }

        if ($('#conversationDropdown')) {
            $('#conversationDropdown').unbind('click');
            $('#conversationDropdown').click(function() {
                var show_conversation_url = url.show_conversation;
                $(this).next('ul:first').spin('tiny');
                
                mooAjax.get({
                    url : show_conversation_url,
                }, function(data){
                    $('#conversation_list').html(data);
                    $('#conversationDropdown').next('ul:first').spin(false);
                    $('.initSlimScroll').slimScroll({ height: '500px' });
                });
                
            });
        }
    }

    var initRefreshNotification = function(){
        var refresh_notification_url = url.refresh_notification_url;
        if (typeof(refresh_notification_url) != 'undefined'){
            window.setInterval(function(){
                $.getJSON(refresh_notification_url, function(data) {
                    // update notification count for sidebar menu
                    if ($('#notification_count')){
                        $('#notification_count').html(data.notification_count);
                    }

                    // update notification count for topbar menu
                    if (parseInt(data.notification_count) > 0){
                        if($('.notification_count').length > 0)
                        {
                            $('.notification_count').html(data.notification_count);

                        }else{
                            $('#notificationDropdown').append('<span class="notification_count">1</span>');
                        }
                    }else{
                        if($('.notification_count')){
                            $('.notification_count').remove();
                        }
                    }

                    // update conversation count
                    if (parseInt(data.conversation_count) > 0){
                        if($('.conversation_count').length > 0)
                        {
                            $('.conversation_count').html(data.conversation_count);

                        }else{
                            $('#conversationDropdown').append('<span class="conversation_count">1</span>');
                        }
                    }else{
                        if($('.conversation_count')){
                            $('.conversation_count').remove();
                        }
                    }

                }).fail(function() {
                    console.log("Error when calling " + refresh_notification_url)
                });
            }, interval);
        }
    }
    
    // app/View/Notifications/show.ctp
    var initRemoveNotification = function(){
        $('.removeNotification').unbind('click');
        $('.removeNotification').on('click', function(){
            var data = $(this).data();
            removeNotification(data.id);
        });
    }
    
    var removeNotification = function(id){
        
        mooAjax.get({
            url : mooConfig.url.base + '/notifications/ajax_remove/'+id,
        }, function(data){
            
            $("#noti_"+id).slideUp();

            if ( $('#noti_' + id).hasClass('unread') && $("#notification_count").html() != '0' )
            {
                var noti_count = parseInt($(".notification_count").html()) - 1;

                if(noti_count == 0)
                {
                    $(".notification_count").remove();
                }
                else
                {
                    $(".notification_count").html( noti_count );
                }
                $("#notification_count").html( noti_count );

                Tinycon.setBubble( noti_count );
            }
            
            $('body').trigger('afterRemoveNotificationCallback',[]);
        });
    };
    
    // app/View/Notifications/ajax_show.ctp
    var initAjaxShow = function(){
        
        $("#notifications_list_content li").hover(
            function () {
		$(this).contents().find('.delete-icon').show();
            },
            function () {
		$(this).contents().find('.delete-icon').hide();
            }
	);

        // bind action remove notification
        $('.removeNotification').unbind('click');
        $('.removeNotification').click(function(){
            
            var data = $(this).data();
            // remove a notification
            removeNotification(data.id);
        });
        
        // bind action clear all notification
        $('.clearAllNotification').unbind('click');
        $('.clearAllNotification').click(function(){
            clearNotifications();
        });
        
        mooBehavior.initMoreResults();
    }
    
    var clearNotifications = function(){
        $.get(mooConfig.url.base + '/notifications/ajax_clear');
        $(".notification_list").slideUp();
        $("#new_notifications").fadeOut();
        $("#notification_count").html('0');
        $('.notification_count').html('0');
        Tinycon.setBubble(0);

        $('body').trigger('afterClearNotificationCallback',[]);
        return false;
    }
    
    // app/View/Notifications/stop.ctp
    var initNotification = function(){
        $('#notificationButton').unbind('click');
        $('#notificationButton').click(function () {
            var item_type = $('#item_type').val();
            var item_id = $('#item_id').val();
            
            mooButton.disableButton('notificationButton');
            
            $('#notificationButton').spin('small');
            
            $.post(mooConfig.url.base + "/notifications/ajax_save", $("#notificationForm").serialize(), function (data) {
                mooButton.enableButton('notificationButton');
                $('#notificationButton').spin(false);
                var json = $.parseJSON(data);

                if (json.result == 1)
                {
                    $(".error-message").hide();
                    if (json.is_stop == 1){
                        $('#stop_notification_' + item_type + item_id).html(mooPhrase.__('turn_on_notifications'));
                    }else{
                        $('#stop_notification_' + item_type + item_id).html(mooPhrase.__('stop_notifications'));
                    }
                    
                    mooAlert.alert(json.message);
                    $('#portlet-config').modal('hide');
                    $('#themeModal').modal('hide');
                }
                else
                {
                    $(".error-message").show();
                    $(".error-message").html(json.message);
                }
            });
        });
        return false;
    }
    
    // app/View/Notifications/ajax_show.ctp
    // app/View/Notifications/show.ctp
    // app/View/Notifications/stop.ctp
    var init = function(){
        if (active)
        {
            initLoadNotification();
            initRefreshNotification();
        }
    }
    
    var setUrl = function(a){
        url = a;
    }
    
    var setActive = function(a){
        active = a;
    }
    
    var setInterval = function(a){
        // only set new interval when it greater than 0, by default it's 30 seconds
        if (a > 0){
            interval = a * 1000;
        }
    }
    
    var initMarkRead = function(){
        $('.markMsgStatus').unbind('click');
        $('.markMsgStatus').click(function(){
           var data = $(this).data();
           var obj = $(this);
           mooAjax.post({
                url: mooConfig.url.base + '/notifications/mark_read',
                data: {
                    id : data.id,
                    status : data.status
                }
            }, function (respsonse) {
                var json = $.parseJSON(respsonse);
                var currentCounter = $('#notification_count').html();
                if (json.status === '1'){
                    obj.parents('li:first').find('a:first').removeClass('unread');
                    obj.hide();
                    obj.next().show();
                    
                    // update counter
                    $('#notification_count').html(parseInt(currentCounter) - 1);
                    
                    if (parseInt(currentCounter) - 1 > 0){
                        if($('.notification_count').length > 0)
                        {
                            $('.notification_count').html(parseInt(currentCounter) - 1);

                        }else{
                            $('#notificationDropdown').append('<span class="notification_count">1</span>');
                        }
                    }else{
                        if($('.notification_count')){
                            $('.notification_count').remove();
                        }
                    }
                    
                }else{
                    obj.parents('li:first').find('a:first').addClass('unread');
                    obj.hide();
                    obj.prev().show();
                    
                    // update counter
                    $('#notification_count').html(parseInt(currentCounter) + 1);
                    
                    if($('.notification_count').length > 0)
                    {
                        $('.notification_count').html(parseInt(currentCounter) + 1);

                    }else{
                        $('#notificationDropdown').append('<span class="notification_count">1</span>');
                    }
                }
            });
        });
        
        // init markAllNotificationAsRead
        $('.markAllNotificationAsRead').unbind('click');
        $('.markAllNotificationAsRead').click(function(){
            mooAjax.post({
                url: mooConfig.url.base + '/notifications/mark_all_read',
                data: {}
            }, function (respsonse) {
                var json = $.parseJSON(respsonse);
                if (json.success == true){
                    
                    $('.mark_read').hide();
                    $('.mark_unread').show();
                    
                    // remove number for topbar dropdown
                    $('.notification_count').remove();
                    
                    // set count to 0 for home sidebar
                    $("#notification_count").html('0');
                    
                    // notifications items
                    $('#notifications_list a.unread').removeClass('unread');
                }
            });
        });
        
        $('.clearAllNotifications').unbind('click');
        $('.clearAllNotifications').click(function(){
            mooAjax.post({
                url: mooConfig.url.base + '/notifications/clear_all_notifications',
                data: {}
            }, function (data) {                  
                    $('.notification_count').remove();
                    
                    $("#notification_count").html('0');
                    
                    $('#notifications_list').html(data);
                    $('#notificationDropdown').next('ul:first').spin(false);
                    $('.initSlimScroll').slimScroll({ height: '500px' });
            });
        });
    }

    return{
        init: init,
        setUrl: setUrl,
        setActive: setActive,
        setInterval: setInterval,
        removeNotification : removeNotification,
        initAjaxShow : initAjaxShow,
        initNotification : initNotification,
        initRemoveNotification : initRemoveNotification,
        initMarkRead : initMarkRead
    }
}));