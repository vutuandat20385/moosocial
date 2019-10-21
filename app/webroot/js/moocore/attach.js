/* Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['jquery', 'mooFileUploader', 'mooGlobal','mooMention'], factory);
    } else if (typeof exports === 'object') {
        // Node, CommonJS-like
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals (root is window)
        root.mooAttach = factory();
    }
}(this, function ($, mooFileUploader, mooGlobal, mooMention) {
    
    // app/View/Activities/ajax_share.ctp
    // app/View/Elements/activities.ctp
    // app/View/Elements/comment_form.ctp
    var registerAttachComment = function(id, type){
            if(typeof type == "undefined")
            {
                type = '';
            }
            else
            {
                type = '#' + type + ' ';
            }
            var uploader = new mooFileUploader.fineUploader({
                element: $(type + '#comment_button_attach_'+id)[0],
                text: {
                    uploadButton: '<div class="upload-section"><i class="material-icons">photo_camera</i></div>'
                },
                validation: {
                    allowedExtensions: mooConfig.photoExt,
                    sizeLimit: mooConfig.sizeLimit
                },
                multiple: false,
                request: {
                    endpoint: mooConfig.url.base+"/upload/wall"
                },
                callbacks: {
                    onError: mooGlobal.errorHandler,
                    onSubmit: function(id_img, fileName){
                        var element = $('<span id="attach_'+id+'_'+id_img+'" style="background-image:url('+mooConfig.url.base+'/img/indicator.gif);background-size:inherit;background-repeat:no-repeat"></span>');
                        $(type + '#comment_preview_image_'+id).append(element);
                        $(type + '#comment_button_attach_'+id).hide();
                    },
                    onComplete: function(id_img, fileName, response, xhr) {
                        $(this.getItemByFileId(id_img)).remove();
                        img = $('<img src="'+ mooConfig.url.base + '/' +response.photo+'">');
                        img.load(function() {
                            var element = $('#attach_'+id+'_'+id_img);
                            element.attr('style','background-image:url(' + mooConfig.url.base + '/' + response.photo + ')');
                            var deleteItem = $('<a href="javascript:void(0);"><i class="material-icons thumb-review-delete">clear</i></a>');
                            element.append(deleteItem);
                            
                            element.find('.thumb-review-delete').unbind('click');
                            element.find('.thumb-review-delete').click(function(){
                                element.remove();
                                $(type + '#comment_button_attach_'+id).show();
                                $(type + '#comment_image_'+id).val('');
                                });
                        });


                        $(type + '#comment_image_'+id).val(response.photo);
                    }
                }
            });
    };
    
    var registerAttachCommentEdit = function(type,id){
            var uploader = new mooFileUploader.fineUploader({
                element: $('#'+type+'_comment_attach_'+id)[0],
                text: {
                    uploadButton: '<div class="upload-section"><i class="material-icons">photo_camera</i></div>'
                },
                validation: {
                    allowedExtensions: mooConfig.photoExt,
                    sizeLimit: mooConfig.sizeLimit
                },
                multiple: false,
                request: {
                    endpoint: mooConfig.url.base+"/upload/wall"
                },
                callbacks: {
                    onError: mooGlobal.errorHandler,
                    onSubmit: function(id_img, fileName){
                        var element = $('<span id="attach_'+'_'+id+'_'+id_img+'" style="background-image:url('+mooConfig.url.base+'/img/indicator.gif);background-size:inherit;background-repeat:no-repeat"></span>');
                        $('#'+type+'_comment_preview_attach_'+id).append(element);
                        $('#'+type+'_comment_attach_'+id).hide(); 
                    },
                    onComplete: function(id_img, fileName, response, xhr) {
                            $(this.getItemByFileId(id_img)).remove()

                            img = $('<img src="'+ mooConfig.url.base + '/' +response.photo+'">');
                    img.load(function() {
                            var element = $('#attach_'+'_'+id+'_'+id_img);
                            element.attr('style','background-image:url(' + mooConfig.url.base + '/' + response.photo + ')');
                        var deleteItem = $('<a href="javascript:void(0);"><i class="material-icons thumb-review-delete">clear</i></a>');
                        element.append(deleteItem);
                        
                        element.find('.thumb-review-delete').unbind('click');
                        element.find('.thumb-review-delete').click(function(){
                            element.remove();
                            $('#'+type+'_comment_attach_'+id).show();
                            $('#'+type+'_comment_attach_id_'+id).val('');
                        });
                    })

                        $('#'+type+'_comment_attach_id_'+id).val(response.photo);
                        $('#'+type+'_comment_attach_'+id).hide();    
                    }
                }
            });
    };

    var registerAttachCommentReplay = function(id, type){
        mooMention.init('commentReplyForm'+id);
        if(typeof type == "undefined")
        {
            type = '';
        }
        else
        {
            type = '#' + type + ' ';
        }
        var uploader = new mooFileUploader.fineUploader({
            element: $(type + '#comment_reply_button_attach_'+id)[0],
            text: {
                uploadButton: '<div class="upload-section"><i class="material-icons">photo_camera</i></div>'
            },
            validation: {
                allowedExtensions: mooConfig.photoExt,
                sizeLimit: mooConfig.sizeLimit
            },
            multiple: false,
            request: {
                endpoint: mooConfig.url.base+"/upload/wall"
            },
            callbacks: {
                onError: mooGlobal.errorHandler,
                onSubmit: function(id_img, fileName){
                    var element = $('<span id="attach_'+id+'_'+id_img+'" style="background-image:url('+mooConfig.url.base+'/img/indicator.gif);background-size:inherit;background-repeat:no-repeat"></span>');
                    $(type + '#comment_reply_preview_image_'+id).append(element);
                    $(type + '#comment_reply_button_attach_'+id).hide();
                },
                onComplete: function(id_img, fileName, response, xhr) {
                    $(this.getItemByFileId(id_img)).remove();
                    img = $('<img src="'+ mooConfig.url.base + '/' +response.photo+'">');
                    img.load(function() {
                        var element = $('#attach_'+id+'_'+id_img);
                        element.attr('style','background-image:url(' + mooConfig.url.base + '/' + response.photo + ')');
                        var deleteItem = $('<a href="javascript:void(0);"><i class="material-icons thumb-review-delete">clear</i></a>');
                        element.append(deleteItem);

                        element.find('.thumb-review-delete').unbind('click');
                        element.find('.thumb-review-delete').click(function(){
                            element.remove();
                            $(type + '#comment_reply_button_attach_'+id).show();
                            $(type + '#comment_reply_image_'+id).val('');
                        });
                    });


                    $(type + '#comment_reply_image_'+id).val(response.photo);
                }
            }
        });
    };

    var registerAttachActivityCommentReplay = function(id, type){
        mooMention.init('activitycommentReplyForm'+id);
        if(typeof type == "undefined")
        {
            type = '';
        }
        else
        {
            type = '#' + type + ' ';
        }
        var uploader = new mooFileUploader.fineUploader({
            element: $(type + '#activitycomment_reply_button_attach_'+id)[0],
            text: {
                uploadButton: '<div class="upload-section"><i class="material-icons">photo_camera</i></div>'
            },
            validation: {
                allowedExtensions: mooConfig.photoExt,
                sizeLimit: mooConfig.sizeLimit
            },
            multiple: false,
            request: {
                endpoint: mooConfig.url.base+"/upload/wall"
            },
            callbacks: {
                onError: mooGlobal.errorHandler,
                onSubmit: function(id_img, fileName){
                    var element = $('<span id="attach_'+id+'_'+id_img+'" style="background-image:url('+mooConfig.url.base+'/img/indicator.gif);background-size:inherit;background-repeat:no-repeat"></span>');
                    $(type + '#activitycomment_reply_preview_image_'+id).append(element);
                    $(type + '#activitycomment_reply_button_attach_'+id).hide();
                },
                onComplete: function(id_img, fileName, response, xhr) {
                    $(this.getItemByFileId(id_img)).remove();
                    img = $('<img src="'+ mooConfig.url.base + '/' +response.photo+'">');
                    img.load(function() {
                        var element = $('#attach_'+id+'_'+id_img);
                        element.attr('style','background-image:url(' + mooConfig.url.base + '/' + response.photo + ')');
                        var deleteItem = $('<a href="javascript:void(0);"><i class="material-icons thumb-review-delete">clear</i></a>');
                        element.append(deleteItem);

                        element.find('.thumb-review-delete').unbind('click');
                        element.find('.thumb-review-delete').click(function(){
                            element.remove();
                            $(type + '#activitycomment_reply_button_attach_'+id).show();
                            $(type + '#activitycomment_reply_image_'+id).val('');
                        });
                    });


                    $(type + '#activitycomment_reply_image_'+id).val(response.photo);
                }
            }
        });
    };

    var registerAttachCommentItemReplay = function(id, type){
        mooMention.init('item_commentReplyForm'+id);
        if(typeof type == "undefined")
        {
            type = '';
        }
        else
        {
            type = '#' + type + ' ';
        }
        var uploader = new mooFileUploader.fineUploader({
            element: $(type + '#item_comment_reply_button_attach_'+id)[0],
            text: {
                uploadButton: '<div class="upload-section"><i class="material-icons">photo_camera</i></div>'
            },
            validation: {
                allowedExtensions: mooConfig.photoExt,
                sizeLimit: mooConfig.sizeLimit
            },
            multiple: false,
            request: {
                endpoint: mooConfig.url.base+"/upload/wall"
            },
            callbacks: {
                onError: mooGlobal.errorHandler,
                onSubmit: function(id_img, fileName){
                    var element = $('<span id="attach_'+id+'_'+id_img+'" style="background-image:url('+mooConfig.url.base+'/img/indicator.gif);background-size:inherit;background-repeat:no-repeat"></span>');
                    $(type + '#item_comment_reply_preview_image_'+id).append(element);
                    $(type + '#item_comment_reply_button_attach_'+id).hide();
                },
                onComplete: function(id_img, fileName, response, xhr) {
                    $(this.getItemByFileId(id_img)).remove();
                    img = $('<img src="'+ mooConfig.url.base + '/' +response.photo+'">');
                    img.load(function() {
                        var element = $('#attach_'+id+'_'+id_img);
                        element.attr('style','background-image:url(' + mooConfig.url.base + '/' + response.photo + ')');
                        var deleteItem = $('<a href="javascript:void(0);"><i class="material-icons thumb-review-delete">clear</i></a>');
                        element.append(deleteItem);

                        element.find('.thumb-review-delete').unbind('click');
                        element.find('.thumb-review-delete').click(function(){
                            element.remove();
                            $(type + '#item_comment_reply_button_attach_'+id).show();
                            $(type + '#item_comment_reply_image_'+id).val('');
                        });
                    });


                    $(type + '#item_comment_reply_image_'+id).val(response.photo);
                }
            }
        });
    };
    
    return {
        registerAttachComment : registerAttachComment,
        registerAttachCommentEdit : registerAttachCommentEdit,
        registerAttachCommentReplay : registerAttachCommentReplay,
        registerAttachActivityCommentReplay : registerAttachActivityCommentReplay,
        registerAttachCommentItemReplay: registerAttachCommentItemReplay
    }   
}));

