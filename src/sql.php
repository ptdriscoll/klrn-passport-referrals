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
                                  AND TABLE_NAME = 'pages'"                                 
];
