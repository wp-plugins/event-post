function eventpost_apply(addr,lat,lon){
	jQuery('#geo_address').attr('value',addr);
	jQuery('#geo_latitude').attr('value',lat);
	jQuery('#geo_longitude').attr('value',lon);
	jQuery('#eventaddress_result').html('');
}
jQuery(document).ready(function(){
	jQuery('#event_post input[type=date]').datepicker();
	
	jQuery('#event_post input[type=datetime-local]').each(function(){
		lang = jQuery(this).data('language');
		jQuery.datepicker.setDefaults(jQuery.datepicker.regional[lang]);
		//jQuery(this).val(jQuery(this).val().replace('T',' '));
		jQuery(this).datetimepicker({
			dateFormat:'yy-mm-dd',
			formatTime:'HH:mm:ss',
			separator:'T',
			minuteGrid:15
		});
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
				});
			}
		});
	});
	//jQuery('#event_post input[type=datetime]').datepicker();
	jQuery('#event_address_search').click(function(){
		var addr = jQuery('#geo_address').attr('value');
		console.log(addr);
		var data = {
			action: 'EventPostGetLatLong',
			q: addr
		};	
			
		jQuery('#eventaddress_result').html(addr+'...');
		
		jQuery.post(ajaxurl, data, function(data) {
			//console.log(data);
			var html_ret='';
			for(var lieu in data){
				console.log(data[lieu]);
				lieu = data[lieu];
				if(lieu.lat!='undefined' && lieu.lon!='undefined' && lieu.display_name!='undefined'){
					html_ret+='<p><a onclick=\'eventpost_apply("'+lieu.display_name.replace('\'','&apos;').replace('"','&quot;')+'","'+lieu.lat+'","'+lieu.lon+'")\'>'+lieu.display_name+'</a></p>';
				}
			}
			jQuery('#eventaddress_result').html(html_ret);	
		},'json');     
	})
});
