#Get time of last update
##############################
SELECT UPDATE_TIME
FROM information_schema.tables
WHERE  TABLE_SCHEMA = 'passport_referrals'
AND TABLE_NAME = 'pages'  

#Get last day inserted
##############################
SELECT date 
FROM pages 
WHERE id = (SELECT MAX(id) FROM pages);

#Top shows 
##############################
SELECT
  shows.title AS `Show`,
  shows.genre AS `Genre`,
  SUM(pages.views) AS `Pageviews`,
  SUM(pages.users) AS `Users`,
  SUM(pages.duration) AS `Duration`
FROM pages
INNER JOIN videos ON pages.videos_id  = videos.id
INNER JOIN shows ON videos.shows_id = shows.id
WHERE pages.date >= '2023-07-01'
AND pages.date <= '2023-08-30'
GROUP BY `Show`
ORDER BY `Pageviews` DESC, `Users` DESC, `Show`;

#Top episodes 
##############################
SELECT
  shows.title AS `Show`,
  shows.genre AS `Genre`,  
  videos.title AS `Episode`,
  SUM(pages.views) AS `Pageviews`,
  SUM(pages.users) AS `Users`,
  SUM(pages.duration) AS `Duration`
FROM pages
INNER JOIN videos ON pages.videos_id  = videos.id
INNER JOIN shows ON videos.shows_id = shows.id
WHERE pages.date >= '2023-07-01'
AND pages.date <= '2023-08-30'
GROUP BY `Show`, `Episode`
ORDER BY `Pageviews` DESC, `Users` DESC, `Show`, `Episode`;
