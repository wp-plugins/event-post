=== Event post ===
Contributors: bastho, ecolosites
Donate link: http://eelv.fr/adherer/
Tags: Post,posts,event,date,geolocalization,gps,widget,map,openstreetmap
Requires at least: 3.1
Tested up to: 3.5.1
Stable tag: /trunk
License: CC BY-NC 3.0
License URI: http://creativecommons.org/licenses/by-nc/3.0/

Add some meta-datas to posts to convert them into full calendar events :
begin, end, location, color

== Description ==
Add some meta-datas to posts to convert them into full calendar events.
Each event can be exported into ical(.ics), outlook(vcs), or Google Calendar.
Localization works thanks to openstreetmap.

= Date options =
* Begin date-time
* End date-time
* Color

= Location options =
* Address
* GPS coordinates

= Shortcodes =
The plugin comes with two shortcodes wich allows to :
<pre>[events_list]</pre> : display a list of events 
<pre>[events_map]</pre> : display a map of events 

some options are available, such as : 
<ul>
<li><b>nb=$nb</b> <i>(number of post, 0 is all, default: 5)</i></li>
<li><b>future=$future</b> <i>(boolean, retreive, or not, events in the future, default = 1)</i></li>
<li><b>past=$past</b> <i>(boolean, retreive, or not, events in the past, default = 0)</i></li>
<li><b>type=$type</b> <i>(string, possible values are : div, ul, ol default=div | only for [events_list])</i></li>
<li><b>cat=$category_slug</b> <i>(string, select posts only from the selected category, default=null, for all categories)</i></li>
<li><b>geo=$geo</b> <i>(boolean, retreives or not, only events wich have geolocation informations, default=0 | only for [events_map])</i></li>
<li><b>tile=$tile</b> <i>(string (default@osm.org, OpenCycleMap, mapquest, osmfr, 2u), sets the map background, default=default@osm.org | only for [events_map])</i></li>
</ul>

== Installation ==

1. Upload `event-post` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress admin
3. Create a new page and insert the short code `[eelv_news_form]`
4. You can edit defaults settings in Newsletter > Configuration and help

== Frequently asked questions ==

= Is the plugin fetches free ? =
Yes, and it uses only open-sources : openstreetmap, openlayer, jquery


== Screenshots ==

<img src="http://ecolosites.eelv.fr/files/2013/03/single.png"/>
<img src="http://ecolosites.eelv.fr/files/2013/03/admin.png"/>
<img src="http://ecolosites.eelv.fr/files/2013/03/carte.png"/>


== Changelog ==

= 2.2.0 =
* Add : Admin settings page : choose a date format and a default map background
* Add : Tite option for map shortcode, select a map background for a particular map
available maps : default@osm.org, OpenCycleMap, mapquest, osmfr, 2u

= 2.1.0 =
* Add : ajaxloader icon for address search
* Add : Event and location columns in posts list
* Add : widget description
* Add : place icon when available for address search
* Fix : Empty display_name property in address search

= 2.0.0 =
* Add: Category option for widgets and shortcodes
* Add: Force end date to be greater than begin date
* Add: Separate search field for GPS and address
* Fix: Wrong parameter for widget options
* Fix: Load jquery datetimepicker only if not supported by the browser

= 1.1.0 =
* Add: Width & height properties in the '[events_map]' shortcode
* Add: Allow multiple maps on the same page
* Fix: Same ID in multiple DOM elements bug fix 
* Fix: Some W3C standard corrections 

= 1.0.0 =
* Plugin creation

== Upgrade notice ==

No particular informations

== Languages ==

= Français  =
* fr_FR : 100%

= English =
* en	: 100%
