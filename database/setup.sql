DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS videos;
DROP TABLE IF EXISTS shows;

CREATE TABLE IF NOT EXISTS shows (
  id INT AUTO_INCREMENT,
  content_id VARCHAR(127),	
  slug VARCHAR(127),
  title VARCHAR(127),
  genre VARCHAR(127),
  PRIMARY KEY (id),
  UNIQUE (content_id)
);

CREATE TABLE IF NOT EXISTS videos (
  id INT AUTO_INCREMENT,
  type VARCHAR(31),
  content_id VARCHAR(127),	
  slug VARCHAR(127),
  title VARCHAR(127),
  description_short VARCHAR(255),
  description_long VARCHAR(510),
  premiered_on DATE,
  duration SMALLINT,
  content_rating VARCHAR(31),
  legacy_tp_media_id BIGINT,
  image VARCHAR(255),  
  shows_id INT,
  PRIMARY KEY (id),
  FOREIGN KEY (shows_id) REFERENCES shows(id), 
  UNIQUE (content_id)
);

CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT,
  date DATE,
  page VARCHAR(255),
  views SMALLINT,
  users SMALLINT,
  duration SMALLINT,
  referrer VARCHAR(255), 
  video_api_error VARCHAR(127),
  videos_id INT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (videos_id) REFERENCES videos(id),
  CONSTRAINT page_per_day UNIQUE (date, page)  
);
