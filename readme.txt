=== Event post ===
Contributors: bastho, ecolosites
Donate link: http://eelv.fr/adherer/
Tags: Post,posts,event,date,geolocalization,gps,widget,map,openstreetmap,calendar
Requires at least: 3.1
Tested up to: 3.6.1
Stable tag: /trunk
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

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
The plugin comes with three shortcodes wich allows to :
<pre>[events_list]</pre> : display a list of events 
<pre>[events_map]</pre> : display a map of events  
<pre>[events_cal]</pre> : display a calendar of events 

Available options : 
<h5>[events_list]</h5>
<ul>
<li><b>nb=5</b> <i>(number of post, -1 is all, default: 5)</i></li>
<li><b>future=1</b> <i>(boolean, retreive, or not, events in the future, default = 1)</i></li>
<li><b>past=0</b> <i>(boolean, retreive, or not, events in the past, default = 0)</i></li>
<li><b>type=div</b> <i>(string, possible values are : div, ul, ol default=div)</i></li>
<li><b>cat=''</b> <i>(string, select posts only from the selected category, default=null, for all categories)</i></li>
<li><b>geo=0</b> <i>(boolean, retreives or not, only events wich have geolocation informations, default=0)</i></li>
<li><b>title=''</b> <i>(string (default )</i></li>
<li><b>before_title="&lt;h3&gt;"</b> <i>(string (default &lt;h3&gt;)</i></li>
<li><b>after_title="&lt;/h3&gt;"</b> <i>(string (default &lt;/h3&gt;)</i></li>
</ul>
example : <pre>[events_list future=1 past=1 cat="actuality" nb=10]</pre>

<h5>[events_map]</h5>
<ul>
<li><b>nb=5</b> <i>(number of post, -1 is all, default: 5)</i></li>
<li><b>future=1</b> <i>(boolean, retreive, or not, events in the future, default = 1)</i></li>
<li><b>past=0</b> <i>(boolean, retreive, or not, events in the past, default = 0)</i></li>
<li><b>cat=''</b> <i>(string, select posts only from the selected category, default=null, for all categories)</i></li>
<li><b>tile=''</b> <i>(string (default@osm.org, OpenCycleMap, mapquest, osmfr, 2u), sets the map background, default=default@osm.org)</i></li>
<li><b>title=''</b> <i>(string (default )</i></li>
<li><b>before_title="&lt;h3&gt;"</b> <i>(string (default &lt;h3&gt;)</i></li>
<li><b>after_title="&lt;/h3&gt;"</b> <i>(string (default &lt;/h3&gt;)</i></li>
</ul>
example : <pre>[events_map future=1 past=1 cat="actuality" nb="-1"]</pre>

<h5>[events_cal]</h5>
<ul>
<li><b>cat=''</b> <i>(string, select posts only from the selected category, default=null, for all categories)</i></li>
<li><b>date=''</b> <i>(string, date for a month. Absolutly : 2013-9 or relatively : -1 month, default is empty, current month</i></li>
<li><b>datepicker=1</b> <i>(boolean, displays or not a date picker</i></li>
<li><b>mondayfirst=0</b> <i>(boolean, weeks start on monday, default is 0 (sunday)</i></li>
</ul>
example : <pre>[events_cal cat="actuality" date="-2 months" mondayfirst=1]</pre>

== Installation ==

1. Upload `event-post` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress admin
3. Create a new page and insert the short code `[eelv_news_form]`
4. You can edit defaults settings in Settings > Event post

== Frequently asked questions ==

= Is the plugin fetches free ? =
Yes, and it uses only open-sources : openstreetmap, openlayer, jquery


== Screenshots ==

<img src="http://ecolosites.eelv.fr/files/2013/03/single.png"/>
<img src="http://ecolosites.eelv.fr/files/2013/03/admin.png"/>
<img src="http://ecolosites.eelv.fr/files/2013/03/carte.png"/>


== Changelog ==

= 2.4.0 =
* Add : Calendar widget/shortcode

= 2.3.3 =
* Add : Improve address search UI
* Fix : Address search bug fix

= 2.3.2 =
* Add : make the function "EventPost::get_events" usable with an array as param
* Fix : Use of https links
* Fix : Change licence from CC BY-NC to GPLv3

= 2.3.1 =
* Fix : OSM map link error

= 2.3.0 =
* Add : update openlayer version to 2.13.1
* Add : change Map UI buttons
* Add : Shortcode editor
* Fix : Minor JS bug

= 2.2.4 =
* Fix : Quick edit was removing date and geo datas
* Fix : PHP Warning

= 2.2.3 =
* Add : Title, before_title and after_title attributes to shortcode functions
* Fix : Do not display empty title in widget

= 2.2.2 =
* Add : add custom box to all post-types

= 2.2.1 =
* Fix : bad output

= 2.2.0 =
* Add : Admin settings page : choose a date format and a default map background
* Add : Tile option for map shortcode, select a map background for a particular map
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
