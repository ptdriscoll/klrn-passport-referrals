<?php
return [
    'insert_show_types' => "ssss",
    'insert_show' => "INSERT INTO shows(content_id, slug, title, genre)
                      VALUES (?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE
                          id = LAST_INSERT_ID(id),
                          slug = VALUES(slug),
                          title = VALUES(title),
                          genre = VALUES(genre)",

    'insert_video_types' => "sssssssisisi",
    'insert_video' => "INSERT INTO videos(type, content_id, slug, title, description_short, 
                           description_long, premiered_on, duration, content_rating, 
                           legacy_tp_media_id, image, shows_id)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE
                           id=LAST_INSERT_ID(id),
                           slug = VALUES(slug),
                           title = VALUES(title),
                           description_short = VALUES(description_short),
                           description_long = VALUES(description_long),
                           premiered_on = VALUES(premiered_on),
                           duration = VALUES(duration),
                           content_rating = VALUES(content_rating),
                           legacy_tp_media_id = VALUES(legacy_tp_media_id),
                           image = VALUES(image),
                           shows_id = VALUES(shows_id)",

    'insert_page_types' => "ssiiissi",
    'insert_page' => "INSERT INTO pages(date, page, views, users, duration, 
                          referrer, video_api_error, videos_id)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE
                          id=LAST_INSERT_ID(id),
                          views = VALUES(views), 
                          users = VALUES(users), 
                          duration = VALUES(duration), 
                          referrer = VALUES(referrer),
                          video_api_error = VALUES(video_api_error), 
                          videos_id = VALUES(videos_id)",
                          
    'select_last_update_time_types' => "s",
    'select_last_update_time' => "SELECT UPDATE_TIME
                                  FROM information_schema.tables
                                  WHERE  TABLE_SCHEMA = ?
                                  AND TABLE_NAME = 'pages'",

    'select_referrals_types' => "ss",
    'select_referrals' => "SELECT
                               pages.date AS `Date`,
                               SUM(pages.views) AS `Pageviews`,
                               SUM(pages.users) AS `Users`,
                               SUM(pages.duration) AS `Duration`
                           FROM pages
                           WHERE pages.date >= ?
                           AND pages.date <= ?
                           GROUP BY `Date`
                           ORDER BY `Date`;",                                  
    
    'select_shows_types' => "ss",
    'select_shows' => "SELECT
                           shows.id AS `ID`, 
                           shows.title AS `Show`,
                           shows.genre AS `Genre`,
                           SUM(pages.views) AS `Pageviews`,
                           SUM(pages.users) AS `Users`,
                           SUM(pages.duration) AS `Duration`
                       FROM pages
                       INNER JOIN videos ON pages.videos_id  = videos.id
                       INNER JOIN shows ON videos.shows_id = shows.id
                       WHERE pages.date >= ?
                       AND pages.date <= ?
                       GROUP BY `ID`
                       ORDER BY `Pageviews` DESC, `Users` DESC, `Show`",
                       
    'select_episodes_types' => "ss",
    'select_episodes' => "SELECT
                              shows.id AS `ShowID`, 
                              shows.title AS `Show`,
                              shows.genre AS `Genre`,  
                              videos.id AS `VideoID`,
                              videos.title AS `Episode`,
                              SUM(pages.views) AS `Pageviews`,
                              SUM(pages.users) AS `Users`,
                              SUM(pages.duration) AS `Duration`
                          FROM pages
                          INNER JOIN videos ON pages.videos_id  = videos.id
                          INNER JOIN shows ON videos.shows_id = shows.id
                          WHERE pages.date >= ?
                          AND pages.date <= ?
                          GROUP BY `ShowID`, `VideoID`
                          ORDER BY `Pageviews` DESC, `Users` DESC, `Show`, `Episode`;",
                          
    'select_shows_trends_types' => "ssiii",
    'select_shows_trends' => "SELECT
                              pages.date AS `Date`,
                              shows.id AS `ID`,
                              shows.title AS `Show`,
                              SUM(pages.views) AS `Pageviews`,
                              SUM(pages.users) AS `Users`,
                              SUM(pages.duration) AS `Duration`
                          FROM pages
                          INNER JOIN videos ON pages.videos_id  = videos.id
                          INNER JOIN shows ON videos.shows_id = shows.id                          
                          WHERE pages.date >= ?
                          AND pages.date <= ?
                          AND shows.id IN (?, ?, ?)
                          GROUP BY `Date`, `ID`
                          ORDER BY `Date`, `ID`;",  
                          
    'select_episodes_trends_types' => "ssiii",
    'select_episodes_trends' => "SELECT
                            pages.date AS `Date`,
                            shows.id AS `ShowID`,
                            shows.title AS `Show`,
                            videos.id AS `VideoID`,
                            videos.title AS `Episode`,
                            SUM(pages.views) AS `Pageviews`,
                            SUM(pages.users) AS `Users`,
                            SUM(pages.duration) AS `Duration`
                        FROM pages
                        INNER JOIN videos ON pages.videos_id  = videos.id
                        INNER JOIN shows ON videos.shows_id = shows.id
                        WHERE pages.date >= ?
                        AND pages.date <= ?
                        AND videos.id IN (?, ?, ?)
                        GROUP BY `Date`, `VideoID`  
                        ORDER BY `Date`, `VideoID`;",                            
];
