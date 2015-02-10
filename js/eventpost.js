jQuery(document).ready(function () {

    if(typeof ol !== 'undefined'){
        var ep_maps = [];
        var ep_vectorSources = [];
        var ep_pop_elements = [];
        var ep_popups = [];
        var ep_icons = [];
        ep_proj_source = new ol.proj.Projection({code: 'EPSG:4326'});
        ep_proj_destination = new ol.proj.Projection({code: 'EPSG:900913'});


        /* ------------------------------------------------------------------------------------------------------------------
         * Single Location link
         * show it in a tiny map instead of following the link
         */
        jQuery('a.event_link.gps').click(function () {
            //console.log(jQuery(this).data('latitude'));
            if (jQuery(this).parent().data('latitude') !== undefined && jQuery(this).parent().data('longitude') !== undefined) {
                var lat = jQuery(this).parent().data('latitude');
                var lon = jQuery(this).parent().data('longitude');
                var marker = jQuery(this).parent().data('marker');
                var id = jQuery(this).parent().data('id');
                var zoom = 16;
                var map_id = 'event_map' + id;
                var position = new ol.proj.transform([lon, lat], ep_proj_source, ep_proj_destination);
                if (jQuery('#' + map_id).length === 0) {
                    jQuery(this).parent().append('<div id="' + map_id + '-wrap"><div id="' + map_id + '" class="event_map"></div></div>');
                    jQuery('#' + map_id+'-wrap').css({
                        height: '400px',
                        margin: 'auto',
                        clear:  'both'
                    }).animate({height: 'toggle'}, 1);

                    ep_vectorSources[map_id] = new ol.source.Vector();
                    ep_maps[map_id] = new ol.Map({
                        target: map_id,
                        layers: [
                            new ol.layer.Tile({
                                source: new ol.source.XYZ({
                                    urls: eventpost_params.maptiles[eventpost_params.defaulttile]['urls']
                                })
                            }),
                            new ol.layer.Vector({
                                source: ep_vectorSources[map_id]
                            })
                        ],
                        view: new ol.View({
                            center: position,
                            zoom: 12
                        })
                    });
                    ep_maps[map_id].addControl(new ol.control.Zoom());
                    var ep_feature = new ol.Feature({
                        geometry: new ol.geom.Point(position)
                    });

                    if (ep_icons[marker] == undefined) {
                        ep_icons[marker] = new ol.style.Style({
                            image: new ol.style.Icon(({
                                anchor: [16, 32],
                                anchorXUnits: 'pixels',
                                anchorYUnits: 'pixels',
                                opacity: 1,
                                src: marker
                            }))
                        });
                    }
                    ep_feature.setStyle(ep_icons[marker]);
                    ep_vectorSources[map_id].addFeature(ep_feature);

                }
                jQuery('#' + map_id+'-wrap').animate({height: 'toggle'}, 1000, function () {
                    ep_maps[map_id].getView().setCenter(position);
                });
                return false;
            }
        });




        /* ------------------------------------------------------------------------------------------------------------------
         * List of events
         * Making a big map with all available locations
         */
        // Parse all list wich have to be displayed as a map
        jQuery('.event_geolist').each(function () {
            jQuery(this).children('.event_item').hide();
            var geo_id = jQuery(this).attr('id');
            var map_id = 'event_map_all' + geo_id;
            var mark_id = 'event_markersall' + geo_id;
            var width = jQuery(this).data('width');
            var height = jQuery(this).data('height');
            var maptile = jQuery(this).data('tile');

            // Add html elements for map and popup
            jQuery(this).append('<div id="' + map_id + '" class="event_map map"></div><div id="' + map_id + '-popup" class="event_map_popup"></div>');
            css = {
                margin: 'auto',
                clear: 'both'
            };
            if (width !== 'auto')
                css.width = width;
            if (height !== 'auto')
                css.height = height;
            jQuery('#' + map_id).css(css);

            // Create a layer for markers
            ep_vectorSources[map_id] = new ol.source.Vector();

            // Create a popup object
            ep_pop_elements[map_id] = jQuery('#' + map_id + '-popup');
            ep_popups[map_id] = new ol.Overlay({
                element: ep_pop_elements[map_id],
                positioning: 'bottom-center',
                stopEvent: false
            });

            // Initialize map
            ep_maps[map_id] = new ol.Map({
                target: map_id,
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.XYZ({
                            urls: eventpost_params.maptiles[maptile]['urls']
                        })
                    }),
                    new ol.layer.Vector({
                        source: ep_vectorSources[map_id]
                    })
                ],
                view: new ol.View({
                    center: [0, 0],
                    zoom: 12,
                    maxZoom: 18
                }),
                overlays: [ep_popups[map_id]]
            });
            ep_maps[map_id].addControl(new ol.control.ZoomSlider());

            //Add action for each markers
            ep_maps[map_id].on('click', function (evt) {
                var feature = ep_maps[map_id].forEachFeatureAtPixel(evt.pixel,
                        function (feature, layer) {
                            return feature;
                        });
                if (feature) {
                    ep_pop_elements[map_id].hide(0);
                    view = ep_maps[map_id].getView();
                    var geometry = feature.getGeometry();
                    var coord = geometry.getCoordinates();
                    var pan = ol.animation.pan({
                        duration: 1000,
                        source: view.getCenter()
                    });
                    ep_maps[map_id].beforeRender(pan);
                    view.setCenter(coord);
                    ep_popups[map_id].setPosition(coord);

                    html_output = '<a href="' + feature.get('link') + '">' +
                            (feature.get('thumbnail')!=''&&feature.get('thumbnail')!=undefined?'<img src="'+feature.get('thumbnail')+'">':'')+
                            '<strong>' + feature.get('name') + '</strong><br>' +
                            '<time>' + feature.get('date') + '</time><br>' +
                            '<address>' + feature.get('address') + '</address>' +
                            (feature.get('desc')!=''&&feature.get('desc')!=undefined?'<p>'+feature.get('desc')+'</p>':'')+
                            '</a>';
                    ep_pop_elements[map_id].delay(500).html(html_output).show(500);

                } else {
                    ep_pop_elements[map_id].hide(200);
                }
            });

            // Parse all items to create markers and put them on the map
            jQuery(this).find('address').each(function () {
                var lat = parseFloat(jQuery(this).data('latitude'));
                var lon = parseFloat(jQuery(this).data('longitude'));
                if (lat != undefined && lon != undefined) {
                    var item = jQuery(this).parent().parent();
                    var marker = jQuery(this).data('marker');
                    var id = jQuery(this).data('id');
                    coords = new ol.proj.transform([lon, lat], ep_proj_source, ep_proj_destination);

                    obj={
                        geometry: new ol.geom.Point(coords),
                        name: item.find('h5').text(),
                        address: jQuery(this).html(),
                        date: item.find('time').text(),
                        link: item.find('a').attr('href'),
                        desc: item.find('.event_exerpt').html()
                    };
                    if(item.find('img').length>0){
                        obj.thumbnail=item.find('img').attr('src');
                    }
                    var ep_feature = new ol.Feature(obj);


                    if (ep_icons[marker] == undefined) {
                        ep_icons[marker] = new ol.style.Style({
                            image: new ol.style.Icon(({
                                anchor: [16, 32],
                                anchorXUnits: 'pixels',
                                anchorYUnits: 'pixels',
                                opacity: 1,
                                src: marker
                            }))
                        });
                    }
                    ep_feature.setStyle(ep_icons[marker]);
                    ep_vectorSources[map_id].addFeature(ep_feature);
                }
            });

            //Center the map to show all markers
            ep_maps[map_id].getView().fitExtent(ep_vectorSources[map_id].getExtent(), ep_maps[map_id].getSize());

        });
    }


    /* ------------------------------------------------------------------------------------------------------------------
     * Calendar Widget
     */
    function eventpost_cal_links() {
        jQuery('.eventpost_cal_bt').click(function () {
            var calcont = jQuery(this).parent().parent().parent().parent().parent();
            jQuery.get(eventpost_params.ajaxurl, {action: 'EventPostCalendar', date: jQuery(this).data('date'), cat: calcont.data('cat'), mf: calcont.data('mf'), dp: calcont.data('dp')}, function (data) {
                calcont.html(data);
                eventpost_cal_links();
            });
        });
        jQuery('.eventpost_cal_link').click(function () {
            var calcont = jQuery(this).parent().parent().parent().parent().parent();
            calcont.find('.eventpost_cal_list').fadeOut(function () {
                jQuery(this).remove();
            });
            jQuery.get(eventpost_params.ajaxurl, {action: 'EventPostCalendarDate', date: jQuery(this).data('date'), cat: calcont.data('cat'), mf: calcont.data('mf'), dp: calcont.data('dp')}, function (data) {
                calcont.append('<div class="eventpost_cal_list"><button class="eventpost_cal_close">x</button>' + data + '</div>');
                calcont.find('.eventpost_cal_list').hide(1).fadeIn(500);
                calcont.find('.eventpost_cal_close').click(function () {
                    console.log('ggg');
                    jQuery(this).parent().hide(500).remove();
                });
            });
        });

    }
    jQuery('.eventpost_calendar').each(function () {
        var calcont = jQuery(this);
        calcont.html('<img src="' + eventpost_params.imgpath + 'cal-loader.gif" class="eventpost_cal_loader"/>');
        jQuery.get(eventpost_params.ajaxurl, {action: 'EventPostCalendar', date: jQuery(this).data('date'), cat: jQuery(this).data('cat'), mf: jQuery(this).data('mf'), dp: jQuery(this).data('dp')}, function (data) {
            calcont.html(data);
            eventpost_cal_links();
        });
    });

});
