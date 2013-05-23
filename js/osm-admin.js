function eventpost_apply(addr,lat,lon){
	jQuery('#geo_address').attr('value',addr);
	jQuery('#geo_latitude').attr('value',lat);
	jQuery('#geo_longitude').attr('value',lon);
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
	})
});
