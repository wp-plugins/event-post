jQuery(document).ready(function(){	
	// Single Location link 
	// show it in a tiny map instead of following the link
	jQuery('a.event_link.gps').click(function(){
		console.log(jQuery(this).data('latitude'));
		if(jQuery(this).parent().data('latitude')!=undefined && jQuery(this).parent().data('longitude')!=undefined){
			var lat = jQuery(this).parent().data('latitude');
			var lon = jQuery(this).parent().data('longitude');
			var marker = jQuery(this).parent().data('marker');
			var id = jQuery(this).parent().data('id');
			var zoom  = 16;	
			
		 	if(jQuery('#event_map'+id).length==0){
				jQuery(this).parent().append('<div id="event_map'+id+'" class="event_map"></div>');
				jQuery('#event_map'+id).css({
					width:'90%',
					height:'400px',
					margin:'auto',
					clear:'both'
				}).animate({height:'toggle'},1);	
				var fromProjection = new OpenLayers.Projection("EPSG:4326");   // Transform from WGS 1984
			    var toProjection   = new OpenLayers.Projection("EPSG:900913"); // to Spherical Mercator Projection
			    var position       = new OpenLayers.LonLat(lon, lat).transform( fromProjection, toProjection);
		    
				OpenLayers.ImgPath = eventpost_params.imgpath;
			    map = new OpenLayers.Map('event_map'+id);
			    map.addControl(new OpenLayers.Control.PanZoom());
			    var mapnik = new OpenLayers.Layer.OSM();
			    map.addLayer(mapnik);
			 
			    var markers = new OpenLayers.Layer.Markers( 'event_markers' );
			    var size = new OpenLayers.Size(32,32);
				var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
				var icons = new Array();
			    icons[marker] = new OpenLayers.Icon(marker, size, offset);
	
			    map.addLayer(markers);
			    markers.addMarker(new OpenLayers.Marker(position,icons[marker]));
			    
							
			}		
			jQuery('#event_map'+id).animate({height:'toggle'},1000,function(){
			    	map.setCenter(position, zoom);
			});
		    return false;
		}
	});	
	// List of events
	// Making a big map with all available locations	
	function addMarker(maplayer,markerslayer,ll, popupClass, popupContentHTML, closeBox, overflow,icon) {

        var feature = new OpenLayers.Feature(markerslayer, ll); 
        feature.closeBox = closeBox;
        feature.popupClass = popupClass;
        feature.data.popupContentHTML = popupContentHTML;
        feature.data.icon = icon;
        feature.data.overflow = (overflow) ? "auto" : "hidden";
                
        var marker = feature.createMarker();

        var markerClick = function (evt) {
            if (this.popup == null) {
                this.popup = this.createPopup(this.closeBox);
                maplayer.addPopup(this.popup);
                this.popup.show();
            } else {
                this.popup.toggle();
            }
            currentPopup = this.popup;
            OpenLayers.Event.stop(evt);
        };
        marker.events.register("mousedown", feature, markerClick);

        markerslayer.addMarker(marker);
    }
    
    var mapall=new Array();
    var markersall=new Array();
    
	jQuery('.event_geolist').each(function(){
		jQuery(this).children('.event_item').hide();
		var geo_id = jQuery(this).attr('id');
		var map_id = 'event_map_all'+geo_id;
		var mark_id = 'event_markersall'+geo_id;
		var width = jQuery(this).css('width');
		var height = jQuery(this).css('height');
		var maptile = jQuery(this).data('tile');
		
		jQuery(this).append('<div id="'+map_id+'" class="event_map"></div>');
		
		jQuery('#'+map_id).css({
			width:width,
			height:height,
			margin:'auto',
			clear:'both'
		});	
		var zoom  = 12;	
   	    
		OpenLayers.ImgPath = eventpost_params.imgpath;
	    mapall[map_id] = new OpenLayers.Map(map_id);
	    mapall[map_id] .addControl(new OpenLayers.Control.PanZoomBar());
	    if(maptile!='' && eventpost_params.maptiles[maptile]){
	    	console.log(eventpost_params.maptiles[maptile]);
			var maptile = new OpenLayers.Layer.OSM(maptile,eventpost_params.maptiles[maptile]['urls']);
		}
		else{
			maptile = new OpenLayers.Layer.OSM();
		}
	    mapall[map_id].addLayer(maptile);
	 
	    markersall[mark_id] = new OpenLayers.Layer.Markers( mark_id );
	    mapall[map_id].addLayer(markersall[mark_id]);
	     
	    var fromProjection = new OpenLayers.Projection("EPSG:4326");   // Transform from WGS 1984
	    var toProjection   = new OpenLayers.Projection("EPSG:900913"); // to Spherical Mercator Projection
	     
	    var size = new OpenLayers.Size(32,32);
	    var popupsize = new OpenLayers.Size(200,200);
		var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
		var iconsall = new Array();
		var position=false;
		var bounds = new OpenLayers.Bounds();
		var popup = OpenLayers.Class(OpenLayers.Popup.FramedCloud, {
            'autoSize': true,
        });
    
		jQuery(this).find('address').each(function(){
			var lat = jQuery(this).data('latitude');
			var lon = jQuery(this).data('longitude');
			if(lat!=undefined && lon!=undefined){
				var marker = jQuery(this).data('marker');
				var id = jQuery(this).data('id');
				var html = jQuery(this).parent().parent().find('h5').parent().wrap('<p>').parent().html()+jQuery(this).html();
				
				var date = jQuery(this).parent().parent().find('time').html();
				if(date!=undefined){
					html+='<p>'+date+'</p>';
				}
				position = new OpenLayers.LonLat(lon, lat).transform( fromProjection, toProjection);
				//console.log(lat+' / '+lon);
				if(iconsall[marker]!=undefined){
					icon_this = iconsall[marker].clone();
				}
				else{
					iconsall[marker] = new OpenLayers.Icon(marker, size, offset);
					icon_this = iconsall[marker];
				}
				//new OpenLayers.Marker(position,icon_this)
				addMarker(mapall[map_id],markersall[mark_id],position,popup,html,true,false,icon_this);
				bounds.extend(position);
    
			}
		});
	    
		mapall[map_id].setCenter(bounds.getCenterLonLat(), mapall[map_id].getZoomForExtent(bounds,false)); 
		
		
	    
	});
});
