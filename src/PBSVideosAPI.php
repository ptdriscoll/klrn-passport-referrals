<?php
namespace PassportReferrals;

/**
 * PBS Media Manager client to retrieve specific video data
 * Reference, Media Manager API:
 * https://docs.pbs.org/display/CDA/Media+Manager+API
 * 
 * Uses the PBS Media Manager API client  
 * https://github.com/tamw-wnet/PBS_Media_Manager_Client
 */
class PBSVideosAPI {
    private $client;

    /**
     * @param array $config 
     */
    function __construct($config) {
        require_once 'PBSMediaManagerAPI.php';
        $this->client = new PBSMediaManagerAPI(
            $client_id = $config['pbs_client_id'], 
            $client_secret = $config['pbs_client_secret'], 
            $base_endpoint = 'https://media.services.pbs.org/api/v1'
        );
    } 

    /**
     * @param string $referrer 
     *   such as https://www.pbs.org/video/episode-1-zjybup/
     * @return array from call to PBS Media Manager API
     */
    function getData($referrer) {
        $referrer = preg_replace('/[\/]+$/', '', $referrer); //remove any and all slashes at end
        $referrerArr = explode('/', $referrer);
        $videoSlug = end($referrerArr);

        //if slug is a wnet url with a custom id at end, then move pointer to prev 
        if (is_numeric($videoSlug) 
            && strlen($videoSlug) < 8
            && strpos($referrer, '/wnet/')) {
                $videoSlug = prev($referrerArr);
        }
        
        //if slug is a number, treat it as a possible legacy tp media id
        //otherwise use normal asset api 
        if (is_numeric($videoSlug)) {
            $data = $this->client->get_asset_by_tp_media_id($videoSlug);
        }
        else $data = $this->client->get_asset($videoSlug);        
        return $data;
    }

    /**
     * @param string $showID 
     *   such as e1321889-7efc-4fbd-9f4a-d3f77b318bb0
     * @return string from call to PBS Media Manager API
     */
    function getGenre($showID) {
        if ($showID) {            
            $data = $this->client->get_show($showID);   
            $genre = $data['data']['attributes']['genre']['title'] ?? NULL;            
        }
        else $genre = NULL;       
        return $genre;
    }    

    /**
     * Prints api response array recursively with indentation     
     * @param array $arr array to process
     * @param string $indent indentation string
     * Reference https://stackoverflow.com/questions/3684463/php-foreach-with-nested-array
     */
    function printRawDataRecursively($arr) {
        print '<br><br>=========================
        .<br>RAW DATA RECURSIVE ARRAY<br>=========================';

        function printRecursively($arr, $indent='') {
            if ($arr) {
                foreach ($arr as $key => $value) {
                    if (is_array($value)) {
                        printRecursively($value, $indent . "--$key");
                    } else {
                        print "<br>$indent--$key ::: $value";
                    }
                }
            }
        }
        printRecursively($arr);
    }
    /**
     * @param array $data 
     *   as returned from $this->getData()
     * @return array [string $apiError, array $videoResult, array $showResult]
     */
    function prepData($data) {
        $apiError = NULL;
        $videoResult = [];
        $showResult = [];

        if (array_key_first($data) === 'errors') {
            $response = $data['errors']['response'];
            if (array_key_exists('detail', $response)) $api_error = 'Not published';
            elseif (array_key_exists(5, $response)) $api_error = 'Not found on server';
            else $api_error= 'Error message not found';
            $apiError = $api_error;
        }
    
        else {
            $attributes = $data['data']['attributes'];
            $type = $attributes['parent_tree']['type'];
    
            if ($type === 'episode') {
                $show = $attributes['parent_tree']['attributes']['season']['attributes']['show'];
            }
            elseif ($type === 'special') {
                $show = $attributes['parent_tree']['attributes']['show'];
            }
            else $type = NULL;

            $videoResult['type'] = $type;
            $videoResult['content_id'] = $data['data']['id'];
            $videoResult['slug'] = $attributes['slug'];
            $videoResult['title'] = $attributes['title'];
            $videoResult['description_short'] = $attributes['description_short'];
            $videoResult['description_long'] =$attributes['description_long'];
            $videoResult['premiered_on'] = $attributes['premiered_on'];
            $videoResult['duration'] = $attributes['duration'];
            $videoResult['content_rating'] = $attributes['content_rating'];
            $videoResult['legacy_tp_media_id'] = $attributes['legacy_tp_media_id'];
            $videoResult['image'] = $attributes['images'][0]['image'];
            $videoResult['shows_id'] = NULL;

            if ($type) {
                $showResult['content_id'] = $show['id'];
                $showResult['slug'] = $show['attributes']['slug'];
                $showResult['title'] = $show['attributes']['title'];
                $showResult['genre'] = $this->getGenre($show['id']);
            }
        }
        return [$apiError, $videoResult, $showResult];
    }

    /**
     * @param array $data 
     *   as returned from $this->prepData()
     * @param string $version
     *   whether VIDEO or SHOW
     */
    function printPreppedData($data, $version='') {
        print '<br><br>========================='
             .'<br>PREPPED '.$version.' DATA'
             .'<br>=========================';

        foreach($data as $key => $value) {
            print '<br>'.$key.': '.$value; 
        }
    }
}
