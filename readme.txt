=== Event post ===
Contributors: bastho, ecolosites
Donate link: http://ba.stienho.fr/#don
Tags: Post,posts,event,date,geolocalization,gps,widget,map,openstreetmap,calendar
Requires at least: 3.8
Tested up to: 4.1.1
Stable tag: /trunk
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add some meta-datas to posts to convert them into full calendar events :
begin, end, location, color

== Description ==
Add some meta-datas to posts to convert them into full calendar events.
Each event can be exported into ical(.ics), outlook(vcs), or Google Calendar.
Geolocation works thanks to openstreetmap.

= Localization =

* English
* fr_FR : French
* it_IT : Italian by NewHouseStef
* sv_SE : Swedish by mepmepmep


= Post metas =


**Date attributes**

* Begin date-time
* End date-time
* Color

**Location attributes**

* Address
* GPS coordinates

[see full documentation](https://wordpress.org/plugins/event-post/other_notes/)

## Shortcodes
The plugin comes with three shortcodes wich allows to :

* `[events_list]` : display a list of events
* `[events_map]` : display a map of events
* `[events_cal]` : display a calendar of events

### Available options :
#### [events_list]
##### Query parameters
* **nb=5** *(number of post, -1 is all, default: 5)*
* **future=1** *(boolean, retreive, or not, events in the future, default = 1)*
* **past=0** *(boolean, retreive, or not, events in the past, default = 0)*
* **cat=''** *(string, select posts only from the selected category, default=null, for all categories)*
* **tag=''** *(string, select posts only from the selected tag, default=null, for all tags)*
* **geo=0** *(boolean, retreives or not, only events wich have geolocation informations, default=0)*
* **order="ASC"** *(string (can be "ASC" or "DESC")*
* **orderby="meta_value"** *(string (if set to "meta_value" events are sorted by event date, possible values are native posts fileds : "post_title","post_date" etc...)*

##### Display parameters

* **thumbnail=""** * (Bool, default:false, used to display posts thumbnails)*
* **thumbnail_size=""** * (String, default:"thmbnail", can be set to any existing size : "medium","large","full" etc...)*
* **excerpt=""** * (Bool, default:false, used to display posts excerpts)*
* **style=""** * (String, add some inline CSS to the list wrapper)*
* **type=div** *(string, possible values are : div, ul, ol default=div)*
* **title=''** *(string, hidden if no events is found)*
* **before_title="&lt;h3&gt;"** *(string (default &lt;h3&gt;)*
* **after_title="&lt;/h3&gt;"** *(string (default &lt;/h3&gt;)*
* **container_schema=""** *(string html schema to display list)*
* **item_schema=""** *(string html schema to display item)*

example : `[events_list future=1 past=1 cat="actuality" nb=10]`

container_schema default value :

>	&lt;%type% class="event_loop %id% %class%" id="%listid%" style="%style%" %attributes%&gt;
>		%list%
>	&lt;/%type%&gt;
>


item_schema default value :

>	&lt;%child% class="event_item %class%" data-color="%color%"&gt;
>	      	&lt;a href="%event_link%"&gt;
>	      		%event_thumbnail%
>	      		&lt;h5>%event_title%&lt;/h5&gt;
>	      	&lt;/a&gt;
>     		%event_date%
>      		%event_cat%
>      		%event_location%
>      		%event_excerpt%
>     &lt;/%child%&gt;
>

####[events_map]

* **nb=5** *(number of post, -1 is all, default: 5)*
* **future=1** *(boolean, retreive, or not, events in the future, default = 1)*
* **past=0** *(boolean, retreive, or not, events in the past, default = 0)*
* **cat=''** *(string, select posts only from the selected category, default=null, for all categories)*
* **tag=''** *(string, select posts only from the selected tag, default=null, for all tags)*
* **tile=''** *(string (default@osm.org, OpenCycleMap, mapquest, osmfr, 2u, satelite, toner), sets the map background, default=default@osm.org)*
* **title=''** *(string (default )*
* **before_title="&lt;h3&gt;"** *(string (default &lt;h3&gt;)*
* **after_title="&lt;/h3&gt;"** *(string (default &lt;/h3&gt;)** **thumbnail=""** * (Bool, default:false, used to display posts thumbnails)*
* **excerpt=""** * (Bool, default:false, used to display posts excerpts)*

example: `[events_map future=1 past=1 cat="actuality" nb="-1"]`

####[events_cal]

* **cat=''** *(string, select posts only from the selected category, default=null, for all categories)*
* **date=''** *(string, date for a month. Absolutly : 2013-9 or relatively : -1 month, default is empty, current month*
* **datepicker=1** *(boolean, displays or not a date picker*
* **mondayfirst=0** *(boolean, weeks start on monday, default is 0 (sunday)*

example: `[events_cal cat="actuality" date="-2 months" mondayfirst=1]`

= Hooks =
#### Filters
* eventpost_printdate
* eventpost_printlocation
* eventpost_params
* eventpost_get
* eventpost_retreive
* eventpost_multisite_get
* eventpost_multisite_blogids
* eventpost_list_shema

#### Actions
* before_eventpost_generator
* after_eventpost_generator

== Installation ==

1. Upload `event-post` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress admin
3. You can edit defaults settings in Settings > Event post

== Frequently asked questions ==

= Is the plugin fetches free ? =
Yes, and it uses only open-sources : openstreetmap, openlayer, jquery


== Screenshots ==

1. Single page
2. Editor interface
3. Map

== Changelog ==

= 3.4.2 =
* Fix: remove PHP warnings
* Fix: JS script not loaded when the "calendar widget" is alone

= 3.4.1 =
* Fix: remove PHP warnings

= 3.4.0 =
* Add: Whole category ICS feed (link available in list widget, for future events)
* Fix: JS was not loaded in single events since last version
* Fix: Strict Standards warning, reported by argad
* Fix: Depence of OpenLayer librairy not needed by calendar widget, reported by p1s1

= 3.3.0 =
* Add: eventpost_list_shema filter
* Add: Global container/item shema settings
* Add: Security improvement in settings management
* Fix: Load scripts only of needed

= 3.2.4 =
* Fix: Previous fix fix

= 3.2.3 =
* Fix: Custom icons fix

= 3.2.2 =
* Fix: Category filter
* Fix: Add max zoom

= 3.2.1 =
* Fix: Event list widget : missing title

= 3.2.0 =
* Add: Italian localization, thanks to NewHouseStef

= 3.1.1 =
* Fix: Future/past display style

= 3.1.0 =
* Add: Save default settings to improve performances
* Add: More options in list and map widgets

= 3.0.0 =
* Update to OpenLayer3
* Add: Responsive support
* Add: Satelite and Toner view
* Add: `cat` attributes now accepts multiple categories values ( cat="1,2,3" )
* Add: Custom markers directory for developpers
* Add: Global "event bar position" option : before or after the single content
* Fix: Cleaner settings page

= 2.8.12 =
* Add : Swedish localization, thanks to Mepmepmep

= 2.8.11 =
* Fix : PHP warnings on empty dates

= 2.8.10 =
* Fix : 00 minutes bug

= 2.8.9 =
* Fix : Bug fix
* Change : Multisite support is no more a separated plugin

= 2.8.8 =
* Fix : Empty date error

= 2.8.7 =
* Fix : JS error in minified osm-admin.js file

= 2.8.6 =
* Fix : Error while retreiving the excerpt

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