/* Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['jquery','mooPhrase', 'mooBehavior', 'mooMention', 'mooEmoji', 'mooFileUploader', 'mooUser', 'mooButton', 'mooGlobal',
            'mooResponsive', 'mooAttach', 'mooComment', 'mooLike', 'mooTooltip', 'mooAlert',
            'autogrow', 'spinner'], factory);
    } else if (typeof exports === 'object') {
        // Node, CommonJS-like
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals (root is window)
        root.mooActivities = factory();
    }
}(this, function ($, mooPhrase, mooBehavior, mooMention, mooEmoji, mooFileUploader, mooUser, 
    mooButton, mooGlobal, mooResponsive, mooAttach, mooComment, mooLike, mooTooltip, mooAlert) {
    
    var config = {};
    
    var initRemoveTags = function(){
        $('.removeTags').unbind('click');
        $('.removeTags').click(function(){
            var data = $(this).data();
            removeTags(data.activityId, data.activityItemType);
        });
    }
    
    var removeTags = function(item_id, item_type){
        $.fn.SimpleModal({
            btn_ok: mooPhrase.__('confirm'),
            btn_cancel: mooPhrase.__('cancel'),
            callback: function(){
                $.post(mooConfig.url.base + '/activities/ajax_remove_tags', {item_id: item_id, item_type : item_type}, function() {
                    window.location.reload();
                });
            },
            title: mooPhrase.__('remove_tags'),
            contents: mooPhrase.__('remove_tags_contents'),
            model: 'confirm', 
            hideFooter: false, 
            closeButton: false
        }).showModal();
    };
    
    var initShowAllComments = function(){
        $('.showAllComments').unbind('click');
        $('.showAllComments').on('click', function(){
            var data = $(this).data();
            showAllComments(data.id);
        });
    }
    
    
    var showAllComments = function( activity_id ){
        $('#comments_' + activity_id + ' .hidden').fadeIn();
        $('#comments_' + activity_id + ' .hidden').attr('class','');
        $('#all_comments_' + activity_id).hide();
    }
    
    var removeActivity = function(id)
    {
        $.fn.SimpleModal({
            btn_ok: mooPhrase.__('ok'),
            btn_cancel: mooPhrase.__('cancel'),
            callback: function(){
                $.post(mooConfig.url.base + '/activities/ajax_remove', {id: id}, function() {
                    $('#activity_'+id).fadeOut('normal', function() {
                        $('#activity_'+id).remove();

                        //plugin: sticky sidebar
                        $('body').trigger('afterRemoveActivityCallback',[]);
                    });
                });
            },
            title: mooPhrase.__('please_confirm'),
            contents: mooPhrase.__('please_confirm_remove_this_activity'),
            model: 'confirm', 
            hideFooter: false, 
            closeButton: false
        }).showModal();
    };
    
    
    var initEditActivity = function(){
        $('.editActivity').unbind("click");
        $('.editActivity').click(function(){
            var data = $(this).data();
            editActivity(data.activityId);
        });
    }
    
    var initRemoveActivity = function(){
        $('.removeActivity').unbind("click");
        $('.removeActivity').click(function(){
            var data = $(this).data();
            removeActivity(data.activityId);
        });
    }
    
    // app/View/Activities/ajax_load_activity_edit.ctp
    var initOnAjaxLoadActivityEdit = function(){
        
        // init cancel edit activity event
        $('.cancelEditActivity').unbind('click');
        $('.cancelEditActivity').click(function(){
            var data = $(this).data();
            cancelEditActivity(data.activityId);
        });
        
        // init comfirm edit activity event
        $('.confirmEditActivity').unbind('click');
        $('.confirmEditActivity').click(function(){
            var data = $(this).data();
            confirmEditActivity(data.activityId);
        });
    }

    var activity_edit_array = [];
    var editActivity = function(activity_id)
    {
    	if ($('#activity_edit_'+activity_id).length == 0)
        {
            $.post(mooConfig.url.base + '/activities/ajax_loadActivityEdit/'+ activity_id, function(data){
                $('#activity_feed_content_text_'+activity_id + ' .comment_message').hide();
                $(data).insertAfter($('#activity_feed_content_text_'+activity_id + ' .comment_message'));
                activity_edit_array.push(activity_id);
                init();

                
                //user mention
                mooMention.init($(data).find('textarea').attr('id'),'edit_activity');
                
                //user emoji
                mooEmoji.init($(data).find('textarea').attr('id'));

                //plugin: sticky sidebar
                $('body').trigger('afterLoadFormEditActivityCallback',[]);
            });
        }
    };

    var cancelEditActivity = function(activity_id)
    {
        //destroy overlay instance;
        if($('#message_edit_'+activity_id).siblings('.textoverlay')){
            $('#message_edit_'+activity_id).destroyOverlayInstance($('#message_edit_'+activity_id));
        }

        $('#activity_feed_content_text_'+activity_id + ' .comment_message').show();
        $('#activity_edit_'+activity_id).remove();

        var index = $.inArray(activity_id, activity_edit_array);
        activity_edit_array.splice(index, 1);

        //plugin: sticky sidebar
        $('body').trigger('afterCancelFormEditActivityCallback',[]);
    };

    var confirmEditActivity = function(activity_id)
    {
        if ($.trim($('#message_edit_'+activity_id).val()) != '')
        {
            var messageVal;
            if($("#message_edit_"+activity_id+"_hidden").length != 0){
                messageVal = $("#message_edit_"+activity_id+'_hidden').val();
            }else{
                messageVal = $("#message_edit_"+activity_id).val()
            }
            $.post(mooConfig.url.base + '/activities/ajax_editActivity/'+ activity_id,{message: messageVal}, function(data){
                //destroy overlay instance;
                if($('#message_edit_'+activity_id).siblings('.textoverlay')){
                    $('#message_edit_'+activity_id).destroyOverlayInstance($('#message_edit_'+activity_id));
                }

                $('#activity_feed_content_text_'+activity_id + ' .comment_message').html($(data).html());
                $('#history_activity_'+activity_id).show();
                cancelEditActivity(activity_id);
            });
        }
    };
    
     
    var removeActivityPhotoComment = function(id)
    {
        $.fn.SimpleModal({
            btn_ok: mooPhrase.__('ok'),
            btn_cancel: mooPhrase.__('cancel'),
            callback: function(){
                $.post(mooConfig.url.base + '/comments/ajax_remove', {id: id}, function() {
                    $('#photo_comment_'+id).fadeOut('normal', function() {
                        $('#photo_comment_'+id).remove();
                    });
                });
            },
            title:  mooPhrase.__('please_confirm'),
            contents: mooPhrase.__('please_confirm_remove_this_activity'),
            model: 'confirm', hideFooter: false, closeButton: false
        }).showModal();
    };
    
    var submitComment = function(activity_id)
    {
        if ($.trim($("#commentForm_"+activity_id).val()) != '' || $('#comment_image_'+activity_id).val() != '')
        {
            $('#commentButton_' + activity_id + ' a').addClass('disabled');
            $('#commentButton_' + activity_id + ' a').prepend('<i class="icon-refresh icon-spin"></i>');
            var comment = ($("#commentForm_"+activity_id).siblings('input.messageHidden').length > 0) ? $("#commentForm_"+activity_id).siblings('input.messageHidden').val() : $("#commentForm_"+activity_id).val();
            $.post(mooConfig.url.base + "/activities/ajax_comment", {activity_id: activity_id,thumbnail:$('#comment_image_'+activity_id).val(), comment: comment}, function(data){
                if (data != ''){
                    showPostedComment(activity_id, data);

                    
                    //reset mention
                    var textArea = $("#commentForm_"+activity_id);
                    mooMention.resetMention(textArea);
                    mooTooltip.init();
                }
            });
        }
    };
    
    var submitItemComment = function(item_type, item_id, activity_id)
    {
        if ($.trim($("#commentForm_"+activity_id).val()) != '' || $('#comment_image_'+activity_id).val() != '')
        {
            $('#commentButton_' + activity_id + ' a').prepend('<i class="icon-refresh icon-spin"></i>');
            $('#commentButton_' + activity_id + ' a').addClass('disabled');
            var message = '';
            if($("#commentForm_"+activity_id).siblings('.messageHidden').length > 0){
                message = $("#commentForm_"+activity_id).siblings('.messageHidden').val();
            }else{
                message = $("#commentForm_"+activity_id).val();
            }
            $.post(mooConfig.url.base + "/comments/ajax_share", {type: item_type, target_id: item_id, thumbnail:$('#comment_image_'+activity_id).val() ,message: message, activity: 1}, function(data){
                if (data != ''){
                    showPostedComment(activity_id, data);

                    
                    //reset mention
                    var textArea = $("#commentForm_"+activity_id);
                    mooMention.resetMention(textArea);
                    mooTooltip.init();
                }
            });
        }
    };

    var showPostedComment = function(activity_id, data)
    {
        if (mooConfig.comment_sort_style === '1'){
            $('#newComment_'+activity_id).before(data);
        }else{
            $('#newComment_'+activity_id).after(data);
        }
        
        $('.slide').slideDown();
        $('#commentButton_' + activity_id + ' a').removeClass('disabled');
        $('#commentButton_' + activity_id + ' a i').remove();
        $("#commentForm_"+activity_id).val('');
        //$("#commentButton_"+activity_id).hide();
        
        $('.commentBox').css('height', '27px');
        $('#comment_preview_image_' + activity_id).html('');
        $('#comment_image_' + activity_id).val('');
        $('#comment_button_attach_'+activity_id).show();
        mooBehavior.registerImageComment();
        init();
    };

    var changeActivityPrivacy = function(obj, activity_id, privacy)
    {
        $.post(mooConfig.url.base + '/activities/ajax_changeActivityPrivacy/',{activityId: activity_id, privacy: privacy}, function(data){
            if(data != ''){
                data = JSON.parse(data);
                var parent = obj.parents('.dropdown');
                parent.find('a#permission_'+activity_id).attr('original-title',data.text);
                parent.find('a#permission_'+activity_id+' i').html(data.icon);
                parent.find('.dropdown-menu li a').removeClass('n52');
                obj.addClass('n52');
            }
        });
    };
    
    // app/View/Elements/activity_form.ctp
    // app/View/Elements/activities.ctp
    // app/View/Comments/ajax_share.ctp
    // app/View/Activities/ajax_share.ctp
    var init = function(configParam){
        $('textarea:not(.no-grow)').autogrow();
        if( typeof config !== undefined) config = configParam;

        // init remove tags
        initRemoveTags();
        
        // bind edit activity event
        initEditActivity();
        
        // bind remove activity event
        initRemoveActivity();
        
        // bind edit activity comment event
        mooComment.initEditActivityComment();
             
        // bind remove activity comment event
        mooComment.initRemoveActivityComment();
        
        // bind remove item comment event
        mooComment.initRemoveItemComment();
        
        // remove  activity photo comment event
        $('body').off('click.activity','a.admin-or-owner-confirm-delete-photo-comment').on('click.activity','a.admin-or-owner-confirm-delete-photo-comment',function(){
            var data = $(this).data();

            if( typeof data.commentId !== undefined){
                removeActivityPhotoComment(data.commentId);
            }
        });
        // submitComment event
        $('body').off('click.activity','a.viewer-submit-comment').on('click.activity','a.viewer-submit-comment',function(){
            var data = $(this).data();

            if( typeof data.activityId !== undefined){
                submitComment(data.activityId);
            }
        });
        // submitComment event
        $('body').off('click.activity','a.viewer-submit-item-comment').on('click.activity','a.viewer-submit-item-comment',function(){
            var data = $(this).data();

            if( typeof data.itemType !== undefined && typeof data.activityItemId !== undefined && typeof data.activityId !== undefined){
                submitItemComment(data.itemType,data.activityItemId,data.activityId);
            }
        });
        // submitReply event
        $('body').off('click.activity','a.activity_reply_comment_button').on('click.activity','a.activity_reply_comment_button',function(){
            var data = $(this).data();
            type = data.type;
            id = data.id;
            var activity_id = data.activity;

            showReplies(type,id,0,activity_id);
        });
        $('body').off('click.activity','a.activity_reply_comment').on('click.activity','a.activity_reply_comment',function(){
            var data = $(this).data();
            button = $(this);
            type = data.type;
            id = data.id;
            typeid = '';
            if (type != 'comment')
            {
                typeid = 'activity';
            }

            if ($.trim($("#"+typeid+"commentReplyForm"+id).val()) != '' || $('#'+typeid+'comment_reply_image_'+id).val() != '')
            {
                $('#'+typeid+'commentReplyButton_' + id + ' a').prepend('<i class="icon-refresh icon-spin"></i>');
                $('#'+typeid+'commentReplyButton_' + id + ' a').addClass('disabled');
                var message = '';
                if($("#"+typeid+"commentReplyForm"+id).siblings('.messageHidden').length > 0){
                    message = $("#"+typeid+"commentReplyForm"+id).siblings('.messageHidden').val();
                }else{
                    message = $("#"+typeid+"commentReplyForm"+id).val();
                }
                $.post(mooConfig.url.base + "/comments/ajax_share", {activity:1,type: type, target_id: id, thumbnail:$('#'+typeid+'comment_reply_image_'+id).val() ,message: message}, function(data){
                    if (data != ''){
                        if( $('#'+typeid+'comments_reply_' + id).hasClass('isLoadNew')){
                            $('#' + typeid + 'newComment_reply_' + id).before(data);
                        }else {
                            if (mooConfig.reply_sort_style === '1') {
                                $('#' + typeid + 'newComment_reply_' + id).before(data);
                            } else {
                                $('#' + typeid + 'newComment_reply_' + id).after(data);
                            }
                        }

                        $('.slide').slideDown();
                        button.removeClass('disabled');

                        $("#"+typeid+"commentReplyForm"+id).val('');

                        $('.commentBox').css('height', '27px');
                        $('#'+typeid+'comment_reply_preview_image_' + id).html('');
                        $('#'+typeid+'comment_reply_image_' + id).val('');
                        $('#'+typeid+'comment_reply_button_attach_'+id).show();
                        mooBehavior.registerImageComment();

                        //reset mention
                        var textArea = $("#"+typeid+"commentReplyForm"+id);
                        mooMention.resetMention(textArea);
                        mooTooltip.init();
                    }
                });
            }
        });

        mooComment.initReplyReply();

        $('body').off('click.activity','a.activity_reply_comment_viewmore').on('click.activity','a.activity_reply_comment_viewmore',function(){
            var data = $(this).data();
            type = data.type;
            id = data.id;
            var close = data.close;
            var activity_id = data.activity;

            if(typeof close == 'undefined'){
                close = 0;
            }

            showReplies(type,id, close, activity_id);
        });
        //change activity's privacy
        $('body').off('click.activity','a.change-activity-privacy').on('click.activity','a.change-activity-privacy',function(){
            var data = $(this).data();
            if(typeof data.activityId !== undefined && typeof data.privacy !== undefined){
                changeActivityPrivacy($(this),data.activityId, data.privacy);
            }
        });
        
        // init showCommentForm
        mooComment.initShowCommentForm();
        
        // init LikeActivit
        mooLike.initLikeActivity();
        
        // init remove item comment
        mooComment.initRemoveItemComment();
        
        // init edit item comment
        mooComment.initEditItemComment();
        
        // init show comment btn on focus textarea 
        mooComment.initShowCommentBtn();
        
        // init View all %s comments
        initShowAllComments();
        
        // init load more
        mooBehavior.initMoreResults();

        //bind Close comment
        initCloseComment();
        
		//init feed form
        mooBehavior.initFeedForm();
    }

    var showReplies = function(type, id, close_comment, activity_id)
    {
        $('.activity_reply_comment_viewmore').each(function(){
            data = $(this).data();

            if (data.id == id && data.type == type)
            {
                $(this).parent().remove();
                return true;
            }
        });

        var div_id = '';
        if (type == 'comment')
        {
            div_id = 'comments_reply_' + id;
            if ($('#newComment_reply_' + id).is(":visible") && !$('#' + div_id).hasClass('isLoadNew')) {
                $('#newComment_reply_' + id +' .commentBox').focus();
                return;
            }
            $('#newComment_reply_' + id).show();
            $('#newComment_reply_' + id +' .commentBox').focus();
        }
        else
        {
            div_id = 'activitycomments_reply_' + id;
            if ($('#activitynewComment_reply_' + id).is(":visible") && !$('#' + div_id).hasClass('isLoadNew')) {
                $('#activitynewComment_reply_' + id+' .commentBox').focus();
                return;
            }
            $('#activitynewComment_reply_' + id).show();
            $('#activitynewComment_reply_' + id+' .commentBox').focus();
        }

        var url = mooConfig.url.base + "/comments/browse/"+type + "/" +id + '/id_content:'+div_id;
        if(typeof close_comment != 'undefined'){
            url += '/is_close_comment:' + close_comment;
        }
        if(typeof activity_id != 'undefined'){
            url += '/activity_id:' + activity_id;
        }

        $.post(url, function(data){
            if (data != ''){
                if($('#' + div_id).hasClass('isLoadNew')){
                    $('#' + div_id + '>li:not(:last-child)').remove();
                    $('#' + div_id).removeClass('isLoadNew');
                }

                if (mooConfig.reply_sort_style === '1') {
                    $('#' + div_id).prepend(data);
                }
                else {
                    $('#' + div_id).append(data);
                }

                mooTooltip.init();
                mooBehavior.registerImageComment();
            }
        });
    }

    // app/View/Elements/activity_form.ctp
    var stop_fetch_link = false;
    var initActivityForm = function(){
        
        $('[data-toggle="tooltip"]').tooltip();
        
        var uploader = new mooFileUploader.fineUploader({
            element: $('#select-2')[0],
            text: {
                uploadButton: '<div class="upload-section"><i class="material-icons">photo_camera</i></div>'
            },
            validation: {
                allowedExtensions: mooConfig.photoExt,
                sizeLimit : mooConfig.sizeLimit
            },
            multiple: true,
            request: {
                endpoint: mooConfig.url.base + "/upload/wall"
            },

            callbacks: {
                onError: mooGlobal.errorHandler,
                onSubmit: function(id, fileName){
                    var element = $('<span id="feed_'+id+'" style="background-image:url(' + mooConfig.url.base + '/img/indicator.gif);background-size:inherit;background-repeat:no-repeat"></span>');
                    element.insertBefore('.addMoreImage');
                    $('#wall_photo_preview').show();
                    $('#addMoreImage').show();
                },
                onComplete: function(id, fileName, response, xhr) {
                    if (response.success){
                        $('[data-toggle="tooltip"]').tooltip('hide');
                        $(this.getItemByFileId(id)).remove();
                        var img = $('<img src="'+response.file_path+'">');
                        img.load(function() {
                            var element = $('#feed_'+id);
                            element.attr('style','background-image:url(' + response.file_path + ')');
                            var deleteItem = $('<a href="#"><i class="material-icons thumb-review-delete">clear</i></a>');
                            element.append(deleteItem);

                            element.find('.thumb-review-delete').unbind('click');
                            element.find('.thumb-review-delete').click(function(e){
                                 e.preventDefault();
                                 $(this).parents('span').remove();
                                 $('#wall_photo').val($('#wall_photo').val().replace(response.photo + ',',''));
                                 $('body').trigger('afterDeleteWallPhotoCallback',[response]);
                            });
                        });

                        var wall_photo = $('#wall_photo').val();
                        $('#wall_photo').val(wall_photo+ response.photo + ',');
                        destroyPreviewlink();
                    }         
                    $('body').trigger('afterUploadWallPhotoCallback',[response]);
                }
            }
        });

        $('#addMoreImage').unbind('click');
        $('#addMoreImage').click(function(){
            $('#select-2 input[name=file]').click();
        });
        
        // init onfocus share what's news
        $('#message').on('focus', function(){
            mooComment.showCommentButton(0);
        });
        
        // bind share button
        $('#status_btn').unbind('click');
        $('#status_btn').click(function(){
            postWall(); 
        });
        
        
        // show activity form
        $('#status_box').slideDown("slow", function () {
            $('body').trigger('afterShowStatusBoxCallback');
        });
        
        $('#message').on('paste', function (e) {  
        	if ($('#wall_photo').val().trim() != '')
        		return;
            if($('#preview_link').length == 0){
                if (e.originalEvent.clipboardData) {            
                    var text = e.originalEvent.clipboardData.getData("text/plain");             
                    pasteInFeed(text);
                }
            }
        });


        $('#message').on('keydown keyup keypress', function (e) {
        	if ($('#wall_photo').val().trim() != '')
        		return;
            if(!e.ctrlKey && $('#preview_link').length == 0){
                var text = $(this).val();
                pasteInFeed(text);
            }        
        });
        
    
    }
    
    function getUrlFromText(text) {
        result = text.match(/\b([\d\w\.\/\+\-\?\:]*)((ht|f)tp(s|)\:\/\/|[\d\d\d|\d\d]\.[\d\d\d|\d\d]\.|www\.|\.tv|\.ac|\.com|\.edu|\.gov|\.int|\.mil|\.net|\.org|\.biz|\.info|\.name|\.pro|\.museum|\.co)([\d\w\.\/\%\+\-\=\&amp;\?\:\\\&quot;\'\,\|\~\;]*)\b/gi);
        if (result)
    	{
        	return result[0];
    	}
    }
    
    var pasteInFeed = function(iContent){
         var content = $('#message').val();        
         iContent = getUrlFromText(iContent);
         if (array_delete_links.hasOwnProperty(iContent))
        	 return;
         
           if (iContent && (substr(iContent, 0, 7) == 'http://' || substr(iContent, 0, 8) == 'https://' || (substr(iContent, 0, 4) == 'www.')))
	{           
            var checkHttps = strpos(iContent,'https://',0);
            var checkHttp = strpos(iContent,'http://',0);           
            if(checkHttps === 0  || checkHttp === 0){
                //check video link
                var checkV1 = strpos(iContent,'youtube.com',0);
                var checkV2 = strpos(iContent,'youtu.be',0);
                var checkV3 = strpos(iContent,'vimeo.com',0);
                if(!checkV1 && !checkV2 && !checkV3){
                    $('.userTagging-userShareLink').removeClass('hidden');
                    $('.userTagging-userShareVideo').addClass('hidden');
                    $('#userShareVideo').val('');
                    $('#userShareLink').val(iContent);
                    getLinkPreview('userShareLink', iContent, true, content);
                }else{
                    $('.userTagging-userShareVideo').removeClass('hidden');
                    $('.userTagging-userShareLink').addClass('hidden');
                    $('#userShareLink').val('');
                    $('#userShareVideo').val(iContent);
                    getLinkPreview('userShareVideo', iContent, true, content);              
                }
            }      
        }
    }
          
    var substr = function(sString, iStart, iLength) { 
        if(iStart < 0) 
        {
            iStart += sString.length;
        }

        if(iLength == undefined) 
        {
            iLength = sString.length;
        } 
        else if(iLength < 0)
        {
            iLength += sString.length;
        } 
        else 
        {
            iLength += iStart;
        }

        if(iLength < iStart) 
        {
            iLength = iStart;
        }

        return sString.substring(iStart, iLength);
    }

    var strpos = function(haystack, needle, offset) {
        var i = (haystack+'').indexOf(needle, (offset || 0));
        return i === -1 ? false : i;
    }
    
    var removePreviewlink = function(){
        $('.removeImage').unbind('click');
        $('.removeImage').on('click', function(){
           $(this).parent().remove();
           $('#shareImage').val('0');
        }); 
        $('.removeContent').unbind('click');
        $('.removeContent').on('click', function(){
        	destroyPreviewlink();
        });      
    }
    
    var destroyPreviewlink = function()
    {
    	if ($('#userShareLink').val().trim() != '')
    	{
    		array_delete_links[$('#userShareLink').val().trim()] = '1';
    	}
    	if ($('#userShareVideo').val().trim() != '')
    	{
    		array_delete_links[$('#userShareVideo').val().trim()] = '1';
    	}
    	
    	$('#preview_link').remove();
        $('#userShareLink').val('');
        $('#userShareVideo').val('');
        $('#shareImage').val('1');
        $('body').trigger('afterDestroyPreviewLinkWallCallback',[]);
    }
    
    var requestSent = false;
    var array_save_links = {};
    var array_delete_links = {};
    var getLinkPreview = function(el, content, paste, oldContent){
    	var element = $('.userTagging-'+ el);
        
    	if (!array_save_links.hasOwnProperty(content))
        {
    		element.spin('tiny');
        }
        setTimeout(function(){ //break the callstack to let the event finish
            if(!requestSent) {
            	if (array_save_links.hasOwnProperty(content))
    	        {
    	    		console.log(array_save_links[content]);
    	    		requestSent = true;
    	    		doPreviewLink(element,content, paste, oldContent,array_save_links[content]);	    		
    	        	return;
    	        }
            	
                requestSent = true;    
                var fbURL=mooConfig.url.base + "/activities/ajax_preview_link";                
                $.post(fbURL, {content:content}, function(resp){
                		array_save_links[content]= resp;
                        element.spin(false);
                        doPreviewLink(element,content, paste, oldContent,resp);
                });                  
            }
        },0);
    }
    
    var doPreviewLink = function(element,content, paste, oldContent, resp)
    {
    	$('#preview_link').remove();
        var obj = jQuery.parseJSON(resp);

        if(!jQuery.isEmptyObject(obj)) {
            if (typeof obj.title !== "undefined" && obj.title !== "404 Not Found" && obj.title !== "403 Forbidden") {
                var data = '<div class="activity_item" id="preview_link">';
                if (typeof obj.image !== "undefined" && obj.image != '') {
                    data += '<div class="activity_left"><a class="removePreviewlink removeImage" href="javascript:void(0)"><i class="icon-delete material-icons">clear</i></a>';
                    if (obj.image.indexOf('http') != -1) {
                        data += '<img src="' + obj.image + '" class="img_wrapper2">';
                    } else {
                        data += '<img src="' + mooConfig.url.base + '/uploads/links/' + obj.image + '" class="img_wrapper2">';
                    }
                    data += '<input type="hidden" name="data[share_image]" id="userShareLink" value="1">';
                    data += '</div>';
                }
                if (obj.image != '') {
                    data += '<div class="activity_right">';
                } else {
                    data += '<div>';
                }
                data += '<a class="removePreviewlink removeContent" href="javascript:void(0)"><i class="icon-delete material-icons">clear</i></a>';
                data += '<a class="attachment_edit_link feed_title" href="' + obj.url + '" target="_blank" rel="nofollow">';
                data += '<strong>' + obj.title + '</strong>';
                data += '</a>';
                if (typeof obj.description !== "undefined" && obj.description != '') {
                    data += '<div class="attachment_body_description">';
                    data += '<a class="attachment_edit_link comment_message feed_detail_text">' + obj.description + '</a>';
                    data += '</div>';
                }
                data += '<input type="hidden" name="data[share_text]" id="userShareLink" value="1">';
                data += '</div></div>';
            } else if (typeof obj.type !== "undefined" && obj.type == "img") {

                data = '<div class="activity_item" id="preview_link">';
                if (typeof obj.image !== "undefined" && obj.image != '') {
                    data += '<div class="activity_parse_img"><a class="removePreviewlink removeContent" href="javascript:void(0)"><i class="icon-delete material-icons">clear</i></a>';
                    data += '<img src="' + obj.image + '" class="img_wrapper2">';

                    data += '<input type="hidden" name="data[share_image]" id="userShareLink" value="1">';
                    data += '</div>';
                }
                data += '<input type="hidden" name="data[share_text]" id="userShareLink" value="1">';
                data += '</div>';
            }

            if (data != '') {
                element.append(data);
                if (paste) {
                    $('.textoverlay').text(oldContent);
                    $('.autogrow-textarea-mirror').text(oldContent);
                }
                removePreviewlink();
                $('body').trigger('afterPreviewLinkWallCallback', []);
            }
        }
        requestSent = false;
    }

    var postWall = function()
    {
        if (!mooUser.validateUser()){
            return false;
        }

        var msg = $('#message').val();
        if ($.trim(msg) != '' || ($("#video_destination").length > 0 && $("#video_destination").val() !== '') || $('#userShareLink').val() != '' || $('#userShareVideo').val() !='' || ($('#wall_photo_preview :not(#addMoreImage)').html() != '' && $('#wall_photo_preview :not(#addMoreImage)').html() != 'add'))
        {

            mooButton.disableButton('status_btn');
            $('#status_btn').spin('small');
            $.post(mooConfig.url.base + "/activities/ajax_share", $("#wallForm").serialize(), function(data){
                $('#wall_photo').val('');
                mooButton.enableButton('status_btn');
                $('#message').val("");
                $('.userTagging-userShareLink').addClass('hidden');
                $('.userTagging-userShareVideo').addClass('hidden');
                $('#shareImage').val('1');
                if ($("#video_destination").length > 0 && $("#video_destination").val() !== ''){
                    $.fn.SimpleModal({
                        model: 'content',
                        title: mooPhrase.__('upload_video_phrase_4'),
                        contents: mooPhrase.__('upload_video_phrase_0')
                    }).showModal();

                    setTimeout(function(){
                        $('#simpleModal').hideModal();
                    }, 3000);
                }
                else{
                    if (data != '')
                    {
                        if($('.no-feed').length > 0 ){
                            $('#list-content .no-feed').remove();
                        }

                        $('#list-content').prepend(data);

                        $('#message').css('height', '36px');
                        $('.slide').slideDown();

                        $('#wall_photo_preview span:not(.addMoreImage)').remove();
                        $('#addMoreImage').hide();
                        $('.form-feed-holder').css('padding-bottom','0px');

                        //register image
                        var attachment_id = $(data).find('div[id^=comment_button_attach_]').data('id');
                        mooAttach.registerAttachComment(attachment_id);
                    }
                }

                $('#status_btn').spin(false);
                mooResponsive.init();
                $(".tip").tipsy({ html: true, gravity: 's' });
                $('[data-toggle="tooltip"]').tooltip();

                //reset mention
                var textArea = $("#wallForm").find('#message');

                mooMention.resetMention(textArea);

                mooTooltip.init();

                $('#preview_link').remove();
                array_delete_links = {};
                $('#userShareLink').val('');
                $('#userShareVideo').val('');
                $('body').trigger('afterPostWallCallbackSuccess',[]);

            });
            $('.stt-action .userTagging-userTagging').addClass('hidden');
            $('.stt-action').css('margin-top','0');
            $('#wall_photo_preview').hide();
            $('#userTagging').tagsinput('removeAll');
        }else{
            $.fn.SimpleModal({
                btn_ok : mooPhrase.__('btn_ok'),
                btn_cancel: mooPhrase.__('cancel'),
                model: 'modal',
                title: mooPhrase.__('warning'),
                contents: mooPhrase.__('share_whats_new_can_not_empty')
            }).showModal();
        }
    }

    var initCloseComment = function(){
        $('.closeComment').unbind("click");
        $('.closeComment').click(function(){
            var data = $(this).data();
            $.post(mooConfig.url.base + '/comments/ajax_close', {item_id: data.id, item_type: data.type}, function(data) {
                var json = JSON.parse(data);
                if(json.result == 1){
                    mooAlert.alert(json.message);
                }
            });

            if(data.close == '1') {
                $(this).html(mooPhrase.__('close_comment'));
                $(this).data('close', 0);
            }else{
                $(this).html(mooPhrase.__('open_comment'));
                $(this).data('close', 1);
            }
        });
    }
       
    //    exposed public method
    return {
        init:init,
        initActivityForm : initActivityForm,
        initOnAjaxLoadActivityEdit : initOnAjaxLoadActivityEdit
    };
}));