function eventpost_apply(addr,lat,lon){
    if(jQuery('#geo_address').val()==''){
        jQuery('#geo_address').val(addr);
    }
    jQuery('#geo_latitude').val(lat);
    jQuery('#geo_longitude').val(lon);
    jQuery('#eventaddress_result').html('');
}
function eventpost_numdate(str){
    var r=new RegExp("[-: T/]", "g");
    if(str.replace){
        str=str.replace(r,'');
    }
    return parseInt(str);
}
function eventpost_getdate(field){
    d = jQuery('#'+field+'_date').val();
    return d?eventpost_numdate(d.substring(0,16)+':00'):'';
}
function eventpost_getdate_sql(field){
    return jQuery('#'+field+'_date').val();
}
function eventpost_concat_time(){
    jQuery('.eventpost-datepicker-separate-wrap').each(function(){
        d=jQuery(this).find('.eventpost-datepicker-separate-date').val()+' '
        +jQuery(this).find('.eventpost-datepicker-separate-hour').val()+':'
        +jQuery(this).find('.eventpost-datepicker-separate-time').val()+':00';
        jQuery(this).find('.eventpost-datepicker-separate').val(d);
    });
}
function eventpost_chkdate(){
    //console.log('change date');
    var date_start=eventpost_getdate(eventpost.META_START);
    var date_end=eventpost_getdate(eventpost.META_END);
    console.log(date_start+' '+date_end);
    if(date_end==='' || date_start>date_end){
        jQuery('#'+eventpost.META_END+'_date').val(jQuery('#'+eventpost.META_START+'_date').val());
        jQuery('#'+eventpost.META_END+'_date').parent().find('.human_date').html(jQuery('#'+eventpost.META_START+'_date').parent().find('.human_date').html());
        date_end=date_start;
    }
    console.log(date_start);
    // UI
    if(date_start===0){
        jQuery('.event-post-event_begin_date-remove').hide();
    }
    else{
        jQuery('.event-post-event_begin_date-remove').show();
    }
    if(date_end===0){
        jQuery('.event-post-event_end_date-remove').hide();
    }
    else{
        jQuery('.event-post-event_end_date-remove').show();
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
        if(!jQuery(this).hasClass('event_post_sc_no_use')){
            var pc = jQuery(this).parent().parent().parent().attr('class');
            var att = jQuery(this).data('att');
            var val = jQuery(this).val();
            if(att!=null && val!='' && (pc=='all' || pc==sctype)){
                ep_sce+=' '+att+'="'+val+'"';
            }
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
    /*
     * Hide icons
     */
    jQuery('.event-color-section p label:nth-child(n9)').wrapAll('<span id="event-color-section-more"/>');
    jQuery('#event-color-section-more').hide().before('<a id="event-color-section-more-btn">'+eventpost.more_icons+'</a>');
    jQuery('#event-color-section-more-btn').css({display:'block'}).click(function(){
        jQuery('#event-color-section-more').toggle(300);
    });
    /*
     * Date picker Dual
     */
    eventpost_chkdate();
    jQuery( ".eventpost-datepicker-dual").each(function(){
        var datepick  ={
            format:'Y-m-d H:i',
            lang: jQuery(this).data('lang'),
            step:15,
            value:jQuery(this).val(),
            onSelectDate:function(ct,$i){
              eventpost_chkdate();
            },
            onSelectTime:function(ct,$i){
              eventpost_chkdate();
            }
        };
        console.log(datepick);
        jQuery(this).datetimepicker(datepick);
    }).css({
        visibility:'hidden',
        height:'1px'
    }).change(function(){
        var date_id = jQuery(this).attr('id');
        var hd = jQuery('#'+date_id+'_human');
        if(jQuery(this).val()!==''){
            jQuery.post(ajaxurl, {  action: 'EventPostHumanDate',date: jQuery(this).val()}, function(data) {
                hd.html(data);
                eventpost_chkdate();
            });
        }
    });
    jQuery( ".human_date").click(function(){
        var date_id = jQuery(this).attr('id').replace('_human','');
        jQuery( "#"+date_id).datetimepicker('show');
    }).each(function(){
        var date_id = jQuery(this).attr('id').replace('_human','');
        jQuery(this).after('<a class="dashicons dashicons-trash eventpost-date-remove event-post-'+date_id+'-remove" data-id="'+date_id+'"/>');
    });
    jQuery('.eventpost-date-remove').click(function(){
        var date_id = jQuery(this).data('id');
        jQuery( "#"+date_id).val('');
        jQuery( "#"+date_id+'_human').text(eventpost.pick_a_date);
        eventpost_chkdate();
    });
    /*
     * Date picker Separate
     */
    jQuery(".eventpost-datepicker-separate").each(function(){
        current_date = jQuery(this).val();
        current_id = jQuery(this).attr('id');
        jQuery(this).wrap('<span class="eventpost-datepicker-separate-wrap">');
        jQuery(this).after('<input class="eventpost-datepicker-separate-date" value="'+current_date.substr(0, 10)+'" data-id="'+current_id+'">'
                +'<select class="eventpost-datepicker-separate-hour" data-id="'+current_id+'"></select>'
                +'<select class="eventpost-datepicker-separate-time" data-id="'+current_id+'"></select>');
        jQuery(this).hide();  
        for(h=0 ; h<24 ; h++){
            h0 = h>9 ? h : '0'+h;
            h0h = h0;
            if(eventpost.lang==='en'){
                if(h<=12){
                    h0h = h+' AM'
                }
                else{
                    h0h = (h-12)+' PM'
                }
            }
            jQuery(".eventpost-datepicker-separate-hour").append('<option value="'+h0+'"'+(h===parseInt(current_date.substr(11, 2))?' selected':'')+'>'+h0h+'</option>');
        }
        m_sel=false;
        for(m=0 ; m<60 ; m+=15){
            m0 = m>9 ? m : '0'+m;
            selected = '';
            if(!m_sel && Math.abs(m-parseInt(current_date.substr(14, 2)))<=7){
                selected=' selected';
                m_sel=true;
            }
            jQuery(".eventpost-datepicker-separate-time").append('<option value="'+m0+'"'+selected+'>'+m0+'</option>');
        }
    });
    if (jQuery.datepicker) {
        jQuery(".eventpost-datepicker-separate-date").datepicker({
            firstDay: 1,
            changeYear: true,
            changeMonth: true,
            showMonthAfterYear: true,
            yearRange: "c-5:+5",
            buttonText: eventpost.date_choose,
            showOn: "both",
            dateFormat: "yy-mm-dd",
            autoSize: true
        }).change(function () {
            var date_id = jQuery(this).attr('id').replace('_date', '');
            var hd = jQuery('#' + date_id + '_date_human');
            if (jQuery(this).val() != '') {
                jQuery.post(ajaxurl, {action: 'EventPostHumanDate', date: eventpost_getdate_sql(date_id)}, function (data) {
                    hd.html(data);
                    eventpost_chkdate();
                });
            }
        });
    }
    jQuery(".eventpost-datepicker-separate-date, .eventpost-datepicker-separate-hour, .eventpost-datepicker-separate-time").change(function () {
        eventpost_concat_time();
        var date_id = jQuery(this).data('id').replace('_date','');
        var hd = jQuery('#' + date_id + '_date_human');
        if (jQuery(this).val() != '') {
            jQuery.post(ajaxurl, {action: 'EventPostHumanDate', date: eventpost_getdate_sql(date_id)}, function (data) {
                hd.html(data);
                eventpost_chkdate();
            });
        }
    });
    /*
     * Widgets stylish with icons
     */
    if(jQuery('body').hasClass('widgets-php')){
        jQuery('.widget').each(function(){
            wid = jQuery(this).attr('id');
            if(wid.indexOf('eventpostmap')>-1){
                jQuery(this).addClass('eventpost_admin_widget eventpost_widget_map');
            }
            else if(wid.indexOf('eventpostcal')>-1){
                jQuery(this).addClass('eventpost_admin_widget eventpost_widget_cal');
            }
            else if(wid.indexOf('eventpost')>-1){
                jQuery(this).addClass('eventpost_admin_widget eventpost_widget_list');
            }
        });
    }
});