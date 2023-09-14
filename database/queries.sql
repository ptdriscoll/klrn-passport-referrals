
#Get time of last update
##############################
SELECT UPDATE_TIME
FROM information_schema.tables
WHERE  TABLE_SCHEMA = 'passport_referrals'
AND TABLE_NAME = 'pages'  

#Get last day that was inserted
##############################
SELECT date 
FROM pages 
WHERE id = (SELECT MAX(id) FROM pages);

#Daily referrals 
##############################
SELECT
  pages.date AS `Date`,
  SUM(pages.views) AS `Pageviews`,
  SUM(pages.users) AS `Users`,
  SUM(pages.duration) AS `Duration`
FROM pages
WHERE pages.date >= '2023-07-01'
AND pages.date <= '2023-08-30'
GROUP BY `Date`
ORDER BY `Date`;

#Top shows 
##############################
SELECT
  shows.id As `ID`,
  shows.title AS `Show`,
  shows.slug AS `Slug`,
  shows.genre AS `Genre`,
  SUM(pages.views) AS `Pageviews`,
  SUM(pages.users) AS `Users`,
  SUM(pages.duration) AS `Duration`
FROM pages
INNER JOIN videos ON pages.videos_id  = videos.id
INNER JOIN shows ON videos.shows_id = shows.id
WHERE pages.date >= '2023-07-01'
AND pages.date <= '2023-08-30'
GROUP BY `ID`
ORDER BY `Pageviews` DESC, `Users` DESC, `Show`;

#Top episodes 
##############################
SELECT
  shows.id As `ShowID`,
  shows.title AS `Show`,
  shows.genre AS `Genre`,  
  videos.id AS `VideoID`,
  videos.title AS `Episode`,
  videos.slug AS `Slug`,
  SUM(pages.views) AS `Pageviews`,
  SUM(pages.users) AS `Users`,
  SUM(pages.duration) AS `Duration`
FROM pages
INNER JOIN videos ON pages.videos_id  = videos.id
INNER JOIN shows ON videos.shows_id = shows.id
WHERE pages.date >= '2023-07-01'
AND pages.date <= '2023-08-30'
GROUP BY `ShowID`, `VideoID`
ORDER BY `Pageviews` DESC, `Users` DESC, `Show`, `Episode`;

#Shows trends 
##############################
SELECT
  pages.date AS `Date`,
  shows.id AS `ID`,
  shows.title AS `Show`,
  SUM(pages.views) AS `Pageviews`,
  SUM(pages.users) AS `Users`,
  SUM(pages.duration) AS `Duration`
FROM pages
INNER JOIN videos ON pages.videos_id  = videos.id
INNER JOIN shows ON videos.shows_id = shows.id
WHERE pages.date >= '2023-07-01'
AND pages.date <= '2023-08-30'
AND shows.id IN (55, 82, 3)
GROUP BY `Date`, `ID`
ORDER BY `Date`, `ID`;

#Episodes trends 
##############################
SELECT
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
WHERE pages.date >= '2023-07-01'
AND pages.date <= '2023-08-30'
AND videos.id IN (399, 220, 542)
GROUP BY `Date`, `VideoID`  
ORDER BY `Date`, `VideoID`;
