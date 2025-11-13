<?php
require_once '../vendor/autoload.php';
require_once '../src/Logger.php';
use PassportReferrals\Logger;
use PassportReferrals\GoogleAnalyticsAPI;
use PassportReferrals\PBSVideosAPI;

$config = require '../auth/config.php';
$conn = require '../database/conn.php';
$sql = require '../database/sql.php';

//set time zone
date_default_timezone_set($config['timezone']);
$conn->query("SET time_zone = '".date('P')."'");
$updateDate = date('Y-m-d H:i:s');

//create file logger, and start logging 
$logger = new Logger(__DIR__ . '/../logs/pbs_api_log.txt');
$logSummary = [
    'success' => 0,
    'pbsErrors' => 0,
    'otherErrors' => 0,
];
$logger->newLine();
$logger->info("=== STARTING SYNC for {$updateDate} ===");

//register shutdown function
register_shutdown_function(function() use ($logger, &$logSummary) {
    $logger->summary($logSummary);
    $lastError = error_get_last();
    if ($lastError && $lastError['type'] === E_ERROR) {
        $logger->newLine();
        $logger->error('FATAL ERROR — ' . $lastError['message']);
    }
});

//start print outputs
print "<br>START UPDATE: {$updateDate}";

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
$logSummary['totalRows'] = count($analyticsData->getRows());

//prepare database insert statements
$showStatement = $conn->prepare($sql['insert_show']);
$videoStatement = $conn->prepare($sql['insert_video']);
$pageStatement = $conn->prepare($sql['insert_page']);

//loop through page analytics results
foreach ($analyticsData->getRows() as $key => $row) {     
    $logger->newLine();
    $logger->info("=== ROW {$key} ===");

    print '<br><br>============================================================'
         .'<br>=========================== ROW '.$key.' ==========================='                          
         .'<br>============================================================';

    try {
        //for each row, prep page analytics data, 
        //then make api call to PBS Media Manager, and prep that data
        //base endpoint: https://media.services.pbs.org/api/v1/assets/{asset_id}/
        $pageData = $analytics->prepRowData($row);
        $date = $pageData['date'];
        $referrer = $pageData['referrer'];
        
        //run video assets through API
        if (strpos($referrer, '/video/') !== false) {
            $videoRawData = $videos->getData($referrer);
            [$apiError, $videoData, $showData] = $videos->prepData($videoRawData);

            if ($apiError) {
                $pageData['video_api_error'] = $apiError;
                $logger->error("FAILED PBS API — [{$date}] Referrer: {$referrer} — {$apiError}");
                $logSummary['pbsErrors']++;       
            }
            else {
                //insert prepped show data into database shows table
                $showDataValues = array_values($showData);
                $showStatement->bind_param($sql['insert_show_types'], ...$showDataValues);
                if ($showStatement->execute()) {
                    $shows_id = $conn->insert_id; 
                    $logger->info("Inserted show ID {$shows_id}");
                }            
                
                //insert prepped video data into database shows table
                $videoData['shows_id'] = $shows_id; //reset from null
                $videoDataValues = array_values($videoData); 
                $videoStatement->bind_param($sql['insert_video_types'], ...$videoDataValues);
                if ($videoStatement->execute()) {
                    $videos_id = $conn->insert_id; 
                    $logger->info("Inserted video ID {$videos_id}");
                }
                
                //reset videos_id in $pageData from null
                $pageData['videos_id'] = $videos_id; 
            }
        }    

        //insert prepped page analytics data into database pages table    
        $pageDataValues = array_values($pageData);
        $pageStatement->bind_param($sql['insert_page_types'], ...$pageDataValues);
        if ($pageStatement->execute()) {
            $pages_id = $conn->insert_id; 
            $logger->info("Inserted page ID {$pages_id}");
        }

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

        $logger->info("SUCCESS — [{$date}] Referrer: {$referrer}");
        $logSummary['success']++;
        
        //print prepped show and video results
        $videos->printPreppedData($showData ?? [], 'SHOW');
        $videos->printPreppedData($videoData ?? [], 'VIDEO');   
             
    } catch (Throwable $e) {
        $logger->error("FAILED OTHER — [{$date}] Referrer: {$referrer} — " . $e->getMessage());
        $logSummary['otherErrors']++;
    } 
    //print prepped page results
    $analytics->printPreppedRowData($pageData); 
}

//log summary
$logger->summary($logSummary);
$logger->newLine();
$logger->info("=== FINISHED SYNC for {$updateDate} ===");

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
