/* Copyright (c) SocialLOFT LLC

 * mooSocial - The Web 2.0 Social Network Software

 * @website: http://www.moosocial.com

 * @author: mooSocial

 * @license: https://moosocial.com/license/

 */
(function(root, factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD
		define(['jquery', 'mooMention', 'textcomplete'], factory);
	} else if (typeof exports === 'object') {
		// Node, CommonJS-like
		module.exports = factory(require('jquery'));
	} else {
		// Browser globals (root is window)
		root.mooEmoji = factory(root.jQuery);
	}
}(this, function($, mooMention) {
	var emojies = {
		'a48': ':evil:',
		'a20': ':sad:',
		'a1': ':laugh:',
		'a41': ':cool:',
		'a50': ':surprised:',
		'a24': ':crying:',
		'a29': ':sweating:',
		'a52': ':speechless:',
		'a8': ':kiss:',
		'a39': ':cheeky:',
		'a6': ':wink:',
		'a4': ':blushing:',
		'a47': ':wondering:',
		'a42': ':sleepy:',
		'a7': ':inlove:',
		'a3': ':smile:',
		'a43': ':yawn:',
		'a37': ':puke:',
		'a35': ':angry:',
		'a15': ':wasntme:',
		'a33': ':worry:',
		'a8': ':love:',
		'a74': ':devil:',
		'a72': ':angel:',
		'a19': ':envy:',
		'a53': ':meh:',
		'a23': ':rofl:',
		'a18': ':happy:',
		'a57': ':smirk:',
		'a77': ':beer:',
		'a76': ':clap:',
		'a2': ':sun:',
		'a5': ':flex:',
		'a9': ':no:',
		'a10': ':yes:',
		'a11': ':ok:',
		'a12': ':punch:',
		'a13': ':star:',
		'a14': ':car:',
		'a16': ':poop:',
		'a17': ':umbrella:',
		'a21': ':cake:',
		'a22': ':drink:',
		'a25': ':football:',
		'a26': ':mad:',
		'a27': ':silly:',
		'a28': ':flu:',
		'a30': ':excited:',
		'a31': ':pained:',
		'a32': ':cup:',
		'a34': ':music:',
		'a36': ':candy:',
		'a38': ':chicken:',
		'a40': ':cow:',
		'a44': ':dog:',
		'a45': ':hih:',
		'a46': ':email:',
		'a49': ':bike:',
		'a50': ':time:',
		'a51': ':brokenheart:',
		'a54': ':slow:',
		'a55': ':eat:',
		'a56': ':corn:'
	};
	var termLength;
	var searchTerm;
	var init = function(textAreaId, type) {
		if (mooConfig.isMobile)
			return;
		if (typeof(mooViewer) == 'undefined'){
			return;
		}
		var $ele_toggle = $('#' + textAreaId + '-emoji');
		if ($ele_toggle.length > 0 && !$ele_toggle.find('.emoji-toggle-face').length) {
			renderEmoji($ele_toggle, textAreaId);
		}
	};
	var renderEmoji = function($element, textAreaId) {
		$element.append('<i class="emoji-toggle-face material-icons">mood</i>' + '<div class="emoji-toggle-popup">' + '<div class="emoji-toggle-overlay"></div>' + '<div class="emoji-toggle-main">' + '<ul class="emoji-toggle-list"></ul>' + '</div>' + '</div>');
		$element.find('.emoji-toggle-face').click(function() {
			$(this).parent().find('.emoji-toggle-popup').toggleClass('show');
		});
		$element.find('.emoji-toggle-overlay').click(function() {
			$(this).parent().removeClass('show');
		});
		var index = 0;
		for (key in emojies) {
			var ele_item = $('<li class="" data-index="' + index + '"><span id="' + key + '" class="iconos"></span></li>');
			ele_item.find('span').click(function(e) {
				e.preventDefault();
				var keyID = $(this).attr('id');
				//insert emoji to textarea
				insertAtCaret(textAreaId, emojies[keyID]);
				//close popup
				$element.find('.emoji-toggle-overlay').trigger('click');
			});
			ele_item.appendTo($element.find('.emoji-toggle-list'));
			index++;
		}
	};
	// this function is used for updated hidden message
	var triggerReplaceMention = function(key, value, obj, termLength) {
		// position of : when user suggest an emoji
		var replacePosition = 0;
		var messageHidden = obj.siblings('.messageHidden');
		var originalObjVal = (messageHidden.length > 0) ? messageHidden.val() : obj.val();
		replacePosition = originalObjVal.length - searchTerm.length;
		var strReplace = value;
		// value before suggest emoji
		var frontValue = originalObjVal.substring(0, replacePosition);
		// emoji value
		var backValue = originalObjVal.substring(replacePosition);
		backValue = backValue.replace(originalObjVal.substring(replacePosition, (replacePosition + termLength)), strReplace);
		// update hidden message
		messageHidden.val(frontValue + backValue);
		// init mooMention
		mooMention.reConfigOverlay(obj);
	};
	var reConfigOverlay = function(obj, reRender) {
		//reRender overlay
		var textAreaObj = obj.getInstanceOverlay(obj);
		obj.revokeOverlay([{
			match: emojies
		}], textAreaObj);
		if (typeof reRender !== undefined) {
			obj.reRenderTextOnOverlay(textAreaObj);
		}
	};
	var insertAtCaret = function(areaId, value) {
			if ($('#' + areaId).siblings('.messageHidden').length)
			{
				termLength = 0;
				searchTerm = '';
				var replacePosition = $('#' + areaId).prop('selectionStart') - (value.length) + 1;
				triggerReplaceMention(0, value, $('#' + areaId), termLength + 1); // :el termLength = len(el), len(:) = 1 => Total = termLength + 1
				// init mooMention
				mooMention.reConfigOverlay($('#' + areaId), true);

				$('#' + areaId).focus();
			}
			else
			{
				var cursorPos = $('#'+areaId).prop('selectionStart');
			    var v = $('#'+areaId).val();
			    var textBefore = v.substring(0,  cursorPos);
			    var textAfter  = v.substring(cursorPos, v.length);

			    $('#'+areaId).val(textBefore + value + textAfter);
			    
			    element = document.getElementById(areaId);
				element.focus();
				element.setSelectionRange(cursorPos+value.length,cursorPos+value.length);
			}
        	$('#' + areaId).trigger('updateAutoGrow');
		}
		//    exposed public methods
	return {
		init: init
	}
}));