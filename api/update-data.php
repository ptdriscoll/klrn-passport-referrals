<?php
require_once '../vendor/autoload.php';
use PassportReferrals\GoogleAnalyticsAPI;
use PassportReferrals\PBSVideosAPI;

$config = require '../auth/config.php';
$conn = require '../database/conn.php';
$sql = require '../database/sql.php';

//set time zone
date_default_timezone_set($config['timezone']);
$conn->query("SET time_zone = '".date('P')."'");

print '<br>START UPDATE: '.date('Y-m-d H:i:s');

//instantiate apis
$analytics = new GoogleAnalyticsAPI($config);
$videos = new PBSVideosAPI($config);

//make api call to GA4 analytics
//dates are inclusive, and in formats YYYY-MM-DD, NdaysAgo, yesterday or today
$startDate = $analytics->getLastUpdateDay($conn, $config);
$endDate = 'yesterday';
print '<br><br>START DATE: '.$startDate;
print '<br>END DATE: '.$endDate;

$analyticsData = $analytics->getData($startDate, $endDate);

//prepare database insert statements
$showStatement = $conn->prepare($sql['insert_show']);
$videoStatement = $conn->prepare($sql['insert_video']);
$pageStatement = $conn->prepare($sql['insert_page']);

//loop through page analytics results
foreach ($analyticsData->getRows() as $key => $row) {    
    print '<br><br>============================================================'
         .'<br>=========================== ROW '.$key.' ==========================='                          
         .'<br>============================================================';

    //for each row, prep page analytics data, 
    //then make api call to PBS Media Manager, and prep that data
    $pageData = $analytics->prepRowData($row);
    $videoRawData = $videos->getData($pageData['referrer']);
    [$apiError, $videoData, $showData] = $videos->prepData($videoRawData);

    if ($apiError) $pageData['video_api_error'] = $apiError;
    else {
        //insert prepped show data into database shows table
        $showDataValues = array_values($showData);
        $showStatement->bind_param($sql['insert_show_types'], ...$showDataValues);
        if ($showStatement->execute()) $shows_id = $conn->insert_id; 
        
        //insert prepped video data into database shows table
        $videoData['shows_id'] = $shows_id; //reset from null
        $videoDataValues = array_values($videoData); 
        $videoStatement->bind_param($sql['insert_video_types'], ...$videoDataValues);
        if ($videoStatement->execute()) $videos_id = $conn->insert_id; 
        
        //reset videos_id in $pageData from null
        $pageData['videos_id'] = $videos_id; 
    }

    //insert prepped page analytics data into database pages table    
    $pageDataValues = array_values($pageData);
    $pageStatement->bind_param($sql['insert_page_types'], ...$pageDataValues);
    if ($pageStatement->execute()) $pages_id = $conn->insert_id; 

    //print ids from database insertions
    print '<br>';
    print '<br>SHOWS ID: '.($videoData['shows_id'] ?? 'NULL');
    print '<br>VIDEOS ID: '.($pageData['videos_id'] ?? 'NULL');
    print '<br>PAGES ID: '.$pages_id;

    //print raw analytics results
    //$analytics->printRawRowData($row);
    
    //print raw api responses recursively for specific videos,
    //here listed as referrer from $pageData['referrer']
    $referrers = [
        //'https://www.pbs.org/kenburns/benjamin-franklin/',
        //'https://video.pbs.org/video/out-past/', 
    ];           
    if (in_array($pageData['referrer'], $referrers)) {
        $videos->printRawDataRecursively($videoRawData);    
    } 
    
    //print prepped results
    $videos->printPreppedData($showData, 'SHOW');
    $videos->printPreppedData($videoData, 'VIDEO');
    $analytics->printPreppedRowData($pageData);   
}

//get time of last update
$updatedStatement = $conn->prepare($sql['select_last_update_time']);
$updatedStatement->bind_param($sql['select_last_update_time_types'], $config['db_name']);
if ($updatedStatement->execute()) {
    $updatedTime = $updatedStatement->get_result();
    $updatedTime = $updatedTime->fetch_assoc()['UPDATE_TIME'];
}

print '<br><br>============================================================'
     .'<br>========================= FINISHED =========================='                          
     .'<br>============================================================';

print '<br><br>END UPDATE: '.date('Y-m-d H:i:s');
print '<br><br>DATABASE LAST UPDATED: '.$updatedTime.'<br><br>';

//close everything
$showStatement->close();
$videoStatement->close();
$pageStatement->close();
$updatedStatement->close();
$conn->close();
