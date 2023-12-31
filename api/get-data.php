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
    $episodes = [];
    $showsTrends = [];
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
        $referralsTrends = get_data($conn, $sql['select_referrals'], 
                                    $sql['select_referrals_types'], 
                                    [$startTrendsDate, $endDate]); 

        //get show trends
        $topShows = array_slice($shows, 0, 3);
        $topShowsIDs = array_map(function($arr) {return $arr['ID'];}, $topShows);
        $bindVars = array_merge([$startTrendsDate, $endDate], $topShowsIDs);
        $showsTrends = get_data($conn, $sql['select_shows_trends'], 
                                $sql['select_shows_trends_types'], 
                                $bindVars);

        //get episode trends
        $topEpisodes = array_slice($episodes, 0, 3);        
        $topEpisodesIDs = array_map(function($arr) {return $arr['VideoID'];}, $topEpisodes);
        $log = $topEpisodesIDs;
        $bindVars = array_merge([$startTrendsDate, $endDate], $topEpisodesIDs);
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
        'episodes' => $episodes,
        'showsTrends' => $showsTrends,
        'episodesTrends' => $episodesTrends,
    ];

    echo json_encode($res);
    exit();
}
?>
