/**
 * jQuery custom selectboxes
 * 
 * Copyright (c) 2008 Krzysztof Suszyński (suszynski.org)
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 *
 * @version 0.6.1
 * @category visual
 * @package jquery
 * @subpakage ui.selectbox
 * @author Krzysztof Suszyński <k.suszynski@wit.edu.pl>
**/
jQuery.fn.selectbox = function(options){
	/* Default settings */
	var settings = {
		className: 'jquery-selectbox',
		animationSpeed: "fast",
		listboxMaxSize: 16,
		replaceInvisible: false,
		sprid: 0,
		spremenljivka_preview: true,
		parent_id: 'jquery-selectbox'
	};
	var commonClass = 'jquery-custom-selectboxes-replaced';
	var listOpen = false;
	var showList = function(listObj) {
		var selectbox = listObj.parents('.' + settings.className + '');
		listObj.slideDown(settings.animationSpeed, function(){
			listOpen = true;
		});
		selectbox.addClass('selecthover');
		jQuery(document).bind('click', onBlurList);
		return listObj;
	}
	var hideList = function(listObj) {
		var selectbox = listObj.parents('.' + settings.className + '');
		listObj.slideUp(settings.animationSpeed, function(){
			listOpen = false;
			jQuery(this).parents('.' + settings.className + '').removeClass('selecthover');
		});
		jQuery(document).unbind('click', onBlurList);
		return listObj;
	}
	var onBlurList = function(e) {
		var trgt = e.target;
		var currentListElements = jQuery('.' + settings.className + '-list:visible').parent().find('*').andSelf();
		if(jQuery.inArray(trgt, currentListElements)<0 && listOpen) {
			hideList( jQuery('.' + commonClass + '-list') );
		}
		return false;
	}
	
	/* Processing settings */
	settings = jQuery.extend(settings, options || {});
	/* Wrapping all passed elements */
	return this.each(function() {
		var _this = jQuery(this);
		if(_this.filter(':visible').length == 0 && !settings.replaceInvisible)
			return;
		if (jQuery(this).attr('spr_id')) { // poseben tip za predogled tipa vprasanja
			settings.spremenljivka_preview = true;
			var replacement = jQuery(
			//		'<div id="spremenljivka_tip_'+jQuery(this).attr('spr_id')+'" class="' + settings.className + ' ' + commonClass + '">' +
					'<div id="'+jQuery(this).attr('id')+'" class="' + settings.className + ' ' + commonClass + '">' +
						'<div class="' + settings.className + '-moreButton" />' +
						'<div class="' + settings.className + '-list ' + commonClass + '-list" />' +
						'<span class="' + settings.className + '-currentItem" />' +
					'</div>'
				);
			//settings.parent_id = 'spremenljivka_tip_'+jQuery(this).attr('spr_id');
			settings.parent_id = jQuery(this).attr('id');
		} else { // normalni nacin dropdown
			settings.spremenljivka_preview = false;
			var replacement = jQuery(
					'<div id="'+jQuery(this).attr('id')+'" class="' + settings.className + ' ' + commonClass + '">' +
						'<div class="' + settings.className + '-moreButton" />' +
						'<div class="' + settings.className + '-list ' + commonClass + '-list" />' +
						'<span class="' + settings.className + '-currentItem" />' +
					'</div>'
				);
			settings.parent_id = jQuery(this).attr('id');
		}
		jQuery('option', _this).each(function(k,v){
			var v = jQuery(v);
			var spr_id = jQuery(this).parents().attr('spr_id');
			var name = jQuery(this).parents().attr('name');

			if ( jQuery(this).attr('disabled') )
				var disabled_string = " option_disabled";
			else
				var disabled_string = "";

			if (settings.spremenljivka_preview)
				var listElement =  jQuery('<span id="'+jQuery(this).parents().attr('spr_id')+'_'+v.val()+'" class="' + settings.className + '-item value-'+v.val()+' item-'+k+'" val="'+v.val()+'">' + v.text() + '</span>');
			else {
				var listElement =  jQuery('<span id="'+jQuery(this).parents().attr('id')+'_'+v.val()+'" class="' + settings.className + '-item value-'+v.val()+' item-'+k+disabled_string+'" val="'+v.val()+'" value="'+v.val()+'">' + v.text() + '</span>');
			}
			if ( !jQuery(this).attr('disabled') ) // ce ni disejblan 
			listElement.click(function(){
				var thisListElement = jQuery(this);
				var thisReplacment = thisListElement.parents('.'+settings.className);
				var thisIndex = thisListElement[0].className.split(' ');
				for( k1 in thisIndex ) {
					if(/^item-[0-9]+$/.test(thisIndex[k1])) {
						thisIndex = parseInt(thisIndex[k1].replace('item-',''), 10);
						break;
					}
				};
				var thisValue = thisListElement[0].className.split(' ');

				for( k1 in thisValue ) {
					if(/^value-.+$/.test(thisValue[k1])) {
						thisValue = thisValue[k1].replace('value-','');
						break;
					}
				};
				thisReplacment
					.find('.' + settings.className + '-currentItem')
					.text(thisListElement.text());
				thisReplacment
					.find('select')
					.val(thisValue)
					.triggerHandler('change');
				var thisSublist = thisReplacment.find('.' + settings.className + '-list');
				if(thisSublist.filter(":visible").length > 0) {
					hideList( thisSublist );
				}else{
					showList( thisSublist );
				};
				jQuery("#"+settings.parent_id).val(thisValue);
				// skrijemo prwview vprasanja
				$("#tip_preview").hide();
			}).bind('mouseenter',function(){
				jQuery(this).addClass('listelementhover');
			}).bind('mouseleave',function(){
				jQuery(this).removeClass('listelementhover');
			}).bind('mouseover',function(){				
				if( (settings.spremenljivka_preview) && (name == 'design') )
					show_tip_preview_subtype(spr_id,v.val(),17); // prikazemo predogled designa pri razvrscanju
				else if( (settings.spremenljivka_preview) && (name == 'sn_design') )
					show_tip_preview_subtype(spr_id,v.val(),9); // prikazemo predogled designa pri sn generatorju imen
				else if( (settings.spremenljivka_preview) && (name == 'orientation') )
					show_tip_preview_subtype(spr_id,v.val(),1); // prikazemo predogled postavitve pri radio buttnih
				else if( (settings.spremenljivka_preview) && (name == 'orientation_checkbox') )
					show_tip_preview_subtype(spr_id,v.val(),2); // prikazemo predogled postavitve pri radio buttnih
				else if( (settings.spremenljivka_preview) && (name == 'enota') )
					show_tip_preview_subtype(spr_id,v.val(),6); // prikazemo predogled postavitve pri multigridu
                else if( (settings.spremenljivka_preview) && (name == 'podtip_lokacija') )
					show_tip_preview_subtype(spr_id,v.val(),26); // prikazemo predogled podtipa pri lokaciji
				else if ( settings.spremenljivka_preview )
					show_tip_preview(spr_id,v.val()); // prikazemo predogled spremenljivke
			}).bind('mouseout',function(){
//				if (settings.spremenljivka_preview)
					$("#tip_preview").hide();
			});
			jQuery('.' + settings.className + '-list', replacement).append(listElement);
			if(v.filter(':selected').length > 0) {
				jQuery('.'+settings.className + '-currentItem', replacement).text(v.text());
			}
		});
		replacement.find('.' + settings.className + '-moreButton').click(function(){
			var thisMoreButton = jQuery(this);
			var otherLists = jQuery('.' + settings.className + '-list')
				.not(thisMoreButton.siblings('.' + settings.className + '-list'));
			hideList( otherLists );
			var thisList = thisMoreButton.siblings('.' + settings.className + '-list');
			if(thisList.filter(":visible").length > 0) {
				hideList( thisList );
			}else{
				showList( thisList );
			}
		}).bind('mouseenter',function(){
			jQuery(this).addClass('morebuttonhover');
		}).bind('mouseleave',function(){
			jQuery(this).removeClass('morebuttonhover');
		});
		_this.hide().replaceWith(replacement).appendTo(replacement);
		var thisListBox = replacement.find('.' + settings.className + '-list');
		var thisListBoxSize = thisListBox.find('.' + settings.className + '-item').length;
		if(thisListBoxSize > settings.listboxMaxSize)
			thisListBoxSize = settings.listboxMaxSize;
		if(thisListBoxSize == 0)
			thisListBoxSize = 1;	
		var thisListBoxWidth = Math.round(_this.width() + 5);
		if(jQuery.browser.safari)
			thisListBoxWidth = thisListBoxWidth * 0.94+10;
		replacement.css('width', thisListBoxWidth + 'px');
		thisListBox.css({
			width: Math.round(thisListBoxWidth-5) + 'px',
			height: Math.round(thisListBoxSize*15.5)+ 'px'
		});
	});
}
jQuery.fn.unselectbox = function(){
	var commonClass = 'jquery-custom-selectboxes-replaced';
	return this.each(function() {
		var selectToRemove = jQuery(this).filter('.' + commonClass);
		selectToRemove.replaceWith(selectToRemove.find('select').show());		
	});
}	