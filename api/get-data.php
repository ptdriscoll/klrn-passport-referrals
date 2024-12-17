<?php
require_once '../vendor/autoload.php';
use PassportReferrals\GoogleAnalyticsAPI;

require_once '../database/helpers.php';
$config = require '../auth/config.php';
$conn = require '../database/conn.php';
$sql = require '../database/sql.php';
    
if ($_SERVER['REQUEST_METHOD'] == 'POST') {   

    //set time zone
    date_default_timezone_set($config['timezone']);
    $conn->query("SET time_zone = '".date('P')."'");

    //get the date data collection started, and last day for which it was collected
    $minDate = $config['default_start_date'];
    $maxDate = GoogleAnalyticsAPI::getLastUpdateDay($conn, $config);
    $availableDates = ['min' => $minDate, 'max' => $maxDate]; 

    //get json from http raw request and decode
    $req = file_get_contents('php://input');
    $req = json_decode($req, true);

    //get requested date range for sql select
    $startDate = htmlspecialchars(trim($req['start']));
    $endDate = htmlspecialchars(trim($req['end']));

    //set defaults for error reporting
    $errors = [
        'anyErrorsExist' => false,
        'datesEmpty' => false, 
        'datesNotFormatted' => false,
        'dataNotAvailable' => false,
        'datesNotOrdered' => false,
    ];    

    //if date range is default, set for 28 days, or at start of data collection
    //else, if a date range was requested, check and report any errors for input dates
    if ($startDate == 'default' || $endDate == 'default') {
        $endDate = $maxDate;   
        $startDate = setStartDate($minDate, $endDate);
    } 
    else { 
        //dates must not be empty 
        $errors['datesEmpty'] = empty($startDate) || empty($endDate);

        //dates must be formatted correctly 
        $startDateFormatted = empty(preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate));
        $endDateFormatted = empty(preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate));
        $errors['datesNotFormatted'] = $startDateFormatted || $endDateFormatted ;

        //if dates are correct, check availability date and date order
        if (!$errors['datesEmpty'] && !$errors['datesNotFormatted']) {
            $minDateObj = new DateTime($minDate);
            $startDateObj = new DateTime($startDate);
            $endDateObj =  new DateTime($endDate);
            $errors['dataNotAvailable'] = $minDateObj > $startDateObj;
            $errors['datesNotOrdered'] = $startDateObj > $endDateObj;
        } else {
            $errors['datesNotAvailable'] = 'not checked';
            $errors['datesNotOrdered'] = 'not checked';
        }

        //record whether any errors are true
        $errors['anyErrorsExist'] = in_array(true, array_values($errors), true);
    }

    //set variables for data response
    if (!isset($log)) $log = 'none';
    $requestedDates = ['start' => $startDate, 'end' => $endDate];
    $referralsTrends = [];
    $shows = [];
    $showsTrends = [];
    $episodes = [];    
    $episodesTrending = [];
    $episodesTrends = [];

    //if there are no errors, get shows and episodes from database
    if (!$errors['anyErrorsExist']) {

        //get top shows
        $shows = get_data($conn, $sql['select_shows'], 
                          $sql['select_shows_types'], 
                          [$startDate, $endDate]);

        //get top episodes
        $episodes = get_data($conn, $sql['select_episodes'], 
                             $sql['select_episodes_types'], 
                             [$startDate, $endDate]);

        //set trends start date 
        $startTrendsDate = setStartDate($minDate, $endDate, '-7 days');
        $requestedDates['trendsStart'] = $startTrendsDate;

        //get daily referrals
        $referralsTrendsAvailable = get_data($conn, $sql['select_referrals'], 
                                    $sql['select_referrals_types'], 
                                    [$startTrendsDate, $endDate]); 

        //now create daily referrals array with any missing dates filled in
        $referralsTrends = [];
        $currentDateObj = new DateTime($startTrendsDate);
        $endDateObj = new DateTime($endDate);
        $idx = 0;

        while ($currentDateObj <= $endDateObj) {
            $currentDateStr = $currentDateObj->format('Y-m-d');
            $isIdx = isset($referralsTrendsAvailable[$idx]);

                //if index exists in available data and dates match, copy data over 
                if ($isIdx && $currentDateStr === $referralsTrendsAvailable[$idx]['Date']) {
                    $referralsTrends[] = $referralsTrendsAvailable[$idx];
                    $idx += 1;
                }
                else {
                    //otherwise, add placeholder with 0 Pageviews
                    $referralsTrends[] = ['Date' => $currentDateStr, 'Pageviews' => '0'];
                }
            $currentDateObj->modify('+1 day');
        }                                    

        //get show trends
        $topShows = array_slice($shows, 0, 3);
        $topShowsIDs = array_map(function($arr) {return $arr['ID'];}, $topShows);
        $topShowsIDs = array_pad($topShowsIDs, 3, -1);
        $bindVars = array_merge([$startTrendsDate, $endDate], $topShowsIDs);
        $showsTrends = get_data($conn, $sql['select_shows_trends'], 
                                $sql['select_shows_trends_types'], 
                                $bindVars);

        //get top trending episodes
        $episodesTrending = get_data($conn, $sql['select_episodes'], 
                                     $sql['select_episodes_types'], 
                                     [$startTrendsDate, $endDate]);
        $topEpisodesTrending = array_slice($episodesTrending, 0, 3);    
        $topEpisodesTrendingIDs = array_map(function($arr) {return $arr['VideoID'];}, $topEpisodesTrending);
        $topEpisodesTrendingIDs = array_pad($topEpisodesTrendingIDs, 3, -1);
        $bindVars = array_merge([$startTrendsDate, $endDate], $topEpisodesTrendingIDs);
        $episodesTrends = get_data($conn, $sql['select_episodes_trends'], 
                                   $sql['select_episodes_trends_types'], 
                                   $bindVars);  
       
        //close database connection
        $conn->close();
    } 

    //return results
    $res = [
        'log' => $log,
        'errors' => $errors,
        'availableDates' => $availableDates, 
        'requestedDates' => $requestedDates,
        'referralsTrends' => $referralsTrends,
        'shows' => $shows,
        'showsTrends' => $showsTrends,
        'episodes' => $episodes,        
        'episodesTrending' => $episodesTrending,
        'episodesTrends' => $episodesTrends,        
    ];

    echo json_encode($res);
    exit();
}
?>
