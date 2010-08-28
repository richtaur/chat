SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `chat`
--

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(11) NOT NULL auto_increment,
  `room_id` int(11) NOT NULL,
  `hostname` varchar(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY  (`message_id`),
  KEY `room_id` (`room_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE IF NOT EXISTS `rooms` (
  `room_id` int(11) NOT NULL auto_increment,
  `name` varchar(30) NOT NULL,
  `code` varchar(100) NOT NULL,
  `hidden` tinyint(4) NOT NULL,
  `topic` varchar(255) NOT NULL,
  PRIMARY KEY  (`room_id`),
  KEY `name` (`name`),
  KEY `hidden` (`hidden`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rooms_users`
--

CREATE TABLE IF NOT EXISTS `rooms_users` (
  `hostname` varchar(30) NOT NULL,
  `name` varchar(30) NOT NULL,
  `room_id` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `timestamp` int(11) NOT NULL,
  KEY `name` (`name`),
  KEY `room_id` (`room_id`),
  KEY `timestamp` (`timestamp`),
  KEY `hostname` (`hostname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
