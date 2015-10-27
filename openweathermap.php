<?php
/**
 * Provides weather support thanks to OpenWeatherMap
 * http://openweathermap.org
 *
 * License: Creative Commons (cc-by-sa)
 * http://creativecommons.org/licenses/by-sa/2.0/.
 *
 *
 * Get an API key
 * http://openweathermap.org/appid#get
 *
 *
 * API examples
 *
 * current
 * http://api.openweathermap.org/data/2.5/weather?lat={lat}&lon={lon}&APPID=XXXX
 * return:
        {"coord":{"lon":139,"lat":35},
        "sys":{"country":"JP","sunrise":1369769524,"sunset":1369821049},
        "weather":[{"id":804,"main":"clouds","description":"overcast clouds","icon":"04n"}],
        "main":{"temp":289.5,"humidity":89,"pressure":1013,"temp_min":287.04,"temp_max":292.04},
        "wind":{"speed":7.31,"deg":187.002},
        "rain":{"3h":0},
        "clouds":{"all":92},
        "dt":1369824698,
        "id":1851632,
        "name":"Shuzenji",
        "cod":200}
 *
 * forecast
 * api.openweathermap.org/data/2.5/forecast?lat={lat}&lon={lon}&APPID=XXXX
 * return:
       {"city":{"id":1851632,"name":"Shuzenji",
       "coord":{"lon":138.933334,"lat":34.966671},
       "country":"JP",
       "cod":"200",
       "message":0.0045,
       "cnt":38,
       "list":[{
               "dt":1406106000,
               "main":{
                   "temp":298.77,
                   "temp_min":298.77,
                   "temp_max":298.774,
                   "pressure":1005.93,
                   "sea_level":1018.18,
                   "grnd_level":1005.93,
                   "humidity":87
                   "temp_kf":0.26},
               "weather":[{"id":804,"main":"Clouds","description":"overcast clouds","icon":"04d"}],
               "clouds":{"all":88},
               "wind":{"speed":5.71,"deg":229.501},
               "sys":{"pod":"d"},
               "dt_txt":"2014-07-23 09:00:00"}
               ]}
 *
 * history
 * http://api.openweathermap.org/data/2.5/history/city?lat={lat}&lon={lon}&type=hour&start={start}&end={end}&APPID=XXXX
 *   Parameters:
 *   lat, lon coordinates of the location of your interest
 *   type type of the call, keep this parameter in the API call as 'hour'
 *   start start date (unix time, UTC time zone), e.g. start=1369728000
 *   end end date (unix time, UTC time zone), e.g. end=1369789200
 *   cnt amount of returned data (one per hour, can be used instead of 'end') *
 * return:
        {"message":"","cod":"200","type":"tick","station_id":39419,"cnt":30,
        "list":[
        {"dt":1345291920,
            "main":{"temp":291.55,"humidity":95,"pressure":1009.3},
            "wind":{"speed":0,"gust":0.3},
            "rain":{"1h":0.6,"today":2.7},
            "calc":{"dewpoint":17.6} }
        ]}
 *
 */

$EventPostWeather = new EventPostWeather();
class EventPostWeather{
    function __construct() {
        // Hook into the plugin

        // Alter objects
        add_filter('eventpost_params', array(&$this, 'params'));
        add_filter('eventpost_retreive', array(&$this, 'retreive'));

        // Alter schema
        add_filter('eventpost_item_scheme_entities', array(&$this, 'scheme_entities'));
        add_filter('eventpost_item_scheme_values', array(&$this, 'scheme_values'));
        add_filter('eventpost_list_shema', array(&$this, 'list_shema'));
    }

    /**
     * PHP4 constructor
     */
    function EventPostWeather(){
        $this->__construct();
    }

    /**
     *
     * @param array $params
     * @return array
     */
    function params($params=array()){
        return $params;
    }
    /**
     *
     * @param WP_Post $event
     * @return WP_Post
     */
    function retreive($event){
        return $event;
    }
    /**
     *
     * @param array $attr
     * @return array
     */
    function scheme_entities($attr=array()){
        return $attr;
    }
    /**
     *
     * @param array $values
     * @return array
     */
    function scheme_values($values=array()){
        return $values;
    }
    /**
     *
     * @param array $schema
     * @return array
     */
    function list_shema($schema){
        return $schema;
    }


    /**
     * From here, methods intends to get datas
     */
}