<?php
namespace PassportReferrals;

require_once '../vendor/autoload.php';
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;

/**
 * Google Analytics Data API (GA4) client
 * Reference, API Quickstart:
 * https://developers.google.com/analytics/devguides/reporting/data/v1/
 */
class GoogleAnalyticsAPI {
    private $property_id;
    private $client;

    /**
     * @param array $config 
     */
    function __construct($config) {
        //set Google Analytics 4 property ID
        $this->property_id = $config['ga4_property_id'];

        //set environment variable with path to Google Cloud service account credentials
        $path = $config['google_credentials_path'];
        putenv("GOOGLE_APPLICATION_CREDENTIALS=$path");

        //using default constructor instructs client to use Google Cloud credentials
        //specified in GOOGLE_APPLICATION_CREDENTIALS environment variable
        $this->client = new BetaAnalyticsDataClient();
    } 

    /**
     * @param object $conn
     *   MySQLi connection      
     * @param array $config 
     * @return string         
     */
    static function getLastUpdateDay($conn, $config) {
        $sql = 'SELECT date FROM pages WHERE id = (SELECT MAX(id) FROM pages)';
        $lastDate = $conn->query($sql);
        if ($lastDate->num_rows) $startDate = $lastDate->fetch_array(MYSQLI_NUM)[0];
        else $startDate = $config['default_start_date'];
        return $startDate;
    }
    
    /**
     * @param string $startDate      
     * @param string $endDate
     *   both are inclusive
     *   in formats YYYY-MM-DD, NdaysAgo, yesterday or today
     *   https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/DateRange        
     * @return array
     */
    function getData($startDate='2daysAgo', $endDate='yesterday') {
        //make API call
        return $this->client->runReport([
            'property' => 'properties/' . $this->property_id,
            'dateRanges' => [
                new DateRange([
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ]),
            ],
            'dimensions' => [
                new Dimension(['name' => 'date',]),
                new Dimension(['name' => 'pagePathPlusQueryString',]),
            ],
            'metrics' => [
                new Metric(['name' => 'screenPageViews',]),
                new Metric(['name' => 'activeUsers',]),
                new Metric(['name' => 'userEngagementDuration',]),
            ],
            'dimensionFilter' => new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'pagePathPlusQueryString',
                    'string_filter'  => new StringFilter([
                        'match_type' => MatchType::PARTIAL_REGEXP,
                        'value'      => '[\?&]referrer=',                
                    ]),
                ]),
            ]),
            'orderBys' => [
                new OrderBy([
                    'dimension' => new DimensionOrderBy([
                        'dimension_name' => 'date',
                    ]),
                    'desc' => false,
                ]),
                new OrderBy([
                    'metric' => new MetricOrderBy([
                        'metric_name' => 'screenPageViews',
                    ]),
                    'desc' => true,
                ]),
            ],
        ]);
    }

    /**
     * @param array $row 
     *   as returned from $this->getData()
     */
    function printRawRowData($row) {
        print '<br><br>=========================
        .<br>RAW PAGE DATA<br>========================='
        .'<br>date: '.$row->getDimensionValues()[0]->getValue() //date
        .'<br>page: '.$row->getDimensionValues()[1]->getValue() //page
        .'<br>views: '.$row->getMetricValues()[0]->getValue() //views
        .'<br>users: '.$row->getMetricValues()[1]->getValue() //users
        .'<br>duration: '.$row->getMetricValues()[2]->getValue(); //duration
    }

    /**
     * @param array $row 
     *   as returned from $this->getData()
     * @return array
     */
    function prepRowData($row) {
        $result = [];

        $date = $row->getDimensionValues()[0]->getValue();
        $date = substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);
        $result['date'] = $date;

        $result['page'] = rawurldecode($row->getDimensionValues()[1]->getValue());
        $result['views'] = (int)$row->getMetricValues()[0]->getValue();
        $result['users'] = (int)$row->getMetricValues()[1]->getValue();
        $result['duration'] = (int)$row->getMetricValues()[2]->getValue();

        $pathArr =  preg_split('/[\?&]referrer=/', $result['page']);
        $referrer = (count($pathArr) > 1) ? $pathArr[1] : '';
        $referrer = str_replace('pbsvideo://', 'https://', $referrer);
        $referrer = preg_split('/[\?&]/', $referrer)[0]; //remove any other queries
        $result['referrer'] = $referrer; 

        $result['video_api_error'] = NULL; 
        $result['videos_id'] = NULL;

        return $result;
    }

    /**
     * @param array $row 
     *   as returned from $this->prepRowData();
     */
    function printPreppedRowData($row) {
        print '<br><br>========================='
             .'<br>PREPPED PAGE DATA'
             .'<br>=========================';

        foreach($row as $key => $value) {
            print '<br>'.$key.': '.$value; 
        }
    }        
}
