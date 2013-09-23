function eventpost_apply(addr,lat,lon){
	if(jQuery('#geo_address').val()==''){
		jQuery('#geo_address').val(addr);	
	}	
	jQuery('#geo_latitude').val(lat);
	jQuery('#geo_longitude').val(lon);
	jQuery('#eventaddress_result').html('');
}
function eventpost_numdate(str){
	while(str.indexOf('-')>-1){
		str=str.replace('-','');
	}
	while(str.indexOf(':')>-1){
		str=str.replace(':','');
	}
	while(str.indexOf(' ')>-1){
		str=str.replace(' ','');
	}
	while(str.indexOf('T')>-1){
		str=str.replace('T','');
	}
	while(str.indexOf('/')>-1){
		str=str.replace('/','');
	}
	return parseInt(str);
}
function eventpost_chkdate(){
	console.log('change date');
	console.log(eventpost_numdate(jQuery('#'+eventpost.META_START).val()));
	if(jQuery('#'+eventpost.META_END).val()=='' || eventpost_numdate(jQuery('#'+eventpost.META_START).val())>eventpost_numdate(jQuery('#'+eventpost.META_END).val())){
		jQuery('#'+eventpost.META_END).val(jQuery('#'+eventpost.META_START).val());
		jQuery('#'+eventpost.META_END).parent().find('.human_date').html(jQuery('#'+eventpost.META_START).parent().find('.human_date').html());
	}
}
function eventpost_edit(){	
	sctype = jQuery('#ep_sce_type').val();
	if(sctype=='list'){
		jQuery('#ep_sce_maponly').hide();
		jQuery('#ep_sce_listonly').show();
	}
	if(sctype=='map'){
		jQuery('#ep_sce_maponly').show();
		jQuery('#ep_sce_listonly').hide();
	}
	var ep_sce='[events_'+sctype;
	jQuery('#event_post_sc_edit input,#event_post_sc_edit select').each(function(){
		var pc = jQuery(this).parent().parent().parent().attr('class');
		var att = jQuery(this).data('att');
		var val = jQuery(this).val();
		if(att!=null && val!='' && (pc=='all' || pc==sctype)){
			ep_sce+=' '+att+'="'+val+'"';
		}
	});
	ep_sce+=']';
	jQuery('#ep_sce_shortcode').html(ep_sce);
}
function eventpost_insertcontent(str){
  if(IEbof){
    switchEditors.go('content', 'html');
    document.post.content.value+=str;
    switchEditors.go('content', 'tinymce');
  }
  else{
    document.post.content.value+=str;
    if (document.all) {
      value = str;
      document.getElementById('content_ifr').name='content_ifr';
      var ec_sel = document.getElementById('content_ifr').document.selection;
      if(tinyMCE.activeEditor.selection){
        tinyMCE.activeEditor.selection.setContent(str);
      }
      else if(tinyMCE.activeEditor){
        tinyMCE.activeEditor.execCommand("mceInsertRawHTML", false, str);
      }
      else if (ec_sel) {
        var ec_rng = ec_sel.createRange();
        ec_rng.pasteHTML(value);
      }
      else{
      }
    }
    else{
      document.getElementById('content_ifr').name='content_ifr';
      if(document.content_ifr){
        document.content_ifr.document.execCommand('insertHTML', false, str);
      }
      else if(document.getElementById('content_ifr').contentDocument){
        document.getElementById('content_ifr').contentDocument.execCommand('insertHTML', false, str);
      }
      else if(tinyMCE.activeEditor.selection){
        tinyMCE.activeEditor.selection.setContent(str);
      }
      else{
        tinyMCE.activeEditor.execCommand("mceInsertRawHTML", false, str);
      }
    }  
  }
}
jQuery(document).ready(function(){
	var is_browser_good=false;
	var i = document.createElement("input");
	i.setAttribute("type", "datetime-local");
	if(i.type !== "text"){
		is_browser_good=true;
	}
	jQuery('#event_post input[type=datetime-local]').each(function(){
		if(is_browser_good==false){
			lang = jQuery(this).data('language');
			jQuery.datepicker.setDefaults(jQuery.datepicker.regional[lang]);
			jQuery(this).datetimepicker({
				dateFormat:'yy-mm-dd',
				formatTime:'HH:mm:ss',
				separator:'T',
				minuteGrid:15
			});
		}
		
		var hd = jQuery(this).parent().find('.human_date');
		if(jQuery(this).val()!=''){
			jQuery.post(ajaxurl, {	action: 'EventPostHumanDate',date: jQuery(this).val()}, function(data) {
				hd.html(data);	
			});
		}
		jQuery(this).change(function(){
			
			if(jQuery(this).val()!=''){
				jQuery.post(ajaxurl, {	action: 'EventPostHumanDate',date: jQuery(this).val()}, function(data) {
					hd.html(data);	
					eventpost_chkdate();
				});
			}
		});
	});
	jQuery('#event_address_search').click(function(){
		jQuery('#eventaddress_result').html('<input type="search" id="event_address_search_txt"/><input type="button" id="event_address_search_bt" value="ok" class="button"/>');
		
		jQuery('#event_address_search_bt').click(function(){		
		
			var addr = jQuery('#event_address_search_txt').attr('value');
			console.log(addr);
			var data = {
				action: 'EventPostGetLatLong',
				q: addr
			};	
				
			jQuery('#eventaddress_result').html(addr+'<br/><img src="'+eventpost.imgpath+'loader.gif" alt="..."/>');
			
			jQuery.post(ajaxurl, data, function(data) {
				var html_ret='';
				for(var lieu in data){
					//console.log(data[lieu]);
					lieu = data[lieu];
					if(lieu.lat!=undefined && lieu.lon!=undefined && lieu.display_name!=undefined){
						html_ret+='<p><a onclick=\'eventpost_apply("'+lieu.display_name.replace('\'','&apos;').replace('"','&quot;')+'","'+lieu.lat+'","'+lieu.lon+'")\'>';
						if(lieu.icon!=undefined){
							html_ret+='<img src="'+lieu.icon+'" alt="'+lieu.type+'"/>';
						}
						html_ret+=lieu.display_name+'</a></p>';
					}
				}
				jQuery('#eventaddress_result').html(html_ret);	
		},'json'); 
		});    
	});
	jQuery('#event_post_sc_edit input,#event_post_sc_edit select').change(function(){
		eventpost_edit();
	});
	jQuery('#ep_sce_submit').click(function(){
		eventpost_insertcontent(jQuery('#ep_sce_shortcode').html());
	});
	jQuery('#ep_sce_nball').click(function(){
		jQuery('#ep_sce_nb').val('-1');
		eventpost_edit();
	});
	jQuery('#ep_sce_shortcode').click(function(){
		jQuery(this).select();
	});
	
	
	eventpost_edit();
});
