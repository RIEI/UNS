-- --------------------------------------------------------
--
-- UNS SQL INSTALL FILE
-- VER: 1.1
-- 
-- --------------------------------------------------------

--
-- create then use the UNS database.
--

CREATE DATABASE IF NOT EXISTS `uns`;
USE `uns`;
-- --------------------------------------------------------

--
-- Table structure for table `allowed_clients`
--

CREATE TABLE IF NOT EXISTS `allowed_clients` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) NOT NULL,
  `led` int(11) NOT NULL DEFAULT '1',
  `allowed_users` TEXT NOT NULL DEFAULT 'admin',
  FULLTEXT (`allowed_users`),
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_name` (`client_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `allowed_users`
--

CREATE TABLE IF NOT EXISTS `allowed_users` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `tz` VARCHAR( 8 ) NOT NULL DEFAULT 'ewt:0',
  `edit_urls` tinyint(4) NOT NULL DEFAULT '1',
  `edit_emerg` tinyint(4) NOT NULL DEFAULT '1',
  `edit_users` tinyint(4) NOT NULL DEFAULT '0',
  `edit_options` tinyint(4) NOT NULL DEFAULT '0',
  `c_messages` tinyint(4) NOT NULL DEFAULT '1',
  `img_messages` tinyint(4) NOT NULL DEFAULT '1',
  `rss_feeds` tinyint(4) NOT NULL DEFAULT '1',
  `api_key` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `archive_links`
--

CREATE TABLE IF NOT EXISTS `archive_links` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `client` varchar(255) NOT NULL,
  `urls` text COLLATE utf8_bin NOT NULL,
  `name` varchar(255) NOT NULL,
  `details` text NOT NULL,
  `date` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `connections`
--

CREATE TABLE IF NOT EXISTS `connections` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `client` varchar(255) NOT NULL,
  `last_conn` int(32) NOT NULL,
  `last_url` varchar(255) COLLATE utf8_bin NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `c_messages`
--

CREATE TABLE IF NOT EXISTS `c_messages` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `refresh` int(255) NOT NULL,
  `wrapper` tinyint(4) NOT NULL DEFAULT '1',
  UNIQUE KEY `name` (`name`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `img_messages`
--

CREATE TABLE IF NOT EXISTS `img_messages` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `refresh` int(255) NOT NULL,
  `wrapper` tinyint(4) NOT NULL DEFAULT '1',
  UNIQUE KEY `name` (`name`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `emerg`
--

CREATE TABLE IF NOT EXISTS `emerg` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `url` text COLLATE utf8_bin NOT NULL,
  `enabled` tinyint(4) NOT NULL DEFAULT '0',
  `refresh` int(255) NOT NULL DEFAULT '30',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `friendly`
--

CREATE TABLE IF NOT EXISTS `friendly` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `friendly` varchar(255) NOT NULL,
  `client` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `friendly` (`friendly`),
  UNIQUE KEY `client` (`client`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `hash_links`
--

CREATE TABLE IF NOT EXISTS `hash_links` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL,
  `time` int(9) NOT NULL,
  `user` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;
-- --------------------------------------------------------
--
-- Table structure for table `internal_users`
--

CREATE TABLE IF NOT EXISTS `internal_users` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_bin NOT NULL,
  `password` varchar(32) COLLATE utf8_bin NOT NULL,
  `disabled` tinyint(4) NOT NULL,
  `failed` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=0 ;

--
-- Table structure for table `saved_lists`
--

CREATE TABLE IF NOT EXISTS `saved_lists` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `urls` text COLLATE utf8_bin NOT NULL,
  `name` varchar(255) NOT NULL,
  `details` text NOT NULL,
  `date` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emerg` tinyint(4) NOT NULL DEFAULT '0',
  `built_in_admin` tinyint(1) NOT NULL DEFAULT '0',
  `uns_ver` varchar(32) NOT NULL,
  `svn_rev` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;


INSERT INTO `settings` (`id`, `emerg`, `built_in_admin`, `uns_ver`, `svn_rev`) VALUES
(1, 0, 0, '2.0', '81');

-- --------------------------------------------------------

--
-- Table structure for table `rss_feeds`
--

CREATE TABLE IF NOT EXISTS `rss_feeds` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `url` varchar(255) COLLATE utf8_bin NOT NULL,
  `maxlines` int(255) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=0;
