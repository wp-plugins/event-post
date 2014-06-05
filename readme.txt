=== Event post ===
Contributors: bastho, ecolosites
Donate link: http://eelv.fr/adherer/
Tags: Post,posts,event,date,geolocalization,gps,widget,map,openstreetmap,calendar
Requires at least: 3.8
Tested up to: 3.9.1
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
<h6>Query parameters</h6>
<ul>
<li><b>nb=5</b> <i>(number of post, -1 is all, default: 5)</i></li>
<li><b>future=1</b> <i>(boolean, retreive, or not, events in the future, default = 1)</i></li>
<li><b>past=0</b> <i>(boolean, retreive, or not, events in the past, default = 0)</i></li>
<li><b>cat=''</b> <i>(string, select posts only from the selected category, default=null, for all categories)</i></li>
<li><b>tag=''</b> <i>(string, select posts only from the selected tag, default=null, for all tags)</i></li>
<li><b>geo=0</b> <i>(boolean, retreives or not, only events wich have geolocation informations, default=0)</i></li>
<li><b>order="ASC"</b> <i>(string (can be "ASC" or "DESC")</i></li>
<li><b>orderby="meta_value"</b> <i>(string (if set to "meta_value" events are sorted by event date, possible values are native posts fileds : "post_title","post_date" etc...)</i></li>
</ul>
<h6>Display parameters</h6>
<ul>
<li><b>thumbnail=""</b> <i> (Bool, default:false, used to display posts thumbnails)</i></li>
<li><b>thumbnail_size=""</b> <i> (String, default:"thmbnail", can be set to any existing size : "medium","large","full" etc...)</i></li>
<li><b>excerpt=""</b> <i> (Bool, default:false, used to display posts excerpts)</i></li>
<li><b>style=""</b> <i> (String, add some inline CSS to the list wrapper)</i></li>
<li><b>type=div</b> <i>(string, possible values are : div, ul, ol default=div)</i></li>
<li><b>title=''</b> <i>(string, hidden if no events is found)</i></li>
<li><b>before_title="&lt;h3&gt;"</b> <i>(string (default &lt;h3&gt;)</i></li>
<li><b>after_title="&lt;/h3&gt;"</b> <i>(string (default &lt;/h3&gt;)</i></li>
<li><b>container_schema=""</b> <i>(string html schema to display list)</i>
default value :
<pre>
	&lt;%type% class="event_loop %id% %class%" id="%listid%" style="%style%" %attributes%&gt;
		%list%
	&lt;/%type%&gt;
</pre>
</li>
<li><b>item_schema="" <i>(string html schema to display item)
default value :
<pre>
	&lt;%child% class="event_item %class%" data-color="%color%"&gt;
	      	&lt;a href="%event_link%"&gt;
	      		%event_thumbnail%
	      		&lt;h5>%event_title%&lt;/h5&gt;
	      	&lt;/a&gt;
      		%event_date%
      		%event_cat%
      		%event_location%
      		%event_excerpt%
     &lt;/%child%&gt;
</pre>
</li>
</ul>
example : <pre>[events_list future=1 past=1 cat="actuality" nb=10]</pre>

<h5>[events_map]</h5>
<ul>
<li><b>nb=5</b> <i>(number of post, -1 is all, default: 5)</i></li>
<li><b>future=1</b> <i>(boolean, retreive, or not, events in the future, default = 1)</i></li>
<li><b>past=0</b> <i>(boolean, retreive, or not, events in the past, default = 0)</i></li>
<li><b>cat=''</b> <i>(string, select posts only from the selected category, default=null, for all categories)</i></li>
<li><b>tag=''</b> <i>(string, select posts only from the selected tag, default=null, for all tags)</i></li>
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

= Hooks =
<h5>Filters</h5>
eventpost_printdate
eventpost_printlocation
eventpost_params
eventpost_get
eventpost_retreive
eventpost_multisite_get
eventpost_multisite_blogids

<h5>Actions</h5>
before_eventpost_generator
after_eventpost_generator




== Installation ==

1. Upload `event-post` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress admin
3. Create a new page and insert the short code `[eelv_news_form]`
4. You can edit defaults settings in Settings > Event post

== Frequently asked questions ==

= Is the plugin fetches free ? =
Yes, and it uses only open-sources : openstreetmap, openlayer, jquery


== Screenshots ==

1. Single page
2. Editor interface
3. Map

== Changelog ==

= 2.8.6 =
* Fix : Eror while retreiving the excerpt

= 2.8.5 =
* Add : Setting to print/hide link for events with empty content
* Fix : Check content with queried object instead of global $post
* Fix : Bug in calendar animations

= 2.8.4 =
* Fix : Optimize JS in admin side
* Add : French and chinese localisation for date-picker
* Add : Minify CSS

= 2.8.3 =
* Fix : bug fix

= 2.8.2 =
* Fix : apply content filter most later

= 2.8.1 =
* Fix : content filter bug on home page

= 2.8.0 =
* Add : attributes to events_list shortcode : 
* * thumbnail=(true/false) 
* * thumbnail_size=thumbnail 
* * excertp=(true/false)
* * container_schema (documentation comming soon)
* * item_schema (documentation comming soon)
* Add : Usage of the event color for single details
* Enhance : Event information form UI
* Fix : Re-check if end date is after begin date
* Fix : CSS adjustments
* Fix : CSS adjustments
* Fix : Prevent from filters applying "the_content" on another thing than the current post content


= 2.7.1 =
* Fix : Really check all blogs when using "blogs=all" in shortcodes. May cause memory limit on big networks

= 2.7.0 =
* Add : Multisite event list support
* Add : Integration of several hooks
* Add : Map widget
* Add : Parameters to display or not export buttons
* Add : Native WP icons for map and calendar items
* Add : data-color in list items
* Fix : Event's first day not shown in calendar
* Fix : Use of minified JS files

= 2.6.0 =
* Add : order and orderby parameters for shortcode [events_list]

= 2.5.0 =
* Add : tag and style parameters for shortcode [events_list]

= 2.4.1 =
* Fix : Parameters bug in export files

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

= 2.7.0 =
* The event meta box is no more displayed for non posts items such as pages or custom post-types
* Please active the multisite plugin in order to allow your users to browse events from the network

== Languages ==

= Français  =
* fr_FR : 100%

= English =
* en	: 100%
