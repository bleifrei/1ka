-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Generation Time: Mar 21, 2022 at 10:30 AM
-- Server version: 10.3.31-MariaDB-1:10.3.31+maria~focal
-- PHP Version: 7.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;



-- --------------------------------------------------------

--
-- Table structure for table `aai_prenosi`
--

CREATE TABLE `aai_prenosi` (
  `timestamp` int(10) UNSIGNED DEFAULT NULL,
  `moja` varchar(500) DEFAULT NULL,
  `njegova` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `what` varchar(100) NOT NULL DEFAULT '',
  `domain` varchar(100) NOT NULL DEFAULT '',
  `value` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_limits-admin_allow_only_ip', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_limits-clicks_per_minute_limit', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_limits-invitation_count_limit', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_limits-question_count_limit', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_limits-response_count_limit', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-admin_email', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-app_name', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-commercial_packages', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-email_signature_custom', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-email_signature_text', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-export_type', '', 'new');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-footer_custom', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-footer_survey_custom', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-footer_survey_text', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-footer_text', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-owner', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-owner_website', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('app_settings-survey_finish_url', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('cebelica_api', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('confirm_registration', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('confirm_registration_admin', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('debug', '', '0');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_fromSurvey', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_settings-SMTPAuth', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_settings-SMTPFrom', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_settings-SMTPFromNice', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_settings-SMTPHost', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_settings-SMTPPassword', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_settings-SMTPPort', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_settings-SMTPReplyTo', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_settings-SMTPSecure', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('email_server_settings-SMTPUsername', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('facebook-appid', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('facebook-appsecret', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('gdpr_admin_email', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('google-login_client_id', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('google-login_client_secret', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('google-maps_API_key', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('google-recaptcha_sitekey', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('google-secret_captcha', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('hierarhija-default_id', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('hierarhija-folder_id', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('installation_type', '', '0');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('maza-APP_special_login_key', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('maza-FCM_server_key', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('maza-NextPinMainPassword', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('maza-NextPinMainToken', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('meta_admin_ids', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('paypal-account', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('paypal-client_id', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('paypal-secret', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('squalo-key', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('squalo-user', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('stripe-key', '', '');
INSERT INTO `app_settings` (`what`, `domain`, `value`) VALUES('stripe-secret', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `browser_notifications_respondents`
--

CREATE TABLE `browser_notifications_respondents` (
  `id` int(11) NOT NULL,
  `timestamp_joined` datetime DEFAULT NULL,
  `endpoint_link` varchar(255) NOT NULL,
  `endpoint_key` varchar(255) NOT NULL,
  `public_key` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `clan`
--

CREATE TABLE `clan` (
  `menu` int(10) NOT NULL DEFAULT 0,
  `clan` int(10) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `countries_locations`
--

CREATE TABLE `countries_locations` (
  `id` int(11) NOT NULL,
  `country_code` varchar(3) NOT NULL,
  `latitude` float(11,6) NOT NULL,
  `longitude` float(11,6) NOT NULL,
  `name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `fb_users`
--

CREATE TABLE `fb_users` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT 0,
  `first_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `gender` varchar(2) DEFAULT NULL,
  `timezone` varchar(5) DEFAULT NULL,
  `profile_link` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `forum`
--

CREATE TABLE `forum` (
  `id` int(11) NOT NULL,
  `parent` int(11) NOT NULL DEFAULT 0,
  `ord` int(11) NOT NULL DEFAULT 0,
  `naslov` varchar(255) NOT NULL DEFAULT '',
  `opis` tinytext NOT NULL,
  `display` int(11) NOT NULL DEFAULT 1,
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `click` tinyint(1) NOT NULL DEFAULT 1,
  `thread` tinyint(4) NOT NULL DEFAULT 1,
  `user` int(11) NOT NULL DEFAULT 0,
  `clan` int(11) NOT NULL DEFAULT 0,
  `admin` int(11) NOT NULL DEFAULT 0,
  `ocena` int(11) NOT NULL DEFAULT 0,
  `lockedauth` int(11) NOT NULL DEFAULT 0,
  `NiceLink` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `manager`
--

CREATE TABLE `manager` (
  `menu` int(10) NOT NULL DEFAULT 0,
  `manager` int(10) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `manager_clan`
--

CREATE TABLE `manager_clan` (
  `manager` int(10) NOT NULL DEFAULT 0,
  `clan` int(10) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `maza_app_users`
--

CREATE TABLE `maza_app_users` (
  `id` int(11) NOT NULL,
  `identifier` varchar(16) NOT NULL,
  `registration_id` varchar(255) DEFAULT NULL,
  `datetime_inserted` datetime DEFAULT current_timestamp(),
  `datetime_last_active` datetime DEFAULT NULL,
  `deviceInfo` text DEFAULT NULL,
  `tracking_log` text NOT NULL,
  `nextpin_password` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_activity`
--

CREATE TABLE `maza_srv_activity` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `activity_on` tinyint(1) DEFAULT 0,
  `notif_title` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `notif_message` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `notif_sound` tinyint(1) DEFAULT 0,
  `activity_type` varchar(30) DEFAULT 'path',
  `after_seconds` int(11) DEFAULT 300
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_alarms`
--

CREATE TABLE `maza_srv_alarms` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `alarm_on` tinyint(1) DEFAULT 0,
  `alarm_notif_title` varchar(100) DEFAULT '',
  `alarm_notif_message` varchar(100) DEFAULT '',
  `repeat_by` varchar(30) DEFAULT 'everyday',
  `alarm_notif_sound` tinyint(1) DEFAULT 0,
  `time_in_day` varchar(255) DEFAULT NULL,
  `day_in_week` varchar(255) DEFAULT NULL,
  `every_which_day` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_entry`
--

CREATE TABLE `maza_srv_entry` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `entry_on` tinyint(1) DEFAULT 0,
  `location_check` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_geofences`
--

CREATE TABLE `maza_srv_geofences` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `geofence_on` tinyint(1) DEFAULT 0,
  `lat` float(19,15) NOT NULL,
  `lng` float(19,15) NOT NULL,
  `radius` float(21,13) NOT NULL,
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `name` varchar(100) DEFAULT NULL,
  `notif_title` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `notif_message` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `notif_sound` tinyint(1) DEFAULT 0,
  `on_transition` varchar(30) DEFAULT 'dwell',
  `after_seconds` int(11) DEFAULT 300,
  `location_triggered` tinyint(1) DEFAULT 0,
  `trigger_survey` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_repeaters`
--

CREATE TABLE `maza_srv_repeaters` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `repeater_on` tinyint(1) DEFAULT 0,
  `repeat_by` varchar(30) DEFAULT 'everyday',
  `time_in_day` varchar(255) DEFAULT NULL,
  `day_in_week` varchar(255) DEFAULT NULL,
  `every_which_day` int(3) DEFAULT NULL,
  `datetime_start` datetime DEFAULT NULL,
  `datetime_end` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_tracking`
--

CREATE TABLE `maza_srv_tracking` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `tracking_on` tinyint(1) DEFAULT 0,
  `activity_recognition` tinyint(1) DEFAULT 0,
  `tracking_accuracy` varchar(30) DEFAULT 'high',
  `interval_wanted` int(11) DEFAULT 30,
  `interval_fastes` int(11) DEFAULT 10,
  `displacement_min` int(11) DEFAULT 10,
  `ar_interval_wanted` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_triggered_activities`
--

CREATE TABLE `maza_srv_triggered_activities` (
  `id` int(11) NOT NULL,
  `act_id` int(11) NOT NULL,
  `maza_user_id` int(11) NOT NULL,
  `triggered_timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_triggered_geofences`
--

CREATE TABLE `maza_srv_triggered_geofences` (
  `id` int(11) NOT NULL,
  `geof_id` int(11) NOT NULL,
  `maza_user_id` int(11) NOT NULL,
  `triggered_timestamp` datetime NOT NULL,
  `enter_timestamp` datetime DEFAULT NULL,
  `dwell_timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_users`
--

CREATE TABLE `maza_srv_users` (
  `maza_user_id` int(11) NOT NULL,
  `srv_user_id` int(11) NOT NULL,
  `srv_version_datetime` datetime DEFAULT NULL,
  `loc_id` int(11) DEFAULT NULL,
  `tact_id` int(11) DEFAULT NULL,
  `tgeof_id` int(11) DEFAULT NULL,
  `mode` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_survey`
--

CREATE TABLE `maza_survey` (
  `srv_id` int(11) NOT NULL,
  `srv_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_user_activity_recognition`
--

CREATE TABLE `maza_user_activity_recognition` (
  `id` int(11) NOT NULL,
  `maza_user_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `in_vehicle` int(3) DEFAULT 0,
  `on_bicycle` int(3) DEFAULT 0,
  `on_foot` int(3) DEFAULT 0,
  `still` int(3) DEFAULT 0,
  `unknown` int(3) DEFAULT 0,
  `tilting` int(3) DEFAULT 0,
  `running` int(3) DEFAULT 0,
  `walking` int(3) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_user_locations`
--

CREATE TABLE `maza_user_locations` (
  `id` int(11) NOT NULL,
  `maza_user_id` int(11) NOT NULL,
  `lat` float(19,15) NOT NULL,
  `lng` float(19,15) NOT NULL,
  `provider` varchar(30) DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `accuracy` float(10,4) DEFAULT NULL,
  `altitude` float(10,4) DEFAULT NULL,
  `bearing` float(7,4) DEFAULT NULL,
  `speed` float(10,4) DEFAULT NULL,
  `vertical_acc` float(10,4) DEFAULT NULL,
  `bearing_acc` float(7,4) DEFAULT NULL,
  `speed_acc` float(10,4) DEFAULT NULL,
  `extras` varchar(255) DEFAULT NULL,
  `is_mock` tinyint(1) DEFAULT 0,
  `tgeof_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_user_srv_access`
--

CREATE TABLE `maza_user_srv_access` (
  `maza_user_id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `datetime_started` datetime DEFAULT NULL,
  `nextpin_tracking_permitted` tinyint(1) DEFAULT 0,
  `tracking_permitted` tinyint(1) DEFAULT NULL,
  `datetime_unsubscribed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `misc`
--

CREATE TABLE `misc` (
  `what` varchar(255) DEFAULT NULL,
  `value` mediumtext DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `misc`
--

INSERT INTO `misc` (`what`, `value`) VALUES('SurveyDostop', '3');
INSERT INTO `misc` (`what`, `value`) VALUES('CookieLife', '43200');
INSERT INTO `misc` (`what`, `value`) VALUES('SurveyCookie', '-1');
INSERT INTO `misc` (`what`, `value`) VALUES('SurveyForum', '0');
INSERT INTO `misc` (`what`, `value`) VALUES('SurveyLang_admin', '1');
INSERT INTO `misc` (`what`, `value`) VALUES('SurveyLang_resp', '1');
INSERT INTO `misc` (`what`, `value`) VALUES('drupal version', '7.81');
INSERT INTO `misc` (`what`, `value`) VALUES('mobileApp_version', '16.5.30');
INSERT INTO `misc` (`what`, `value`) VALUES('drupal version', '7.81');
INSERT INTO `misc` (`what`, `value`) VALUES('version', '21.11.16');

-- --------------------------------------------------------

--
-- Table structure for table `oid_users`
--

CREATE TABLE `oid_users` (
  `uid` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE `post` (
  `id` int(11) NOT NULL,
  `fid` int(11) NOT NULL DEFAULT 0,
  `tid` int(11) NOT NULL DEFAULT 0,
  `parent` int(11) NOT NULL DEFAULT 0,
  `naslov` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `naslovnica` tinyint(4) NOT NULL DEFAULT 1,
  `vsebina` text CHARACTER SET utf8 NOT NULL,
  `ogledov` int(11) NOT NULL DEFAULT 0,
  `uid` int(11) NOT NULL DEFAULT 0,
  `user` varchar(40) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `time2` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `admin` int(11) NOT NULL DEFAULT 3,
  `dispauth` int(11) NOT NULL DEFAULT 0,
  `dispthread` int(11) NOT NULL DEFAULT 0,
  `ocena` int(11) NOT NULL DEFAULT 0,
  `IP` varchar(128) NOT NULL DEFAULT 'Neznan',
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `NiceLink` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `registers`
--

CREATE TABLE `registers` (
  `ip` varchar(64) NOT NULL DEFAULT '',
  `lasttime` varchar(20) DEFAULT NULL,
  `handle` varchar(255) DEFAULT NULL,
  `code` varchar(80) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `restrict_fk_srv_anketa`
--

CREATE TABLE `restrict_fk_srv_anketa` (
  `ank_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `restrict_fk_srv_anketa`
--

INSERT INTO `restrict_fk_srv_anketa` (`ank_id`) VALUES(-1);
INSERT INTO `restrict_fk_srv_anketa` (`ank_id`) VALUES(0);

-- --------------------------------------------------------

--
-- Table structure for table `restrict_fk_srv_grupa`
--

CREATE TABLE `restrict_fk_srv_grupa` (
  `gru_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `restrict_fk_srv_grupa`
--

INSERT INTO `restrict_fk_srv_grupa` (`gru_id`) VALUES(-2);
INSERT INTO `restrict_fk_srv_grupa` (`gru_id`) VALUES(-1);
INSERT INTO `restrict_fk_srv_grupa` (`gru_id`) VALUES(0);

-- --------------------------------------------------------

--
-- Table structure for table `restrict_fk_srv_if`
--

CREATE TABLE `restrict_fk_srv_if` (
  `if_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `restrict_fk_srv_if`
--

INSERT INTO `restrict_fk_srv_if` (`if_id`) VALUES(0);

-- --------------------------------------------------------

--
-- Table structure for table `restrict_fk_srv_spremenljivka`
--

CREATE TABLE `restrict_fk_srv_spremenljivka` (
  `spr_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `restrict_fk_srv_spremenljivka`
--

INSERT INTO `restrict_fk_srv_spremenljivka` (`spr_id`) VALUES(-4);
INSERT INTO `restrict_fk_srv_spremenljivka` (`spr_id`) VALUES(-3);
INSERT INTO `restrict_fk_srv_spremenljivka` (`spr_id`) VALUES(-2);
INSERT INTO `restrict_fk_srv_spremenljivka` (`spr_id`) VALUES(-1);
INSERT INTO `restrict_fk_srv_spremenljivka` (`spr_id`) VALUES(0);

-- --------------------------------------------------------

--
-- Table structure for table `restrict_fk_srv_vrednost`
--

CREATE TABLE `restrict_fk_srv_vrednost` (
  `vre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `restrict_fk_srv_vrednost`
--

INSERT INTO `restrict_fk_srv_vrednost` (`vre_id`) VALUES(-4);
INSERT INTO `restrict_fk_srv_vrednost` (`vre_id`) VALUES(-3);
INSERT INTO `restrict_fk_srv_vrednost` (`vre_id`) VALUES(-2);
INSERT INTO `restrict_fk_srv_vrednost` (`vre_id`) VALUES(-1);
INSERT INTO `restrict_fk_srv_vrednost` (`vre_id`) VALUES(0);

-- --------------------------------------------------------

--
-- Table structure for table `srv_activity`
--

CREATE TABLE `srv_activity` (
  `sid` int(11) NOT NULL,
  `starts` date NOT NULL,
  `expire` date NOT NULL,
  `uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_advanced_paradata_alert`
--

CREATE TABLE `srv_advanced_paradata_alert` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `time_display` datetime(3) NOT NULL,
  `time_close` datetime(3) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT '',
  `trigger_id` int(11) NOT NULL,
  `trigger_type` varchar(50) NOT NULL DEFAULT '',
  `ignorable` enum('0','1') NOT NULL DEFAULT '0',
  `text` varchar(200) NOT NULL DEFAULT '',
  `action` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_advanced_paradata_movement`
--

CREATE TABLE `srv_advanced_paradata_movement` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `time_start` datetime(3) NOT NULL,
  `time_end` datetime(3) NOT NULL,
  `pos_x_start` int(11) NOT NULL DEFAULT 0,
  `pos_y_start` int(11) NOT NULL DEFAULT 0,
  `pos_x_end` int(11) NOT NULL DEFAULT 0,
  `pos_y_end` int(11) NOT NULL DEFAULT 0,
  `distance` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_advanced_paradata_other`
--

CREATE TABLE `srv_advanced_paradata_other` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `time` datetime(3) NOT NULL,
  `event` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(50) NOT NULL DEFAULT '',
  `pos_x` int(11) NOT NULL DEFAULT 0,
  `pos_y` int(11) NOT NULL DEFAULT 0,
  `div_id` varchar(100) NOT NULL DEFAULT '',
  `div_class` varchar(100) NOT NULL DEFAULT '',
  `div_type` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_advanced_paradata_page`
--

CREATE TABLE `srv_advanced_paradata_page` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `gru_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `recnum` int(11) NOT NULL,
  `load_time` datetime(3) NOT NULL,
  `post_time` datetime(3) NOT NULL,
  `user_agent` varchar(250) NOT NULL DEFAULT '',
  `language` varchar(50) NOT NULL DEFAULT '',
  `devicePixelRatio` decimal(4,2) NOT NULL DEFAULT 0.00,
  `width` int(11) NOT NULL DEFAULT 0,
  `height` int(11) NOT NULL DEFAULT 0,
  `availWidth` int(11) NOT NULL DEFAULT 0,
  `availHeight` int(11) NOT NULL DEFAULT 0,
  `jquery_windowW` int(11) NOT NULL DEFAULT 0,
  `jquery_windowH` int(11) NOT NULL DEFAULT 0,
  `jquery_documentW` int(11) NOT NULL DEFAULT 0,
  `jquery_documentH` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_advanced_paradata_question`
--

CREATE TABLE `srv_advanced_paradata_question` (
  `page_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `vre_order` varchar(250) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_advanced_paradata_settings`
--

CREATE TABLE `srv_advanced_paradata_settings` (
  `ank_id` int(11) NOT NULL,
  `collect_post_time` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_advanced_paradata_vrednost`
--

CREATE TABLE `srv_advanced_paradata_vrednost` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `time` datetime(3) NOT NULL,
  `event` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(50) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_alert`
--

CREATE TABLE `srv_alert` (
  `ank_id` int(11) NOT NULL,
  `finish_respondent` tinyint(1) NOT NULL DEFAULT 0,
  `finish_respondent_cms` tinyint(1) NOT NULL DEFAULT 0,
  `finish_author` tinyint(1) NOT NULL DEFAULT 0,
  `finish_other` tinyint(1) NOT NULL DEFAULT 0,
  `finish_other_emails` mediumtext NOT NULL,
  `finish_subject` varchar(250) CHARACTER SET utf8 NOT NULL,
  `finish_text` text CHARACTER SET utf8 NOT NULL,
  `expire_days` int(11) NOT NULL DEFAULT 3,
  `expire_author` tinyint(1) NOT NULL DEFAULT 0,
  `expire_other` tinyint(1) NOT NULL DEFAULT 0,
  `expire_other_emails` mediumtext NOT NULL,
  `expire_text` text CHARACTER SET utf8 NOT NULL,
  `expire_subject` varchar(250) CHARACTER SET utf8 NOT NULL,
  `delete_author` tinyint(1) NOT NULL DEFAULT 0,
  `delete_other` tinyint(1) NOT NULL DEFAULT 0,
  `delete_other_emails` mediumtext NOT NULL,
  `delete_text` text CHARACTER SET utf8 NOT NULL,
  `delete_subject` varchar(250) CHARACTER SET utf8 NOT NULL,
  `active_author` tinyint(1) NOT NULL DEFAULT 0,
  `active_other` tinyint(1) NOT NULL DEFAULT 0,
  `active_other_emails` mediumtext NOT NULL,
  `active_text0` text CHARACTER SET utf8 NOT NULL,
  `active_subject0` varchar(250) CHARACTER SET utf8 NOT NULL,
  `active_text1` text CHARACTER SET utf8 NOT NULL,
  `active_subject1` varchar(250) CHARACTER SET utf8 NOT NULL,
  `finish_respondent_if` int(11) DEFAULT NULL,
  `finish_respondent_cms_if` int(11) DEFAULT NULL,
  `finish_other_if` int(11) DEFAULT NULL,
  `reply_to` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_alert`
--

INSERT INTO `srv_alert` (`ank_id`, `finish_respondent`, `finish_respondent_cms`, `finish_author`, `finish_other`, `finish_other_emails`, `finish_subject`, `finish_text`, `expire_days`, `expire_author`, `expire_other`, `expire_other_emails`, `expire_text`, `expire_subject`, `delete_author`, `delete_other`, `delete_other_emails`, `delete_text`, `delete_subject`, `active_author`, `active_other`, `active_other_emails`, `active_text0`, `active_subject0`, `active_text1`, `active_subject1`, `finish_respondent_if`, `finish_respondent_cms_if`, `finish_other_if`, `reply_to`) VALUES(0, 0, 0, 0, 0, '', '', '', 3, 1, 0, '', 'SpoÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡tovani,<br/><br/>obveÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ÃƒÆ’Ã¢â‚¬Å¾Ãƒâ€šÃ‚Âamo vas, da bo anketa &#34;[SURVEY]&#34; potekla &#269;ez [DAYS] dni.<br/><br/>Povezava: [URL]<br/><br/>ÃƒÆ’Ã¢â‚¬Å¾Ãƒâ€¦Ã¢â‚¬â„¢as aktivnosti lahko podaljÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â¡ate v nastavitvah [DURATION]<br />\n<br />\n<br />\n1KA<br />\n--------<br />\nOrodje za spletne ankete: <a href=\"http://www.1ka.si\">http://www.1ka.si</a>', '1KA - obvestilo o izteku ankete', 0, 0, '', '', '', 0, 0, '', '', '', '', '', NULL, NULL, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `srv_alert_custom`
--

CREATE TABLE `srv_alert_custom` (
  `ank_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `uid` int(11) NOT NULL,
  `subject` varchar(250) NOT NULL,
  `text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_analysis_archive`
--

CREATE TABLE `srv_analysis_archive` (
  `id` int(11) NOT NULL,
  `sid` int(11) NOT NULL DEFAULT 0,
  `uid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `filename` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  `note` varchar(200) NOT NULL,
  `access` tinyint(4) NOT NULL DEFAULT 0,
  `access_password` varchar(30) DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT 0,
  `duration` date NOT NULL,
  `editid` int(11) NOT NULL DEFAULT 0,
  `settings` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_anketa`
--

CREATE TABLE `srv_anketa` (
  `id` int(11) NOT NULL,
  `hash` varchar(8) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `folder` int(11) NOT NULL DEFAULT 1,
  `backup` int(11) NOT NULL,
  `naslov` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `akronim` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT 0,
  `locked` tinyint(4) NOT NULL DEFAULT 0,
  `db_table` tinyint(4) NOT NULL DEFAULT 0,
  `starts` date NOT NULL,
  `expire` date NOT NULL,
  `introduction` text CHARACTER SET utf8 DEFAULT NULL,
  `conclusion` text CHARACTER SET utf8 DEFAULT NULL,
  `statistics` text CHARACTER SET utf8 DEFAULT NULL,
  `intro_opomba` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `concl_opomba` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `show_intro` tinyint(4) NOT NULL DEFAULT 1,
  `intro_static` tinyint(4) NOT NULL DEFAULT 0,
  `show_concl` tinyint(4) NOT NULL DEFAULT 1,
  `concl_link` int(11) NOT NULL DEFAULT 0,
  `concl_back_button` tinyint(4) NOT NULL DEFAULT 1,
  `concl_end_button` tinyint(4) NOT NULL DEFAULT 1,
  `text` varchar(250) NOT NULL,
  `url` varchar(250) NOT NULL,
  `insert_uid` int(11) NOT NULL,
  `insert_time` datetime NOT NULL,
  `edit_uid` int(11) NOT NULL,
  `edit_time` datetime NOT NULL,
  `last_response_time` datetime NOT NULL,
  `cookie` tinyint(4) NOT NULL DEFAULT 2,
  `cookie_return` tinyint(4) NOT NULL DEFAULT 0,
  `return_finished` tinyint(4) NOT NULL DEFAULT 0,
  `subsequent_answers` enum('0','1') NOT NULL DEFAULT '1',
  `cookie_continue` tinyint(4) NOT NULL DEFAULT 1,
  `user_from_cms` int(11) NOT NULL DEFAULT 0,
  `user_from_cms_email` int(11) NOT NULL DEFAULT 0,
  `user_base` tinyint(4) NOT NULL DEFAULT 0,
  `usercode_skip` tinyint(4) NOT NULL DEFAULT 0,
  `usercode_required` tinyint(4) NOT NULL DEFAULT 0,
  `usercode_text` varchar(255) NOT NULL,
  `block_ip` int(11) NOT NULL DEFAULT 0,
  `dostop` int(11) NOT NULL DEFAULT 3,
  `dostop_admin` date NOT NULL,
  `odgovarja` tinyint(4) NOT NULL DEFAULT 4,
  `skin` varchar(100) NOT NULL DEFAULT '1kaBlue',
  `mobile_skin` varchar(100) NOT NULL DEFAULT 'MobileBlue',
  `skin_profile` int(11) NOT NULL,
  `skin_profile_mobile` int(11) NOT NULL,
  `skin_checkbox` tinyint(4) NOT NULL DEFAULT 0,
  `branching` smallint(6) NOT NULL DEFAULT 0,
  `alert_respondent` tinyint(4) NOT NULL DEFAULT 0,
  `alert_avtor` tinyint(4) NOT NULL DEFAULT 0,
  `alert_admin` tinyint(4) NOT NULL DEFAULT 0,
  `alert_more` tinyint(1) NOT NULL DEFAULT 0,
  `uporabnost_link` varchar(400) NOT NULL,
  `progressbar` tinyint(4) NOT NULL DEFAULT 0,
  `sidebar` tinyint(4) NOT NULL DEFAULT 1,
  `collapsed_content` tinyint(4) NOT NULL DEFAULT 1,
  `library` tinyint(4) NOT NULL DEFAULT 0,
  `countType` tinyint(1) NOT NULL DEFAULT 0,
  `survey_type` tinyint(4) NOT NULL DEFAULT 2,
  `forum` int(11) NOT NULL DEFAULT 0,
  `thread` int(11) NOT NULL,
  `thread_intro` int(11) NOT NULL DEFAULT 0,
  `thread_concl` int(11) NOT NULL DEFAULT 0,
  `intro_note` text NOT NULL,
  `concl_note` text NOT NULL,
  `vote_limit` tinyint(4) NOT NULL DEFAULT 0,
  `vote_count` int(11) NOT NULL DEFAULT 0,
  `lang_admin` tinyint(4) NOT NULL DEFAULT 1,
  `lang_resp` tinyint(4) NOT NULL DEFAULT 1,
  `multilang` tinyint(4) NOT NULL DEFAULT 0,
  `expanded` tinyint(4) NOT NULL DEFAULT 1,
  `flat` tinyint(4) NOT NULL DEFAULT 0,
  `toolbox` tinyint(4) NOT NULL DEFAULT 1,
  `popup` tinyint(4) NOT NULL DEFAULT 1,
  `missing_values_type` tinyint(4) NOT NULL DEFAULT 0,
  `mass_insert` enum('0','1') NOT NULL,
  `monitoring` enum('0','1') NOT NULL,
  `show_email` enum('0','1') NOT NULL DEFAULT '1',
  `vprasanje_tracking` enum('0','1','2','3') CHARACTER SET utf8 NOT NULL DEFAULT '0',
  `parapodatki` enum('0','1') NOT NULL,
  `individual_invitation` enum('0','1') NOT NULL DEFAULT '1',
  `email_to_list` enum('0','1') NOT NULL DEFAULT '0',
  `invisible` enum('0','1') NOT NULL DEFAULT '0',
  `continue_later` enum('1','0') NOT NULL DEFAULT '0',
  `js_tracking` text NOT NULL,
  `concl_PDF_link` tinyint(4) NOT NULL DEFAULT 0,
  `concl_return_edit` enum('0','1') NOT NULL DEFAULT '0',
  `defValidProfile` tinyint(4) NOT NULL DEFAULT 2,
  `showItime` tinyint(4) NOT NULL DEFAULT 0,
  `showLineNumber` tinyint(4) NOT NULL DEFAULT 0,
  `mobile_created` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_anketa`
--

INSERT INTO `srv_anketa` (`id`, `hash`, `folder`, `backup`, `naslov`, `akronim`, `active`, `locked`, `db_table`, `starts`, `expire`, `introduction`, `conclusion`, `statistics`, `intro_opomba`, `concl_opomba`, `show_intro`, `intro_static`, `show_concl`, `concl_link`, `concl_back_button`, `concl_end_button`, `text`, `url`, `insert_uid`, `insert_time`, `edit_uid`, `edit_time`, `last_response_time`, `cookie`, `cookie_return`, `return_finished`, `subsequent_answers`, `cookie_continue`, `user_from_cms`, `user_from_cms_email`, `user_base`, `usercode_skip`, `usercode_required`, `usercode_text`, `block_ip`, `dostop`, `dostop_admin`, `odgovarja`, `skin`, `mobile_skin`, `skin_profile`, `skin_profile_mobile`, `skin_checkbox`, `branching`, `alert_respondent`, `alert_avtor`, `alert_admin`, `alert_more`, `uporabnost_link`, `progressbar`, `sidebar`, `collapsed_content`, `library`, `countType`, `survey_type`, `forum`, `thread`, `thread_intro`, `thread_concl`, `intro_note`, `concl_note`, `vote_limit`, `vote_count`, `lang_admin`, `lang_resp`, `multilang`, `expanded`, `flat`, `toolbox`, `popup`, `missing_values_type`, `mass_insert`, `monitoring`, `show_email`, `vprasanje_tracking`, `parapodatki`, `individual_invitation`, `email_to_list`, `invisible`, `continue_later`, `js_tracking`, `concl_PDF_link`, `concl_return_edit`, `defValidProfile`, `showItime`, `showLineNumber`, `mobile_created`) VALUES(-1, '-1', 1, 0, 'system', '', 0, 0, 0, '0000-00-00', '0000-00-00', '', '', '', '', '', 1, 0, 1, 0, 1, 1, '', '', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2, 0, 1, '1', 1, 0, 0, 0, 1, 0, '', 0, 3, '0000-00-00', 4, 'Modern', 'Mobile', 0, 0, 0, 1, 0, 0, 0, 0, '', 1, 1, 1, 0, 0, 2, 0, 0, 0, 0, '', '', 0, 0, 1, 1, 0, 1, 1, 1, 1, 0, '0', '0', '0', '0', '0', '1', '0', '0', '1', '', 0, '0', 2, 0, 0, '0');
INSERT INTO `srv_anketa` (`id`, `hash`, `folder`, `backup`, `naslov`, `akronim`, `active`, `locked`, `db_table`, `starts`, `expire`, `introduction`, `conclusion`, `statistics`, `intro_opomba`, `concl_opomba`, `show_intro`, `intro_static`, `show_concl`, `concl_link`, `concl_back_button`, `concl_end_button`, `text`, `url`, `insert_uid`, `insert_time`, `edit_uid`, `edit_time`, `last_response_time`, `cookie`, `cookie_return`, `return_finished`, `subsequent_answers`, `cookie_continue`, `user_from_cms`, `user_from_cms_email`, `user_base`, `usercode_skip`, `usercode_required`, `usercode_text`, `block_ip`, `dostop`, `dostop_admin`, `odgovarja`, `skin`, `mobile_skin`, `skin_profile`, `skin_profile_mobile`, `skin_checkbox`, `branching`, `alert_respondent`, `alert_avtor`, `alert_admin`, `alert_more`, `uporabnost_link`, `progressbar`, `sidebar`, `collapsed_content`, `library`, `countType`, `survey_type`, `forum`, `thread`, `thread_intro`, `thread_concl`, `intro_note`, `concl_note`, `vote_limit`, `vote_count`, `lang_admin`, `lang_resp`, `multilang`, `expanded`, `flat`, `toolbox`, `popup`, `missing_values_type`, `mass_insert`, `monitoring`, `show_email`, `vprasanje_tracking`, `parapodatki`, `individual_invitation`, `email_to_list`, `invisible`, `continue_later`, `js_tracking`, `concl_PDF_link`, `concl_return_edit`, `defValidProfile`, `showItime`, `showLineNumber`, `mobile_created`) VALUES(0, '0', 1, 0, 'system', '', 0, 0, 0, '0000-00-00', '0000-00-00', '', '', '', '', '', 1, 0, 1, 0, 1, 1, '', '', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 2, 0, 1, '1', 1, 0, 0, 0, 1, 0, '', 0, 3, '0000-00-00', 4, 'Modern', 'Mobile', 0, 0, 0, 0, 0, 0, 0, 0, '', 1, 1, 1, 0, 0, 2, 0, 0, 0, 0, '', '', 0, 0, 1, 1, 0, 1, 1, 1, 1, 0, '0', '0', '0', '0', '0', '1', '0', '0', '1', '', 0, '0', 2, 0, 0, '0');

-- --------------------------------------------------------

--
-- Table structure for table `srv_anketa_module`
--

CREATE TABLE `srv_anketa_module` (
  `ank_id` int(11) NOT NULL,
  `modul` varchar(100) NOT NULL DEFAULT '',
  `vrednost` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_anketa_template`
--

CREATE TABLE `srv_anketa_template` (
  `id` int(11) NOT NULL,
  `kategorija` tinyint(1) DEFAULT 0,
  `ank_id_slo` int(11) NOT NULL,
  `naslov_slo` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `desc_slo` text NOT NULL,
  `ank_id_eng` int(11) NOT NULL,
  `naslov_eng` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `desc_eng` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_api_auth`
--

CREATE TABLE `srv_api_auth` (
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `identifier` text NOT NULL,
  `private_key` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_branching`
--

CREATE TABLE `srv_branching` (
  `ank_id` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `element_spr` int(11) NOT NULL,
  `element_if` int(11) NOT NULL,
  `vrstni_red` int(11) NOT NULL,
  `pagebreak` tinyint(4) NOT NULL DEFAULT 0,
  `timestamp` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_calculation`
--

CREATE TABLE `srv_calculation` (
  `id` int(11) NOT NULL,
  `cnd_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `operator` smallint(6) NOT NULL,
  `number` int(11) NOT NULL DEFAULT 0,
  `left_bracket` smallint(6) NOT NULL,
  `right_bracket` smallint(6) NOT NULL,
  `vrstni_red` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_call_current`
--

CREATE TABLE `srv_call_current` (
  `usr_id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `started_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_call_history`
--

CREATE TABLE `srv_call_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `survey_id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL,
  `insert_time` datetime NOT NULL,
  `status` enum('A','Z','N','R','T','P','U') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_call_schedule`
--

CREATE TABLE `srv_call_schedule` (
  `usr_id` int(11) NOT NULL,
  `call_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_call_setting`
--

CREATE TABLE `srv_call_setting` (
  `survey_id` int(11) NOT NULL,
  `status_z` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status_n` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `max_calls` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_captcha`
--

CREATE TABLE `srv_captcha` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `text` char(5) NOT NULL,
  `code` char(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_chart_skin`
--

CREATE TABLE `srv_chart_skin` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `colors` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_chat_settings`
--

CREATE TABLE `srv_chat_settings` (
  `ank_id` int(11) NOT NULL,
  `code` text NOT NULL,
  `chat_type` enum('0','1','2') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_clicks`
--

CREATE TABLE `srv_clicks` (
  `ank_id` int(11) NOT NULL,
  `click_count` smallint(6) NOT NULL DEFAULT 0,
  `click_time` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_comment_resp`
--

CREATE TABLE `srv_comment_resp` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `comment_time` datetime NOT NULL,
  `comment` text NOT NULL,
  `ocena` enum('0','1','2','3') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_condition`
--

CREATE TABLE `srv_condition` (
  `id` int(11) NOT NULL,
  `if_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `conjunction` smallint(6) NOT NULL DEFAULT 0,
  `negation` smallint(6) NOT NULL DEFAULT 0,
  `operator` smallint(6) NOT NULL DEFAULT 0,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `modul` smallint(6) NOT NULL DEFAULT 2,
  `ostanek` smallint(6) NOT NULL DEFAULT 0,
  `left_bracket` smallint(6) NOT NULL DEFAULT 0,
  `right_bracket` smallint(6) NOT NULL DEFAULT 0,
  `vrstni_red` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_condition_grid`
--

CREATE TABLE `srv_condition_grid` (
  `cond_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_condition_profiles`
--

CREATE TABLE `srv_condition_profiles` (
  `id` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `if_id` int(11) NOT NULL,
  `condition_label` text NOT NULL,
  `condition_error` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('default','inspect','zoom') NOT NULL DEFAULT 'default'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_condition_vre`
--

CREATE TABLE `srv_condition_vre` (
  `cond_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_custom_report`
--

CREATE TABLE `srv_custom_report` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `spr1` varchar(255) NOT NULL DEFAULT '',
  `spr2` varchar(255) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `sub_type` tinyint(1) NOT NULL DEFAULT 0,
  `vrstni_red` tinyint(1) NOT NULL DEFAULT 0,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `profile` int(11) NOT NULL DEFAULT 0,
  `time_edit` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_custom_report_profiles`
--

CREATE TABLE `srv_custom_report_profiles` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `time_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_custom_report_share`
--

CREATE TABLE `srv_custom_report_share` (
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `profile_id` int(11) NOT NULL DEFAULT 0,
  `author_usr_id` int(11) NOT NULL DEFAULT 0,
  `share_usr_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_datasetting_profile`
--

CREATE TABLE `srv_datasetting_profile` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `dsp_ndp` tinyint(4) NOT NULL DEFAULT 1,
  `dsp_nda` tinyint(4) NOT NULL DEFAULT 2,
  `dsp_ndd` tinyint(4) NOT NULL DEFAULT 2,
  `dsp_res` tinyint(4) NOT NULL DEFAULT 3,
  `dsp_sep` tinyint(4) NOT NULL DEFAULT 0,
  `crossChk0` enum('0','1') NOT NULL DEFAULT '1',
  `crossChk1` enum('0','1') NOT NULL DEFAULT '0',
  `crossChk2` enum('0','1') NOT NULL DEFAULT '0',
  `crossChk3` enum('0','1') NOT NULL DEFAULT '0',
  `crossChkEC` enum('0','1') NOT NULL DEFAULT '0',
  `crossChkRE` enum('0','1') NOT NULL DEFAULT '0',
  `crossChkSR` enum('0','1') NOT NULL DEFAULT '0',
  `crossChkAR` enum('0','1') NOT NULL DEFAULT '0',
  `doColor` enum('0','1') NOT NULL DEFAULT '0',
  `dovalues` enum('0','1') NOT NULL DEFAULT '1',
  `showCategories` enum('0','1') NOT NULL DEFAULT '1',
  `showOther` enum('0','1') NOT NULL DEFAULT '1',
  `showNumbers` enum('0','1') NOT NULL DEFAULT '1',
  `showText` enum('0','1') NOT NULL DEFAULT '1',
  `chartNumbering` enum('0','1') NOT NULL DEFAULT '0',
  `chartFP` enum('0','1') NOT NULL DEFAULT '0',
  `chartTableAlign` enum('0','1') NOT NULL DEFAULT '0',
  `chartTableMore` enum('0','1') NOT NULL DEFAULT '0',
  `chartNumerusText` enum('0','1','2','3') NOT NULL DEFAULT '0',
  `chartAvgText` enum('0','1') NOT NULL DEFAULT '1',
  `chartFontSize` tinyint(4) NOT NULL DEFAULT 8,
  `chartPieZeros` enum('0','1') NOT NULL DEFAULT '1',
  `hideEmpty` enum('0','1') NOT NULL DEFAULT '0',
  `numOpenAnswers` int(11) NOT NULL DEFAULT 10,
  `dataPdfType` enum('0','1','2') NOT NULL DEFAULT '0',
  `exportDataNumbering` enum('0','1') NOT NULL DEFAULT '1',
  `exportDataShowIf` enum('0','1') NOT NULL DEFAULT '1',
  `exportDataFontSize` tinyint(4) NOT NULL DEFAULT 10,
  `exportDataShowRecnum` enum('0','1') NOT NULL DEFAULT '1',
  `exportDataPB` enum('0','1') NOT NULL DEFAULT '0',
  `exportDataSkipEmpty` enum('0','1') NOT NULL DEFAULT '0',
  `exportDataSkipEmptySub` enum('0','1') NOT NULL DEFAULT '0',
  `exportDataLandscape` enum('0','1') NOT NULL DEFAULT '0',
  `exportNumbering` enum('0','1') NOT NULL DEFAULT '1',
  `exportShowIf` enum('0','1') NOT NULL DEFAULT '1',
  `exportFontSize` tinyint(4) NOT NULL DEFAULT 10,
  `exportShowIntro` enum('0','1') NOT NULL DEFAULT '0',
  `enableInspect` enum('0','1') NOT NULL DEFAULT '0',
  `dataShowIcons` enum('0','1') NOT NULL DEFAULT '1',
  `analysisGoTo` enum('0','1') NOT NULL DEFAULT '1',
  `analiza_legenda` enum('0','1') NOT NULL DEFAULT '0',
  `hideAllSystem` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_checkgrid_active`
--

CREATE TABLE `srv_data_checkgrid_active` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_checkgrid_archive1`
--

CREATE TABLE `srv_data_checkgrid_archive1` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_checkgrid_archive2`
--

CREATE TABLE `srv_data_checkgrid_archive2` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_files`
--

CREATE TABLE `srv_data_files` (
  `sid` int(11) NOT NULL,
  `head_file_time` datetime NOT NULL,
  `data_file_time` datetime NOT NULL,
  `collect_all_status` tinyint(4) NOT NULL DEFAULT 1,
  `collect_full_meta` tinyint(4) NOT NULL DEFAULT 1,
  `last_update` datetime NOT NULL,
  `dashboard_file_time` int(11) UNSIGNED NOT NULL,
  `dashboard_update_time` datetime NOT NULL,
  `updateInProgress` enum('0','1') NOT NULL DEFAULT '0',
  `updateStartTime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_glasovanje`
--

CREATE TABLE `srv_data_glasovanje` (
  `spr_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `spol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_grid_active`
--

CREATE TABLE `srv_data_grid_active` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_grid_archive1`
--

CREATE TABLE `srv_data_grid_archive1` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_grid_archive2`
--

CREATE TABLE `srv_data_grid_archive2` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_heatmap`
--

CREATE TABLE `srv_data_heatmap` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL,
  `vre_id` int(11) DEFAULT NULL,
  `lat` float(19,15) NOT NULL,
  `lng` float(19,15) NOT NULL,
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `text` text CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `vrstni_red` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_imena`
--

CREATE TABLE `srv_data_imena` (
  `id` int(10) UNSIGNED NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `antonucci` int(1) NOT NULL DEFAULT 0,
  `emotion` tinyint(1) NOT NULL DEFAULT 0,
  `social` tinyint(1) NOT NULL DEFAULT 0,
  `emotionINT` tinyint(1) NOT NULL DEFAULT 0,
  `socialINT` tinyint(1) NOT NULL DEFAULT 0,
  `countE` int(11) NOT NULL DEFAULT 0,
  `countS` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_map`
--

CREATE TABLE `srv_data_map` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL,
  `vre_id` int(11) DEFAULT NULL,
  `lat` float(19,15) NOT NULL,
  `lng` float(19,15) NOT NULL,
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `text` text CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `vrstni_red` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_number`
--

CREATE TABLE `srv_data_number` (
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `text2` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_random_blockContent`
--

CREATE TABLE `srv_data_random_blockContent` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `block_id` int(11) NOT NULL,
  `vrstni_red` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_random_spremenljivkaContent`
--

CREATE TABLE `srv_data_random_spremenljivkaContent` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `vrstni_red` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_rating`
--

CREATE TABLE `srv_data_rating` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `vrstni_red` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_textgrid_active`
--

CREATE TABLE `srv_data_textgrid_active` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_textgrid_archive1`
--

CREATE TABLE `srv_data_textgrid_archive1` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_textgrid_archive2`
--

CREATE TABLE `srv_data_textgrid_archive2` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_text_active`
--

CREATE TABLE `srv_data_text_active` (
  `id` int(10) UNSIGNED NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `text2` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_id` int(11) DEFAULT 0,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_text_archive1`
--

CREATE TABLE `srv_data_text_archive1` (
  `id` int(10) UNSIGNED NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `text2` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_id` int(11) DEFAULT 0,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_text_archive2`
--

CREATE TABLE `srv_data_text_archive2` (
  `id` int(10) UNSIGNED NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `text2` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_id` int(11) DEFAULT 0,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_upload`
--

CREATE TABLE `srv_data_upload` (
  `ank_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `code` char(13) NOT NULL,
  `filename` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_vrednost_active`
--

CREATE TABLE `srv_data_vrednost_active` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_vrednost_archive1`
--

CREATE TABLE `srv_data_vrednost_archive1` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_vrednost_archive2`
--

CREATE TABLE `srv_data_vrednost_archive2` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_vrednost_cond`
--

CREATE TABLE `srv_data_vrednost_cond` (
  `id` int(10) UNSIGNED NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `text` text COLLATE utf8_bin NOT NULL,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `srv_dostop`
--

CREATE TABLE `srv_dostop` (
  `ank_id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `aktiven` tinyint(4) NOT NULL DEFAULT 1,
  `alert_complete` tinyint(1) NOT NULL DEFAULT 0,
  `alert_expire` tinyint(1) NOT NULL DEFAULT 0,
  `alert_delete` tinyint(1) NOT NULL DEFAULT 0,
  `alert_active` tinyint(1) NOT NULL DEFAULT 0,
  `alert_complete_if` int(11) DEFAULT NULL,
  `dostop` set('edit','test','publish','data','analyse','export','link','mail','dashboard','phone','mail_server','lock') NOT NULL DEFAULT 'edit,test,publish,data,analyse,export,dashboard'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_dostop_language`
--

CREATE TABLE `srv_dostop_language` (
  `ank_id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_dostop_manage`
--

CREATE TABLE `srv_dostop_manage` (
  `manager` int(11) NOT NULL,
  `user` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_evoli_landingpage_access`
--

CREATE TABLE `srv_evoli_landingpage_access` (
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `email` varchar(100) NOT NULL DEFAULT '',
  `pass` varchar(100) NOT NULL DEFAULT '',
  `time_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `used` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_evoli_teammeter`
--

CREATE TABLE `srv_evoli_teammeter` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `skupina_id` int(11) NOT NULL DEFAULT 0,
  `email` varchar(100) DEFAULT '',
  `lang_id` int(11) NOT NULL DEFAULT 1,
  `url` varchar(255) DEFAULT '',
  `kvota_max` int(11) NOT NULL DEFAULT 0,
  `kvota_val` int(11) NOT NULL DEFAULT 0,
  `date_from` date NOT NULL DEFAULT '0000-00-00',
  `date_to` date NOT NULL DEFAULT '0000-00-00',
  `datum_posiljanja` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_evoli_teammeter_data_department`
--

CREATE TABLE `srv_evoli_teammeter_data_department` (
  `department_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_evoli_teammeter_delayed`
--

CREATE TABLE `srv_evoli_teammeter_delayed` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `date_from` date NOT NULL DEFAULT '0000-00-00',
  `tm_group` text NOT NULL,
  `emails` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_evoli_teammeter_department`
--

CREATE TABLE `srv_evoli_teammeter_department` (
  `id` int(11) NOT NULL,
  `tm_id` int(11) NOT NULL DEFAULT 0,
  `department` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_fieldwork`
--

CREATE TABLE `srv_fieldwork` (
  `id` int(10) UNSIGNED NOT NULL,
  `terminal_id` varchar(255) DEFAULT NULL,
  `sid_terminal` int(10) UNSIGNED DEFAULT NULL,
  `sid_server` int(10) UNSIGNED DEFAULT NULL,
  `secret` varchar(500) DEFAULT NULL,
  `lastnum` int(11) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_folder`
--

CREATE TABLE `srv_folder` (
  `id` int(11) NOT NULL,
  `naslov` varchar(50) NOT NULL,
  `parent` int(11) NOT NULL,
  `creator_uid` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_folder`
--

INSERT INTO `srv_folder` (`id`, `naslov`, `parent`, `creator_uid`) VALUES(1, 'Moje 1KA ankete', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `srv_gdpr_anketa`
--

CREATE TABLE `srv_gdpr_anketa` (
  `ank_id` int(11) NOT NULL,
  `1ka_template` enum('0','1') NOT NULL DEFAULT '0',
  `name` enum('0','1') NOT NULL DEFAULT '0',
  `email` enum('0','1') NOT NULL DEFAULT '0',
  `location` enum('0','1') NOT NULL DEFAULT '0',
  `phone` enum('0','1') NOT NULL DEFAULT '0',
  `web` enum('0','1') NOT NULL DEFAULT '0',
  `other` enum('0','1') NOT NULL DEFAULT '0',
  `other_text_slo` varchar(255) NOT NULL DEFAULT '',
  `other_text_eng` varchar(255) NOT NULL DEFAULT '',
  `about` text NOT NULL,
  `expire` enum('0','1') NOT NULL DEFAULT '0',
  `expire_text_slo` varchar(255) NOT NULL DEFAULT '',
  `expire_text_eng` varchar(255) NOT NULL DEFAULT '',
  `other_users` enum('0','1') NOT NULL DEFAULT '0',
  `other_users_text_slo` varchar(255) NOT NULL DEFAULT '',
  `other_users_text_eng` varchar(255) NOT NULL DEFAULT '',
  `export` enum('0','1') NOT NULL DEFAULT '0',
  `export_country_slo` varchar(255) NOT NULL DEFAULT '',
  `export_country_eng` varchar(255) NOT NULL DEFAULT '',
  `export_user_slo` varchar(255) NOT NULL DEFAULT '',
  `export_user_eng` varchar(255) NOT NULL DEFAULT '',
  `export_legal_slo` varchar(255) NOT NULL DEFAULT '',
  `export_legal_eng` varchar(255) NOT NULL DEFAULT '',
  `authorized` varchar(255) NOT NULL DEFAULT '',
  `contact_email` varchar(255) NOT NULL DEFAULT '',
  `note_slo` text NOT NULL DEFAULT '',
  `note_eng` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_gdpr_requests`
--

CREATE TABLE `srv_gdpr_requests` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `url` varchar(200) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `datum` datetime NOT NULL,
  `ip` varchar(100) NOT NULL DEFAULT '',
  `recnum` varchar(50) NOT NULL DEFAULT '',
  `text` text NOT NULL,
  `status` enum('0','1') NOT NULL DEFAULT '0',
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_gdpr_user`
--

CREATE TABLE `srv_gdpr_user` (
  `usr_id` int(11) NOT NULL,
  `type` enum('0','1') NOT NULL DEFAULT '0',
  `has_dpo` enum('0','1') NOT NULL DEFAULT '0',
  `organization` varchar(255) NOT NULL DEFAULT '',
  `organization_maticna` varchar(255) NOT NULL DEFAULT '',
  `organization_davcna` varchar(255) NOT NULL DEFAULT '',
  `dpo_phone` varchar(255) NOT NULL DEFAULT '',
  `dpo_email` varchar(255) NOT NULL DEFAULT '',
  `dpo_lastname` varchar(255) NOT NULL DEFAULT '',
  `dpo_firstname` varchar(255) NOT NULL DEFAULT '',
  `firstname` varchar(50) NOT NULL DEFAULT '',
  `lastname` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL DEFAULT '',
  `phone` varchar(255) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `country` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_glasovanje`
--

CREATE TABLE `srv_glasovanje` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `show_results` tinyint(4) NOT NULL DEFAULT 0,
  `show_percent` tinyint(4) NOT NULL DEFAULT 1,
  `show_graph` tinyint(4) NOT NULL DEFAULT 1,
  `spol` tinyint(4) NOT NULL DEFAULT 0,
  `stat_count` tinyint(4) NOT NULL DEFAULT 1,
  `stat_time` tinyint(4) NOT NULL DEFAULT 1,
  `embed` tinyint(4) NOT NULL DEFAULT 0,
  `show_title` tinyint(4) NOT NULL DEFAULT 0,
  `stat_archive` tinyint(4) NOT NULL DEFAULT 0,
  `skin` varchar(100) NOT NULL DEFAULT 'Classic'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_grid`
--

CREATE TABLE `srv_grid` (
  `id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `naslov` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `vrstni_red` int(11) NOT NULL DEFAULT 0,
  `variable` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `other` tinyint(4) NOT NULL DEFAULT 0,
  `part` tinyint(4) NOT NULL DEFAULT 1,
  `naslov_graf` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_grid_multiple`
--

CREATE TABLE `srv_grid_multiple` (
  `ank_id` int(11) NOT NULL,
  `parent` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `vrstni_red` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_grupa`
--

CREATE TABLE `srv_grupa` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `naslov` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `vrstni_red` int(11) NOT NULL DEFAULT 0,
  `timestamp` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_grupa`
--

INSERT INTO `srv_grupa` (`id`, `ank_id`, `naslov`, `vrstni_red`, `timestamp`) VALUES(-2, 0, 'system', 0, '2018-03-05 09:50:10');
INSERT INTO `srv_grupa` (`id`, `ank_id`, `naslov`, `vrstni_red`, `timestamp`) VALUES(-1, 0, 'system', 0, '2018-03-05 09:50:10');
INSERT INTO `srv_grupa` (`id`, `ank_id`, `naslov`, `vrstni_red`, `timestamp`) VALUES(0, 0, 'system', 0, '2018-03-05 09:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `srv_hash_url`
--

CREATE TABLE `srv_hash_url` (
  `hash` varchar(32) NOT NULL,
  `anketa` int(11) NOT NULL DEFAULT 0,
  `properties` text NOT NULL,
  `comment` varchar(256) NOT NULL,
  `access_password` varchar(30) DEFAULT NULL,
  `refresh` int(2) NOT NULL DEFAULT 0,
  `page` enum('data','analysis') DEFAULT 'data',
  `add_date` datetime DEFAULT NULL,
  `add_uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_help`
--

CREATE TABLE `srv_help` (
  `what` varchar(50) NOT NULL,
  `lang` tinyint(4) NOT NULL DEFAULT 1,
  `help` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_help`
--

INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('DataPiping', 1, '&#268;e respondent pri vpra&#353;anju npr. Q1 (npr. Katero je va&#353;e najljub&#353;e sadje) odgovori npr. \"jabolka\", lahko to vklju&#269;imo v vpra&#353;anje Q2, npr. \"Kako pogosto kupujete #Q1# na tr&#382;nici?\r\n\r\nPri tem je treba upo&#353;tevati:\r\n\r\n    * Vpra&#353;anje Q2, ki vklju&#269;i odgovor, mora biti na naslednji strani,\r\n    * Ime spremenljivke, ki se prena&#353;a (Q1) je treba spremeniti, ker je lahko predmet avtomatskega pre&#353;tevil&#269;enja, npr. spremenimo \"Q1\" v \"SADJE\"');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('DataPiping', 2, 'if the respondent in the question e.g. Q1 (e.g. what is your favorite fruit) answers e.g. \"apples\", this can be included in question Q2, e.g. \"How often do you buy #Q1# at the market?\r\n\r\nThe following must be taken into account:\r\n\r\n    * Question Q2, which includes the answer, should be on the next page,\r\n    * The name of the variable to be transmitted (Q1) needs to be changed because it can be subject to automatic renumbering, e.g. change \"Q1\" to \"FRUIT\"');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaychart_settings', 1, 'If a respondent\'s answer from a question, i.e. Q1 (\"Which fruit is your favourite?\") is \"apple\", we can include this to a subsequent question (i.e. Q2) with the #Q1# command, i.e. \"How often do you by #Q1# at the market?\"\r\n \r\nIt should be taken into account:\r\n\r\n     * Question Q2, which includes the answer, should be on the next page\r\n     * The name of the variable that is being piped (Q1) should be renamed because it may be subject to automatic enumeration, i.e. change \"Q1\" to \"FRUIT\" ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_checkboxes', 1, '<p>Kadar je izbrana opcija <b>\"Podatki\"</b>, se v tabeli prikazujejo podatki respondentov.</p>\r\n<ul><b>\"Status\"</b> prikazuje kon&#269;ne statuse enot:\r\n<li>6 - kon&#269;al anketo</li>\r\n<li>5 - delno izpolnjena</li>\r\n<li>4 - klik na anketo</li>\r\n<li>3 - klik na nagovor</li>\r\n<li>2 - epo&#353;ta - napaka</li>\r\n<li>1 - epo&#353;ta - neodgovor</li>\r\n<li>0 - epo&#353;ta - ni poslana)</li>\r\n<li>lurker - prazna anketa (1 = da, 0 = ne)</li>\r\n<li>Zaporedna &#353;tevilka vnosa</li>\r\n</ul>\r\n<p>Kadar je izbrana opcija <b>\"Parapodatki\"</b> prikazujemo meta podatke uporabnika: datum vnosa, datum popravljanja, &#269;ase po straneh, IP, JavaScript, podatke brskalnika, jezik.</p>\r\n<p>Kadar je izbrana opcija <b>\"Identifikatorji\"</b> prikazujemo sistemske podatke respondenta, ki so bili vne&#353;eni preko sistema za po&#353;iljanje vabil: ime, priimek, email itd.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_checkboxes', 2, '<p>If you select the \"<b>Data</b>\" option, the data of the respondents will be displayed in the table.</p>\r\n<ul><b>\"Status\" option:</b>\r\n<li>6 - survey completed</li>\r\n<li>5 - partially completed</li>\r\n<li>4 - entered first page</li>\r\n<li>3 - click on the intro</li>\r\n<li>2 - email - error</li>\r\n<li>1 - email - non-response</li>\r\n<li>0 - Email - not sent)</li>\r\n<li>Lurker - empty survey (1 = yes, 0 = no)</li>\r\n<li>Record number</li>\r\n</ul>\r\n<p>If you select the \"<b>Para data</b>\" option, users\" para data will be displayed in the table: insert date, edit date, time per page, IP, browser, JavaScript etc.</p>\r\n<p>If you select the \"<b> System data</b>\" option, the table will display respondents\" system information, such as name, surname, email etc.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_data', 1, 'Kadar je opcija izbrana, se v tabeli prika&#382;ejo podatki respondentov');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_data', 2, 'When the option is selected, the respondents\" data is displayed in the table');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_meta', 1, 'Priak&#382;e meta podatke uporabnika: datum vnosa, datum popravljanja, &#269;ase po straneh, IP, JavaScript, podatke brskalnika');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_meta', 2, 'Displays user meta data: entry date, correction date, times per page, IP, JavaScript, browser data');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_pdftype', 1, 'Raz&#353;irjen izpis pomeni izpis oblike, kakr&#353;ne je vpra&#353;alnik, skr&#269;en izpis pa izpi&#353;e vpra&#353;alnik z rezultati v skraj&#353;ani obliki. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-izvoza-pdfrtf-datotek-z-odgovori\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_pdftype', 2, 'Long display means the display in a form of the questionnaire, a short version means that questionnaire results will be presented in an abbreviated form.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_status', 1, '<p>status (6-kon&#269;al anketo, 5-delno izpolnjena, 4-klik na anketo, 3-klik na nagovor, 2-epo&#353;ta-napaka, 1-epo&#353;ta-neodgovor, 0-epo&#353;ta-ni poslana)</p><p>lurker - prazna anketa (1 = da, 0 = ne)</p><p>Zaporedna &#353;tevilka vnosa</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_status', 2, '<p>status (6-completed survey, 5-completed, 4-click on survey, 3-click on address, 2-email-error, 1-email-non-response, 0-email-not sent)</p><p>lurker - empty survey (1 = yes, 0 = no)</p><p>Sequence number of the entry</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_system', 1, 'Prika&#382;e sistemske podatke respondenta: ime, priimek, email...');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('displaydata_system', 2, 'Displays system data of the respondent: name, surname, email ....');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('dodaj_searchbox', 1, 'V zemljevid vklju&#269;i tudi iskalno okno, preko katerega lahko respondent tudi opisno poi&#353;&#269;e lokacijo na zemljevidu');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('dodaj_searchbox', 2, 'It also includes a search window in the map, through which the respondent can also search for a descriptive location on the map');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('edit_date_range', 1, 'Datum lahko navzdol omejimo z letnico, naprimer: 1951 ali kot obdobje -70, kar pomeni zadnjih 70 let. Podobno lahko omejimo datum tudi navzgor. Naprimer: 2013 ali kot obdobje +10, kar pomeni naslednjih 10 let');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('edit_date_range', 2, 'The date can be limited downward with a year, 1951 or as a period -70, which means the last 70 years. Similarly, we can also limit the date upward. For example: 2013 or as a period +10, which means the next 10 years.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('edit_variable', 1, 'Tu lahko poljubno spremenite privzeto ime spremenljivke, kar se upo&#353;teva tudi pri kasnej&#353;em izvozu podatkov. Paziti morate, da se imena spremenljivk ne podvajajo. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('edit_variable', 2, 'You can change the default variable name, which is also taken into account in the subsequent export of data. You should make sure that variable names are not duplicated.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('exportSettings', 1, 'Kadar izberete \"Izvozi samo identifikatorje\" se bodo izvozili samo identifikatorji (sistemski podatki repondenta), brez katerikoli drugih podatkov.<br>Kadar pa ne izva&#382;ate identifikatorjev pa lahko izvozite posamezne para podatke respondenta.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('help-centre', 1, '1KA center za pomo&#269;  lahko pomaga uporabnikom tudi pri konkretni anketi, vendar potrebuje za&#269;asen dostop do ankete, za kar je potrebna va&#353;a oz. avtorjeva odobritev. Seveda pa za&#269;asen dostop velja le za tiste dele ankete, kjer se je te&#382;ava pojavila, in  je potreben za re&#353;itev problema. Dostop omogo&#269;ite  s klikom na povezavo \"Dovoli dostop centu za pomo&#269;\".<span class=\"qtip-more\"><a href=\"https://www.1ka.si/db/24/439/Prirocniki/Dostop_do_moje_ankete_za__1KA_center_za_pomoc_uporabnikom/?&cat=309&p1=226&p2=735&p3=867&p4=0&id=867&from1ka=1\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('help-centre', 2, '1KA help centre can help users with a specific survey, but need a temporary access to the survey, which requires your approval. Of course, the temporary access only applies to those parts of the survey where the problem occurred and is necessary to solve the problem. You can enable the access by clicking the \"Grant access to help centre\".<span class=\"qtip-more\"><a href=\"http://english.1ka.si/index.php?fl=2&lact=1&bid=438&parent=24\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('individual_invitation', 1, 'Z individualiziranimi vabili lahko preverite, kdo iz seznama je odgovoril na anketo in kdo ne, kar je podlaga za po&#353;iljanje opomnikov. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/db/19/435/Pogosta%20vprasanja/Sledenje_respondentom__prednost_ali_slabost/?&cat=270&p1=226&p2=735&p3=789&p4=793&p5=804&id=804&cat=270&page=1&from1ka=1\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('individual_invitation', 2, 'With individualized invitations, you can check who on the list responded to the survey and who did not, which is the basis for sending reminders.<span class=\"qtip-more\"><a href = \"https://www.1ka.si/d/en/help/manuals/use-of-identification-codes-for-respondents\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('inv_recipiens_from_system', 1, 'Prejemniki bodo dodani iz obstoje&#269;ih podatkov v bazi, pri &#269;emer mora vpra&#353;alnik vsebovati sistemsko spremenljivko email.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('inv_recipiens_from_system', 2, 'Recipients will be added from the existing data in the database, which means, that the questionnaire must include a system variable - email.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('marker_podvprasanje', 1, 'V obla&#269;ek markerja dodaj podvpra&#353;anje');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('naslov_podvprasanja_map', 1, 'Besedilo podvpra&#353;anja v obla&#269;ku markerja');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('naslov_podvprasanja_map', 2, 'Sub-question text in the marker bubble');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('Prevodi', 0, '');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('spremenljivka_reminder', 1, 'V primeru, da respondent ni odgovoril na predvideno vpra&#353;anje, imamo tri mo&#382;nosti:\r\n<UL>\r\n<LI><b>Brez opozorila </b> pomeni, da respondenti lahko, tudi &#269;e ne odgovorijo na dolo&#269;eno vpra&#353;anje, brez opozorila nadaljujejo z anketo.</LI>\r\n<LI><b>Trdo opozorilo </b> pomeni, da respondenti, &#269;e ne odgovorijo na vpra&#353;anje s trdim opozorilom, dobijo obvestilo, da ne moreo nadaljevati z re&#353;evanjem ankete.</LI>\r\n<LI><b>Mehko opozorilo </b> pomeni, da respondenti, &#269;e ne odgovorijo, dobijo opozorilo, vendar lahko kljub temu nadaljujejo z re&#353;evanjem.</LI>\r\n</UL>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('spremenljivka_reminder', 2, 'If the respondent did not answer the intended question, we have three options:\r\n<UL>\r\n<LI><b>No warning</b> means that respondents can continue the survey without warning, even if they do not answer a particular question.</LI>\r\n<LI><b>Hard warning</b> means that if respondents do not answer the question with a hard warning, they are notified that they cannot continue solving the survey.</LI>\r\n<LI><b>Soft Warning</b> means that respondents do not receive a warning if they do not respond, but can still proceed with the rescue.</LI>\r\n</UL>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('spremenljivka_sistem', 1, 'S klikanjem na nastavitve v lahko zbiramo med dvema vrstama integracije vpra&#353;anja v anketo:\r\n<UL>\r\n<LI><b>Navadno</b> vpra&#353;anje,</LI>\r\n<LI><b>Sistemsko</b> vpra&#353;anje, ki omogo&#269;a uporabo vpra&#353;anja tudi izven samega vpra&#353;alnika. Gre za dva vidika:\r\n(1) sistemsko vpra&#353;anje (npr. ime) lahko ozna&#269;ite in uporabite, tako da nastopa v elektronskem obvestilu respondentu, kjer spremenljivko z njegovim imenom uporabimo v elektronskem sporo&#269;ilu, da se mu zahvalimo ali ga obvestimo o drugem valu anketiranja,\r\n(2) sistemsko vpra&#353;anje lahko neposredno uvozimo v bazo VNOSI mimo anketnega vpra&#353;alnika. Tako npr. lahko vnesemo ali nalo&#382;imo datoteko s telefonski &#353;tevilkami ali emaili respondentov (v takem primeru bomo spremenljivko ozna&#269;ili tudi kot skrito, saj respondentu ni treba vna&#353;ati emaila).</LI>\r\n</UL>\r\nV primeru uporabe email vabil preko 1KA email sistema, mora biti spremenljivka \"email\" ozna&#269;ena kot sistemska, ne glede, &#269;e je email vnesel respondent sam ali pa ga je pred anketiranjem vnesel administrator.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('spremenljivka_sistem', 2, 'By clicking on the settings in we can collect between two types of integration of questions into the survey:\r\n<UL>\r\n<LI><b>Normal</b> question</LI>\r\n<LI><b>System</b> question, which allows you to use the question outside the questionnaire itself. There are two aspects:\r\n(1) a system question (eg name) can be marked and used by appearing in an electronic notification to the respondent, where the variable with his name is used in an email to thank him or her or inform him about the second wave of the survey,\r\n(2) the system question can be imported directly into the ENTRY database via the survey questionnaire. Thus e.g. we can enter or upload a file with the telephone numbers or emails of the respondents (in this case we will also mark the variable as hidden, as the respondent does not have to enter the email).</LI>\r\n</UL>\r\nIn case of using email invitations via 1KA email system, the variable \" email \" must be marked as system, regardless of whether the email was entered by the respondent himself or by the administrator before the survey.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('spremenljivka_visible', 1, 'S klikanjem na nastavitve vidnosti lahko zbiramo med dvema vrstama integrcije vpra&#353;anja v anketo:\r\n<UL>\r\n<LI><b>vidno</b> vpra&#353;anje, ki bo vidno respondentom v kon&#269;nem vpra&#353;alniku,\r\n<LI><b>skrito</b> vpra&#353;anje, ki bo vidno le avtorju v urejanju vpra&#353;alnika. Mo&#382;nost uporabimo bodisi za skriti nagovor bodisi za sistemsko spremenljivko e-mail, ki je respondentom ni potrebno izpolniti.\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('spremenljivka_visible', 2, 'By clicking on visibility settings, we can collect between two types of integration of questions into the survey:\r\n<UL>\r\n<LI> <b> visible </b> question to be visible to respondents in the final questionnaire\r\n<LI> <b> hidden </b> question that will only be visible to the author when editing the questionnaire. We use the option either for a hidden address or for the system variable e-mail, which respondents do not need to fill in.\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_aapor_link', 1, 'AAPOR kalkulacije');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_aapor_link', 2, 'AAPOR calculations');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_activity_quotas', 1, 'Pri anketi lahko dolo&#269;ite kvoto (omejite &#353;tevilo odgovorov). Kvoto lahko postavite za vse odgovore ali samo na ustrezne enote. Ko bo na anketo odgovorilo toliko respondentov, kot ste dolo&#269;ili, se bo anketa deaktivirala za izpolnjevanje.\r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/trajanje-ankete-glede-na-datum-ali-stevilo-odgovorov-kvote\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_activity_quotas', 2, 'You can set a quota for the survey (limit the number of responses). You can set a quota for all responses or only for the appropriate units. When as many respondents as you specify respond to the survey, the survey will be deactivated for completion.\r\n\r\n<span class = \" qtip-more\"> <a href = \"https://www.1ka.si/d/en/help/manuals/survey-duration-based-on-date-or-the-number-of-responses\" target=\"_blank\">Read more </a> </span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_activity_quotas_valid', 1, '<ul>Ustrezne enote: \r\n<li>delno izpolnjene ankete (status 5)</li>\r\n<li>kon&#269;al anketo (status 6)</li>\r\n</ul>\r\n<ul>\r\nOstale enote:\r\n<li>kliki na nagovor (status 3)</li>\r\n<li>kliki na anketo (status 4)</li>\r\n</ul>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/statusi-enot-ustreznost-veljavnost-manjkajoce-vrednosti\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_activity_quotas_valid', 2, '<ul>Relevant units:\r\n<li>partially completed surveys (status 5)</li>\r\n<li>completed the survey (status 6)</li>\r\n</ul>\r\n<ul>\r\nOther units:\r\n<li>Clicks on speech (status 3)</li>\r\n<li>survey clicks (status 4)</li>\r\n</ul>\r\n<span class=\"qtip-more\"><a href = \"https://www.1ka.si/d/en/help/manuals/status-of-units-relevance-validity-and-missing-values\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_alert_show_97', 1, 'Funkcija prikaz \"Neustrezno\" ob opozorilu, da se respondentu prika&#382;e odgovor \"Neustrezno\" &#353;ele po tem, ko ta ni odgovoril na vpra&#353;anje. Vpra&#353;anje mora biti obvezno ali imeti opozorilo.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_alert_show_97', 2, 'The \"Inappropriate\" function is displayed when the respondent is warned that the answer \"Inappropriate\" is displayed only after the respondent has not answered the question. The question must be mandatory or have a warning.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_alert_show_98', 1, 'Funkcija prikaz \"Zavrnil\" ob opozorilu, da se respondentu prika&#382;e odgovor \"Zavrnil\" &#353;ele po tem, ko ta ni odgovoril na vpra&#353;anje. Vpra&#353;anje mora biti obvezno ali imeti opozorilo.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_alert_show_98', 2, 'The \"Reject\" function is displayed when the respondent is warned that the \"Rejected\" answer is displayed only after the respondent has not answered the question. The question must be mandatory or have a warning.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_alert_show_99', 1, 'Funkcija prikaz \"Ne vem\" ob opozorilu, da se respondentu prika&#382;e odgovor \"Ne vem\" &#353;ele po tem, ko ta ni odgovoril na vpra&#353;anje. Vpra&#353;anje mora biti obvezno ali imeti opozorilo. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_alert_show_99', 2, 'The \"I don\"t know\" function is displayed when the respondent is warned that the answer \"I don\"t know\" is displayed only after the respondent has not answered the question. The question must be mandatory or have a warning.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_arhiv_podatki', 1, 'Ta mo&#382;nost se pogosto uporablja pri prenosu anket iz 1ka.si ali 1ka.arnes.si na svojo lastno in&#353;talacijo, virtualno domeno, drug ra&#269;un in obratno.<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/prenos-ankete-med-domenami-1ka\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_arhiv_podatki', 2, 'This option is often used when transferring surveys from 1ka.si or 1ka.arnes.si to your own installation, virtual domain, other account and vice versa.<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/transfer-survey-between-1ka-domains\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_arhiv_vprasalnik', 1, 'Ta mo&#382;nost se pogosto uporablja pri prenosu anket iz 1ka.si ali arnes.1ka.si na svojo lastno in&#353;talacijo, virtualno domeno, drug ra&#269;un in obratno.<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/prenos-ankete-med-domenami-1ka\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_arhiv_vprasalnik', 2, 'This option is often used when transferring surveys from 1ka.si or arnes.1ka.si to your own installation, virtual domain, other account and vice versa. <span class = \"qtip-more\"> <a href=\"https://www.1ka.si/d/en/help/manuals/transfer-survey-between-1ka-domains\" target=\"_blank\"> Read more </a> </span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_block_ip', 1, 'Tu lahko blokirate respondentov ponovni vnos vpra&#353;alnika, glede na minute (10, 20 ali 60 minut) ali glede na ure (12 ali 24 ur).<span class=\"qtip-more\"><span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/uporaba-ip-naslovov-piskotkov-za-nadzor-nad-podvojenimi-vnosi\" target=\"_blank\">Preberi ve&#269;</a></span></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_block_ip', 2, 'Here you can block the responent\'s attempt to re-take the questionnare for 10, 20 or 60 minutes, or for 12 or 24 hours. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/using-the-ip-address-and-cookies-to-control-duplicate-entries\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_block_random', 1, 'Randomizacija vsebine bloka');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_block_random', 2, 'Randomization of block content');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_branching_expanded', 1, 'Skr&#269;en pogled vpra&#353;anj omogo&#269;a bolj&#353;i pregled nad celotnim vpra&#353;alnikom in njegovo strukturo- prelomi strani, bloki, pogoji, zankami itd.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_branching_expanded', 2, 'A concise view of the questions provides a better overview of the entire questionnaire and its structure - page breaks, blocks, conditions, loops, etc.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_branching_popup', 1, 'Nastavitev prikaza map v odprtem na&#269;inu (en stolpec) ali pa v zamaknjenem na&#269;inu (ve&#269; vzporednih stolpcev). ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_branching_popup', 2, 'You can set the display of maps in open mode (one column) or in offset mode (several parallel columns).');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_calculation_missing', 1, 'Missing kot 0');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_calculation_missing', 2, 'Missing as 0');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_choose_skin', 1, 'Tu lahko izberete eno od vnaprej pripravljenih vizualnih predlog ankete. Kasneje, ko je anketa &#382;e ustvarjena, lahko v nastavitvah to predlogo poljubno spreminjate in jo tudi dodatno prilagodite (npr. vrsta in velikost pisave, barva ozadja itd.). <span class=\"qtip-more\"><a href=\"https://www.1ka.si/c/849/Oblika/?preid=849&from1ka=1\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_choose_skin', 2, 'You can select one of the pre-prepared visual survey designs. Later, when the survey has already been created, you can select a different design and customize it further (e.g. font type and size, background color, etc.). <span class=\"qtip-more\"><a href=\" http://english.1ka.si/c/849/Design/?preid=792\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_collect_all_status_0', 1, '<ul>Statusi ustrezni so:\r\n<li>[6] Kon&#269;al anketo</li>\r\n<li>[5] Delno izpolnjena</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_collect_all_status_0', 2, '<ul>The relevant statuses are:\r\n<li>[6] Completed the survey</li>\r\n<li>[5] Partially fulfilled</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_collect_all_status_1', 1, '<ul>\r\n<li>[6] Kon&#269;al anketo</li>\r\n<li>[5] Delno izpolnjena</li>\r\n<li>[4] Klik na anketo</li>\r\n<li>[3] Klik na nagovor</li>\r\n<li>[2] Napaka pri po&#353;iljanju e-po&#353;te</li>\r\n<li>[1] E-po&#353;ta poslana (neodgovor)</li>\r\n<li>[0] E-po&#353;ta &#353;e ni bila poslana</li>\r\n<li>[-1] Neznan status</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_collect_all_status_1', 2, '<ul><li>[6] Kon&#269;al survey</li>\r\n<li>[5] Partially fulfilled</li>\r\n<li>[4] Click on the survey</li>\r\n<li>[3] Click to speak</li>\r\n<li>[2] Error sending email</li>\r\n<li>[1] Email sent (no reply)</li>\r\n<li>[0] Email not yet sent</li>\r\n<li>[-1] Unknown status</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_collect_data_setting', 1, '<p>Generiranje tabele s podatki:</p>\r\n<p>S poljem \"le ustrezni\" izbiramo med statusi enot, ki se bodo generirali kot potencialni za analize, izvoz podatkov in prikaz vnosov. Kadar je polje \"le ustrezni\" izbrano se upo&#353;tevajo samo enote z statusom: 6 - Kon&#269;al anketo in 5 - Delno izpolnjena.</p>\r\n<p>S poljem \"meta podatki\" izbiramo ali se generirajo tudi meta podatki kot so: lastnosti ra&#269;unalnika, podrobni podatki o e-po&#353;tnih vabilih in telefonskih klicih.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_collect_data_setting', 2, '<p>Generate table data:</p>\r\n<p>With \"only valid status\" checkbox we choose between the status of the units that will be generated as the potential for analysis, data export and entries display.</p>\r\n<p>If the \"only valid status\" field is checked, then only units with status 6 (completed survey) and 5 (partially completed) will be considered.</p> \r\n<p>With \"meta data\" field we decide, if meta data, such as computer properties, e-mail invitation details and telephone calls will also be generated.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_comments_only_unresolved', 1, 'Vsa vpra&#353;anja s komentarji se prika&#382;ejo, &#269;e sta obe mo&#382;nosti izklopljeni (tako \"Vsa vpra&#353;anja\" kot \"Samo nere&#353;eni komentarji\"). <span class=\"qtip-more\"><a href=\" https://www.1ka.si/d/sl/pomoc/prirocniki/komentarji\" target=\"_blank\"> Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_comments_only_unresolved', 2, 'Any questions with comments appear if both options are turned off (\"All questions\" as well as \"Display only unresolved comments\"). <span class=\"qtip-more\"><a href = \"https://www.1ka.si/d/en/help/manuals/comments\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_concl_deactivation_text', 1, 'Obvestilo pri deaktivaciji');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_concl_deactivation_text', 2, 'Deactivation notice');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_concl_PDF_link', 1, 'Na koncu ankete prika&#382;e ikono s povezavo do PDF dokumenta z odgovori respondenta.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_concl_PDF_link', 2, 'At the end of the survey, it displays an icon with a link to the respondent\'s PDF document.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_continue_later_setting', 1, 'Funkcija omogo&#269;a respondentu, da prekine z odgovarjanjem na anketo ter nadaljuje kasneje. Respondent vpi&#353;e svoj email naslov, na katerega prejme URL, preko katerega bo kasneje nadaljeval z odgovarjanjem.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_continue_later_setting', 2, 'This function allows the respondent to terminate survey completion and continue later. A respondent must enter their email address, where they receive a URL with which they continue the survey completion process.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_cookie', 1, 'Pi&#353;kotek (ang. <i>cookie</i>) je koda, ki se instalira v ra&#269;unalnik anketiranca, s &#269;imer lahko 1KA anketiranca identificira tudi pri moreitnem ponovljenem poskusu izpolnjevanja ankete.\r\n\r\n-	\"Do konca izpolnjevanja ankete\" pomeni, da se pi&#353;kotek hrani le v &#269;asu trajanja izpolnjevanja vpra&#353;alnika, ob koncu izpolnjevanja pa se izbri&#353;e. Respondent lahko zato iz istega ra&#269;unalnika neovirano izpolnjuje anketo &#353;e enkrat.\r\n\r\n-	\"Do konca seje\" pomeni, da se pi&#353;kotek hrani za &#269;as trajanja seje brskalnika, izbri&#353;e pa se &#353;ele, ko se zapre brskalnik. Respondent se bo v &#269;asu trajanja seje prepoznal in obravnaval bodisi tako, da bo dobil obvestilo, da je anketa zanj nedostopna, ker jo je &#382;e izpolnil, bodisi pa jo bo lahko popravil. Ko posameznik zapre brskalnik pa je ob ponovnem zagonu brskalnika obravnavan kot nov respondent.\r\n\r\n-	\"1 uro\" ali \"1 mesec\": pi&#353;kotek se hrani &#353;e eno uro ali en mesec po zaklju&#269;ku ankete. V tem &#269;asu bo uporabnik ob ponovnem vra&#269;anju na anketo prepoznan in obravnavan v skladu z nadaljnjimi nastavitvami: bodisi bo dobil obvestilo, da je anketa zanj nedostopna, ker jo je &#382;e izpolnil, bodisi jo bo lahko popravljal.\r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-piskotkov\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_cookie', 2, 'A cookie is a code that is stored on the respondent\'s computer, through which the 1KA tool can authenticate a possible re-attempt of the respondent to fill out the survey.\r\n- \"Until the end of questionnaire\" means that the cookie is only stored while the respondent is filling out the questionnaire. After the survey is completed, the cookie is deleted. Therefore, the user can start the survey again from the same device. \r\n\r\n- \"Until the end of the browser session\" means that the cookie is stored for the duration of the browser session, i.e. also after the survey is completed. It is deleted when the respondent closes the current browser session. If the respondent returns to the survey page during the browser session they will be recognised in accordance with the following settings: they will either recieve a notification that the survey cannot be accessed because it has already been filled out or the survey can be altered by the respondent. Once the respondent closes the browser session they will be treated as a new respondent.\r\n\r\n- \"1 hour\" or \"1 month\": the cookie is saved for one hour (or one month) after the survey is completed. During this time the respondent will be recognized when they return to the page in accordance with the following settings: they will either recieve a notification that the survey cannot be accessed because it has already been filled out or the survey can be altered by the respondent.\r\n\r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/about/terms-of-use/cookie-policy\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_cookie_continue', 1, '&#268;e je vklopljena omejitev, da uporabnik ne morenadaljevati z izpolnjevanjem ankete brez sprejetja pi&#353;kotka, mora biti v anketi obvezno prikazan uvod.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_cookie_continue', 2, 'if the restriction is enabled that the user cannot continue filling in the survey without accepting a cookie, the introduction must be shown in the survey.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_cookie_return', 1, '&#268;e &#382;elite, da respondent ob vrnitvi na anketo ponovno pri&#269;ne z re&#353;evanjem celotne ankete, potem ozna&#269;ite to mo&#382;nost. \r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-za-dostop-respondentov-piskotki-ip-naslovi-gesla-sistemsko-prepoznavanja\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_cookie_return', 2, 'Select this option if you wish that the respondent starts filling out the survey from the beginning when they return. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/settings-for-respondent-access-cookies-and-passwords\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_create_form', 1, 'Forma je enostavna anketa na samo eni strani (npr. obrazec, kratka anketa, registracija, email lista, prijava na dogodke itd.).\r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/o-1ka/splosen-opis/tipi-vprasalnikov/forme\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_create_form', 2, 'A form is a simple survey on a single page (e.g. form, short survey, registration form, mailing list,  event registration...). <span class=\"qtip-more\"><a href=\" http://english.1ka.si/c/828/Forms/?preid=879\" target=\"_blank\">Read more> </a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_create_poll', 1, 'Glasovanje je anketa z enim samim vpra&#353;anjem (peticija, volitve, potrditve, dolo&#269;eno mnenje/strinjanje itd.). <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/o-1ka/splosen-opis/tipi-vprasalnikov/glasovanje\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_create_poll', 2, 'Voting is a survey with a single question (petition, elections, confirmation, particular opinion/agreement...) <span class=\"qtip-more\"><a href=\"http://english.1ka.si/c/835/Voting/?preid=828\" target=\"_blank\">Read more></a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_create_survey', 1, 'Anketa je splo&#353;en, poljuben vpra&#353;alnik, ki je lahko enostaven ali kompleksen (npr. pogoji, bloki, kvizi, testi, email vabila, telefonska anketa itd.). <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/o-1ka/splosen-opis/tipi-vprasalnikov/ankete\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_create_survey', 2, 'A survey is a general, arbitrary questionnaire, which can be simple or complex (e.g. conditions, blocks, quizzes, tests, email invitations, telephone surveys, etc.). <span class=\"qtip-more\"><a href=\"http://english.1ka.si/c/879/Survey/?preid=835\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_create_survey_from_text', 1, 'Besedilo vpra&#353;anj in odgovorov prilepite oziroma vpi&#353;ete v kvadrat na levi strani, vzporedno pa se na desni strani prikazuje predogled vpra&#353;anj. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/uvoz-besedila-kopiranje-besedila-1ka\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_create_survey_from_text', 2, 'Paste or enter the text of the questions and answers in the box on the left, and a preview of the questions is displayed in parallel on the right.\r\n\r\n<span class=\"qtip-more\"> <a href = \"https://www.1ka.si/d/en/help/manuals/import-text-copying-text-to-1ka\" target=\"_blank\">Read more</a> </span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_creport', 1, 'V prilagojenem poro&#269;ilu lahko:<ul><li>naredite poljuben izbor spremenljivk</li><li>jih urejate v poljubnem vrstnem redu</li><li>kombinirate grafe, frekvence, povpre&#269;ja...</li><li>dodajate komentarje</li></ul><span class=\"qtip-more\"><a href=\"http://www.1ka.si/db/19/427/Pogosta%20vpra.anja/Porocila_po_meri/?&cat=286&p1=226&p2=735&p3=789&p4=794&p5=865&id=865\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_creport', 2, 'In a custom report, you can: <ul><li>make any selection of variables</li><li>edit them in any order</li><li>combine graphs, frequencies, averages...</li><li>add comments</li></ul><span class = \"qtip-more\"> <a href =\"https://www.1ka.si/d/sl/pomoc/prirocniki/porocila-meri\" target=\"_blank\">Read more </a> </span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_crosstab_inspect', 1, 'Mo&#382;nost \"ZOOM\" oz. \"Kdo je to\" omogo&#269;a da s klikom na &#382;eleno frekvenco, ogledamo katere enote se v njej nahajajo.<span class=\"qtip-more\"><a href=\"https://www.1ka.si/db/24/338/Prirocniki/ZOOM/?&cat=309&p1=226&p2=735&p3=867&p4=0&id=867\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_crosstab_inspect', 2, 'Option \"ZOOM\" or \"Who is this\" allows, that with a click on the desired frequency, you can see all units (with all of the answers) in a given cell. <span class=\"qtip-more\"><a href=\"http://english.1ka.si/db/24/338/Guides/ZOOM/?&p1=226&p2=735&p3=0&p4=0&id=735\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_crosstab_residual', 1, 'Obarvane celice - glede na prilagojene vrednosti rezidualov (Z) - ka&#382;ejo, ali in koliko je v celici ve&#269; ali manj enot v primerjavi z razmerami, ko celici nista povezani. Bolj temna barva (rdeca ali modra) torej pomeni, da se v celici nekaj dogaja. Natan&#269;ne vrednosti residualov dobimo, &#269;e tako izberemo v NASTAVITVAH. Nadaljnje podrobnosti o izra&#269;unavanju in interpetaciji rezidualov najdete v priro&#269;niku<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/reziduali-tabelah\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_crosstab_residual', 2, 'The 1KA application uses and colours the values 1.0, 2.0 and 3.0 for values of adjusted residuals, which roughly signal the strength of the correlation in a particular cell, i.e. the strength of deviation from the assumptions of the null hypothesis. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/residuals-tables\" target=\"_blank\">Preberi ve&#269;</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_crosstab_residual2', 1, 'Reziduali omogo&#269;ajo izredno enostavno in u&#269;inkovito analizo dogajanja v tabeli, saj natan&#269;no poka&#382;ejo, kje to&#269;no prihaja do povezanosti med spremenljivkami. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/reziduali-tabelah\" target=\"_blank\">Preberi ve&#269;</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_crosstab_residual2', 2, 'Residuals make it extremely easy and efficient to analyze what’s going on in the table, as they show exactly where the correlation between the variables comes from. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/residuals-tables\" target=\"_blank\">Preberi ve&#269;</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_data_filter', 1, 'Zbrane podatke lahko filtrirate glede na spremenljivke, statuse, pogoje ali obdobje.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_data_filter', 2, 'Data can be filtered according to variables, statuses, conditions, or time periods.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_data_onlyMySurvey', 1, 'Kadar anketo resujete kot uporabnik Sispleta in imate vklopljeno opcijo da anketa prepozna respondenta iz CMS, lahko z enostavnim klikom pregledate le vase ankete.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_data_onlyMySurvey', 2, 'When you fill out a survey as a Sisplet user and the \"Recognize respondents from CMS\" option is on, you can browse through only your survey with a simple click.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_data_only_valid', 1, 'Ustrezne enote so tiste ankete, kjer je respondent odgovoril vsaj na eno vpra&#353;anje. V vseh analizah so privzeto vklju&#269;ene le ustrezne enote. Ostale - za vsebinske analize neustrezne enote - namre&#269; vklju&#269;ujejo prazne ankete (npr. anketirance, ki so zgolj kliknili na nagovor) in so zanimive predvsem za analizo procesa odgovarjanja - njihov sumarni pregled pa je v zavihku STATUS.\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/statusi-enot-ustreznost-veljavnost-manjkajoce-vrednosti\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_data_only_valid', 2, 'Valid units are those surveys where the respondent filled out at least one question. Only valid units are included by default in all analyses. Other units - invalid for analysis of content - include empty surveys (for example, when somebody only clicked on the introduction) and are mainly only of interest in the context of analysis of response process. Their summary review is in the DASHBOARD tab. <span class=\"qtip-more\"><a href = \"https://www.1ka.si/d/en/help/manuals/status-of-units-relevance-validity-and-missing-values\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_data_print_preview', 1, 'V hitrem seznamu je izpisanih prvih pet spremenljivk. Primeren je za hiter izpis <a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/prijavnica\" target=\"blank\">prijavnic</a> in <a href=\"https://www.1ka.si/d/sl/o-1ka/splosen-opis/tipi-vprasalnikov/forme\" target=\"_blank\">form</a>. Za podrobne izpise uporabite obstoje&#269;e izvoze. Dodaten izbor spremenljivk lahko naredite v opciji \"Spremenljivke\". <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/podatki/pregledovanje/?from1ka=1=\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_data_print_preview', 2, 'The \"Quick list\" option displays a list of responses for the first five questions. It is suitable for a quick display of <a href=\"https://www.1ka.si/d/en/help/manuals/registration-form\" target=\"_blank\"> registration forms</a> and <a href=\"https://www.1ka.si/d/en/about/general-description/questionnaire-types/forms\" target=\"_blank\"> forms</a>, which you can also export. You can determine which variables the list should use with \"Variables\" option. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/user-guide/data/browse\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_diag_complexity', 1, '<ul>Kompleksnost:\r\n<li>brez pogojev ali blokov => zelo enostavna anketa</li>\r\n<li>1 pogoj ali blok => enostavna anketa</li>\r\n<li>1-10 pogojev ali blokov => zahtevna anketa</li>\r\n<li>10-50 pogojev ali blokov => kompleksna anketa</li>\r\n<li>ve&#269; kot 50 pogojev ali blokov => zelo kompleksna anketa</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_diag_complexity', 2, '<ul>Complexity:\r\n<li>no conditions or blocks => very simple survey</li>\r\n<li>1 condition or block => simple survey</li>\r\n<li>1-10 conditions or blocks => demanding survey</li>\r\n<li>10-50 conditions or blocks => complex survey</li>\r\n<li>more than 50 conditions or blocks => very complex survey</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_diag_time', 1, '<ul>Predviden &#269;as izpolnjevanja::\r\n<li>do 2 min => zelo kratka anketa</li>\r\n<li>2-5 min => kratka anketa</li>\r\n<li>5-10 min => srednje dolga anketa</li>\r\n<li>10-15 min => dolga anketa</li>\r\n<li>15-30 min => zelo dolga anketa</li>\r\n<li>30-45 min => obse&#382;na anketa</li>\r\n<li>ve&#269; kot 45 min => zelo obse&#382;na anketa</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_diag_time', 2, '<ul>Estimated completion time:\r\n<li>up to 2 min => very short survey</li>\r\n<li>2-5 min => short survey</li>\r\n<li>5-10 min => medium length survey</li>\r\n<li>10-15 min => long survey</li>\r\n<li>15-30 min => very long survey</li>\r\n<li>30-45 min => extensive survey</li>\r\n<li>more than 45 min => very extensive survey</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_disabled_question', 1, 'Vpra&#154;anje je onemogo&#269;eno za respondente');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_disabled_question', 2, 'The question is disabled for respondents');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_dostop', 1, 'Nastavitve glede urejanja ankete. Urejate jo vi kot avtor in ostali, glede na va&#353;e nastavitve.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_dostop', 2, 'Survey editing settings. It is edited by you as the author and others, according to your settings.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_dostop_password', 1, 'Generirate lahko ve&#269; gesel in na ta na&#269;in ustvarjate skupine respondentov, ki jih lahko lo&#269;ite pri analizah ali postavite pogoje na vpra&#353;anja. Pomembno je, da opozorite respondenta, da se mu pi&#353;kotek shrani do konca seje brskalnika. \r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-za-dostop-respondentov-piskotki-ip-naslovi-gesla-sistemsko-prepoznavanja\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_dostop_password', 2, 'You can generate multiple passwords and thus create respondent groups, which can be separated in the analysis. It is important that you warn the respondents that the browser cookie is saved until the end of the browser session. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/settings-for-respondent-access-cookies-and-passwords\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_dostop_users', 1, 'Seznam uporabnikov, ki lahko urejajo anketo.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_dostop_users', 2, 'The list of users who can edit the survey. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_dropdown_quickedit', 1, 'Omogo&#269;a hitro urejanje podatkov v zavihku \"PODATKI\" s pomo&#269;jo rolete. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_dropdown_quickedit', 2, 'Allows you to quickly edit the data in \"DATA\" tab with the help of a dropdown menu.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_email_server_settings', 1, '<ul>\r\n<li><strong>1KA - privzeto:</strong> vabila po&#353;ljete preko na&#353;ega stre&#382;nika, kjer je po&#353;iljatelj 1KA (info@1ka.si), v polju \"Odgovor za\" pa je privzeto vpisan email naslov, s katerim ste registrirani na 1KA.</li>\r\n<li><strong>Gmail</strong>: v okviru sistema za po&#353;iljanje 1KA vabila po&#353;ljete prek va&#353;ega Gmail uporabni&#353;kega ra&#269;una.</li>\r\n<li><strong>Lastne SMTP nastavitve</strong>: omogo&#269;a po&#353;iljanje vabil prek lastnega po&#353;tnega stre&#382;nika.</li>\r\n</ul>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/db/24/466/Prirocniki/Posiljanje_emailov_preko_poljubnega_streznika_npr_Gmail/?&cat=256&p1=226&p2=735&p3=789&p4=793&p5=0&id=793&from1ka=1\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_email_server_settings', 2, '<ul>\r\n<li><strong> 1KA - default </strong>: invitations are sent via our server, where the sender is 1KA (info@1ka.si), and in the field \"Reply to\" is the default email address, with to which you are registered on 1KA. </li>\r\n<li><strong> Gmail </strong>: Send 1KA invitations via your Gmail account within the 1KA system. </li>\r\n<li><strong> Custom SMTP settings </strong>: Allows you to send invitations through your own mail server.</li>\r\n</ul>\r\n<span class = \" qtip-more\"> <a href = \" https://www.1ka.si/d/en/help/manuals/sending-emails-via-an-arbitrary-server-eg-gmail\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_email_to_list', 1, 'V seznam za obve&#353;&#269;anje se dodajo tudi ro&#269;no vneseni email naslovi. Za pravilno delovanje tega postopka morate v anketo dodati sistemsko vpra&#353;anje \"email\", ki mora biti vidno (t.j. da ni v naprednih mo&#382;nostih nastavljeno kot skrito).');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_email_to_list', 2, 'Manually entered email addresses are also added to the notification list. For this procedure to work properly, you need to add a \"email\" system question to the survey, which must be visible (i.e. not set as hidden in advanced options).');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_email_with_data', 1, 'Opcija povezovanje identifikatorjev je na voljo le administratorjem ter omogo&#269;a hkraten prikaz anketnih podatkov in identifikatorjev. Namenjena je predvsem v namene testiranja in interno uporabo na lastnih in&#353;talacijah, zato ponovno opozarjamo na skladnost z zakonom o varstvu osebnih podatkov.  <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/anketni-podatki-parapodatki-identifikatorji-sistemske-spremenljivke\" target=\"_blank\">Preberi ve&#269;</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_email_with_data', 2, 'Connection option identifiers is only available to administrators and allows simultaneous display of survey data and identifiers. It is intended primarily for testing purposes and internal use on own installations, so we reiterate compliance with the Personal Data Protection Act. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/survey-data-paradata-identifiers-and-system-variables\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_embed_fixed', 1, 'Tu lahko skopirate kodo va&#353;e ankete (verzija brez JavaScripta in jo vdelate v va&#353;o spletno stran. Za modifikacijo kode je potrebno spremeniti parameter za vi&#353;ino \"height\".');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_embed_fixed', 2, 'You can copy the code of your survey (version without JavaScript) and embed it into your own website. To modificate the code you need to change the \"height\" parameter. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_embed_js', 1, 'Tu lahko skopirate kodo va&#353;e ankete (Javascript verzija) in jo vdelate v va&#353;o spletno stran.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_embed_js', 2, 'You can copy the code of your survey (Javascript version) and embed it into your own website.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_evalvacija_strani', 1, 'Ocenjujete lahko toliko razli&#269;nih spletnih strani, kolikor imate &#353;tevilo strani v va&#353;i anketi.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_evalvacija_strani', 2, 'You can rate as many different websites as you have the number of pages in your survey.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_export_full_meta', 1, 'Izvozi podatke in parapodatke');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_export_full_meta', 2, 'Export data and paradata');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_gdpr_user_options', 1, 'Prejemanje obvestil o novostih, nadgradnjah in dogodku DSA (Dan spletnega anketiranja). <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/dan-spletnega-anketiranja\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_gdpr_user_options', 2, 'Receive notifications of DSA news, upgrades and events (Online Survey Day). <span class = \"qtip-more\"> <a href=\"https://www.1ka.si/d/en/web-survey-day\" target=\"_blank\">Read more </a> </ span >');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_glasovanje_archive', 1, 'Dodaj anketo v arhiv glasovanja. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_glasovanje_archive', 2, 'Add survey to voting archive');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_google_2fa_options', 1, 'Vklop dvo-nivojskega preverjanja pristnosti pomeni, da pri prijavi v orodje 1KA poleg izbranega gesla vpi&#353;ete tudi posebno kodo, ki jo pridobite preko lo&#269;ene aplikacije za generiranje kod.<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-piskotkov\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_google_2fa_options', 2, 'Enabling two-level authentication means that when logging in to the 1KA tool, in addition to the selected password, you also enter a special code, which you obtain via a separate application for generating codes.\r\n\r\n<span class = \" qtip-more\"> <a href = \" https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-piskotkov\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_grid_var', 1, '<p>Vrednosti odgovorov so privzeto razvr&#353;&#269;ene nara&#353;&#269;ajo&#269;e in se pri&#269;nejo z 1. Vrednosti se lahko razvrstijo tudi padajo&#269;e (s klikom na checkbox Razvrsti vrednosti padajo&#269;e).</p>\r\n<ul>Vrednosti odgovorov se lahko spremenijo. Pri tem velja upo&#353;tevati naslednja pravila:\r\n<li>Vrednosti se ne smejo ponavljati (razen v primeru vklopljenega modula Kviz).</li>\r\n<li>Uporabljajo se lahko samo cela &#353;tevila (brez decimalnih &#353;tevil).</li>\r\n<li>Vrednosti -1, -2, -3, -4, -5, -6, -96, -97, -98, -99 so rezervirane za ozna&#269;evanje manjkajo&#269;ih vrednosti in se jih ne sme uporabljati za vrednotenje drugih odgovorov.</li>\r\n</ul>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/urejanje-vrednosti-odgovorov\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_grid_var', 2, '<p>The response values are sorted ascending by default and starting with 1. Values can also be sorted descending (by clicking on the checkbox Sort values descending).</p>\r\n<ul>The values of the answers can be changed. In doing so, the following rules apply:\r\n<li>Values must not be repeated (except in the case when Quiz module is turned on).</li>\r\n<li> Only integers (without decimal numbers) can be used. </li>\r\n<li>The values -1, -2, -3, -4, -5, -6, -96, -97, -98, -99 are reserved for marking missing values and should not be used to evaluate other responses.</li>\r\n</ul>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/edit-responses-values\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_grupe', 1, 'Vpra&#353;alnik je razdeljen na posamezne strani. Vsaka stran naj vsebuje primerno &#353;tevilo vpra&#353;anj. Tukaj vidite izpisane vse strani vpra&#353;alnika, vklju&#269;no z uvodom in zaklju&#269;kom.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_grupe', 2, 'The questionnaire is divided into individual pages. Each page should contain an appropriate number of questions.\r\n\r\nHere you can see all the pages of the questionnaire, including the introduction and conclusion. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_grupe_branching', 1, '<b>ANKETA Z VEJITVAMI IN POGOJI:</b> anketni vpra&#353;alnik potrebuje  preskoke, bloke, gnezdenje pogojev ipd.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_grupe_branching', 2, '<b>BRANCH AND CONDITIONS SURVEY:</b> The survey questionnaire requires skips, blocks, nesting conditions, etc.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_grupe_recount_branching', 1, '<p>&#268;e spreminjate vrstni red vpra&#353;anj, lahko vrstni red ponovno vzpostavite s pre&#353;tevil&#269;enjem celotnega vpra&#353;alnika.</p>\r\n<p>V primeru, da ste sami ro&#269;no preimenovali vpra&#353;anje, se to ne bo upo&#353;tevalo pri avtomatskem pre&#353;tevil&#269;evanju.</p>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/zakaj-vprasanja-niso-ostevilcena-zaporedno\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_grupe_recount_branching', 2, '<p>If you change the order of the questions, the questions can be renumbered for the entire questionnaire by clicking on the # icon.</p>\r\n<span class=\"qtip-more\"><a href=\"http://english.1ka.si/index.php?fl=2&lact=1&bid=393&parent=19\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hierarchy_admin_help', 1, 'Tukaj lahko odstranjujete celotni nivo ali pa s klikom na checkbox izberete, &#269;e so &#353;ifranti znotraj polja unikatni.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hierarchy_admin_help', 2, 'Here you can remove the entire level or click on the checkbox to choose if the code lists within the field are unique.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hierarchy_edit_elements', 1, 'Za vsak izbran nivo se lahko dodaja nove elemente. Z izbiro mo&#382;nosti brisanja se izbri&#353;e celoten nivo z vsemi &#353;ifranti. Lahko pa se omenejene elemente ureja in odstrani zgolj poljuben element nivoja.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hierarchy_edit_elements', 2, 'New items can be added for each selected level. Selecting the delete option deletes the entire level with all code lists. However, limited elements can be edited and only any level element can be removed.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hierarhy_last_level_missing', 1, 'Na zadnjem nivoju manjka izbran element in elektronski naslov osebe, ki bo preko elektronske po&#353;te dobila kodo za re&#353;evanje ankete.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hierarhy_last_level_missing', 2, 'At the last level, the selected element and the e-mail address of the person who will receive the code for solving the survey via e-mail are missing.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_region_color', 1, 'Omogo&#269;a urejanje barve obmo&#269;ja, ko bo to izbrano.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_region_color', 2, 'Allows you to edit the color of the area when it is selected.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_tooltip', 1, 'Izberite mo&#382;nosti prikazovanja namigov z imeni obmo&#269;ij.\r\n\r\nPrika&#382;i ob mouseover: namig je viden, ko je kurzor mi&#353;ke nad obmo&#269;jem;\r\nSkrij: namig ni viden;\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_tooltip', 2, 'Select options for displaying hints with area names.\r\n\r\nShow next to mouseover: a hint is visible when the mouse cursor is over an area;\r\nHide: hint not visible;\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_tooltip_grid', 1, 'Izberite mo&#382;nosti prikazovanja namigov s kategorijami odgovorov.\r\n\r\nPrika&#382;i ob mouseover: kategorije odgovorov so vidne, ko je kurzor mi&#353;ke nad obmo&#269;jem;\r\nPrika&#382;i ob kliku  mi&#353;ke na obmo&#269;je: kategorije odgovorov so vidne, ko se klikne na obmo&#269;je;\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_tooltip_grid', 2, 'Choose options for displaying hints with answer categories.\r\n\r\nShow next to mouseover: answer categories are visible when the mouse cursor is over an area;\r\nShow when mouse clicks on an area: answer categories are visible when an area is clicked;\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_visibility', 1, 'Izberite tip osvetlitve oz. kako, so obmo&#269;ja vidna ali nevidna respondentom.\r\n\r\nSkrij: obmo&#269;je ni vidno;\r\nPrika&#382;i: obmo&#269;je je vidno;\r\nPrika&#382;i ob mouseover: obmo&#269;je je vidno, ko je kurzor mi&#353;ke nad obmo&#269;jem;\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_visibility', 2, 'Choose the type of lighting or how the areas are visible or invisible to the respondents.\r\n\r\nHide: the area is not visible;\r\nShow: the area is visible;\r\nShow next to mouseover: the area is visible when the mouse cursor is over the area;\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_visibility_color', 1, 'Omogo&#269;a urejanje barve osvetlitve obmo&#269;ja.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_hotspot_visibility_color', 2, 'Allows you to edit the color of the area lighting.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_if_operator', 1, '<ul>\r\n<li>\"AND\" = pogoj je izpolnjen le, &#269;e je zado&#353;&#269;eno &#269;isto vsem kriterijem pogoja</li>\r\n<li>\"AND NOT\" = pogoj je izpolnjen, &#269;e velja prvi kriterij ne pa tudi drugi kriterij</li>\r\n<li>\"OR\" = pogoj je izpolnjen, &#269;e velja kateri koli od kriterijev (torej zadostuje, da je izpolnjen le en kriterij)</li>\r\n<li>\"OR NOT\" = pogoj je izpolnjen, &#269;e je izpolnjen prvi kriterij ali, &#269;e ni izpolnjen drugi kriterij</li>\r\n</ul>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/uporaba-pogojev\" target=\"_blank\"> Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_if_operator', 2, '<ul>\r\n<li>\"AND\" = the condition is met only if all the criteria of the condition are met</li>\r\n<li>\"AND NOT\" = condition met if the first criterion applies but not the second criterion</li>\r\n<li>\"OR\" = condition met if any of the criteria are met (ie only one criterion is sufficient)</li>\r\n<li>\"OR NOT\" = condition is met if the first criterion is met or if the second criterion is not met</li>\r\n</ul>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/uporaba-pogojev\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_invitation_rename_profile', 1, 'Vsak vne&#353;en email se privzeto shrani v za&#269;asen seznam, katerega pa lahko preimenujete tudi druga&#269;e. Nove emaile pa lahko dodate tudi v obstoje&#269;e sezname.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_invitation_rename_profile', 2, 'Each entered email is by default stored in a temporary list, which you can rename otherwise. You can also add new emails to existing lists. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_activate_1', 1, 'Z izbiro \"email vabil z individualiziranim URL\" se avtomati&#269;no vklopi opcija \"Da\" za individualizirana vabila, za vnos kode pa \"Avtomatsko v URL\". Respondentom bo sistem 1KA lahko poslal email, v katerem bo individualiziran URL naslovom ankete. &#268;im bo respondent na URL kliknil, bo sistem 1KA sledil respondenta.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_activate_1', 2, 'Selecting \"email invitations with individualized URL\" automatically turns on the \"Yes\" option for individualized invitations and \"Automatically in URL\" for entering the code. Respondents will be able to send an email to 1KA in which the URL of the survey will be individualized. As soon as the respondent clicks on the URL, the 1KA system will follow the respondent.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_activate_2', 1, 'Z izbiro \"ro&#269;ni vnos individualizirane kode\" se avtomati&#269;no vklopi opcija \"Da\" za individulaizirana vabila ter opcija \"Ro&#269;ni vnos\" za vnos kode. Respondenti bodo prejeli enak URL, na za&#269;etku pa bodo morali ro&#269;no vnesti svojo individualno kodo. Vabilo s kodo se lahko respondentu po&#353;lje z emailom preko sistemom 1KA. Lahko pa se po&#353;lje  tudi eksterno (izven sistema 1KA): z dopisom preko po&#353;te, s SMS sporo&#269;ilom kako druga&#269;e; v takem primeru sistem 1KA zgolj zabele&#382;i kdo, kdaj in kako je poslal vabilo.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_activate_2', 2, 'Selecting \"manual entry of individualized code\" automatically activates the \"Yes\" option for individualized invitations and the \"Manual entry\" option for code entry. Respondents will receive the same URL, and will initially need to enter their individual code manually. An invitation with a code can be sent to the respondent by email via the 1KA system. However, it can also be sent externally (outside the 1KA system): by letter by mail, by SMS in some other way; in such a case, the 1KA system merely records who, when and how sent the invitation.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_activate_3', 1, 'Z izbiro \"uporabe splo&#353;nih vabil brez individulaizirane kode\" opcija \"email vabila z individualiziranim URL\" ostaja izklopljena (\"Ne\"). Sistem 1KA bo respondenom lahko poslal emaile, ki pa ne bo imeli individulaizranega URL oziroma individualizirane kode.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_activate_3', 2, 'By selecting \"use general invitations without an individualized code\", the option \"email invitations with an individualized URL\" remains disabled (\"No\"). The 1KA system will be able to send emails to respondents, but they will not have an individualized URL or individualized code.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_activate_4', 1, 'Z izbiro \"Email vabila z ro&#269;nim vnosom kode\" se vklopi opcija \"Da\" za individualizirana vabila, za vnos kode pa \"Ro&#269;ni vnos\". Respondentom bo sistem 1KA lahko poslal email, v katerem bo individualizirana koda, URL naslov ankete pa bo enoten. Ko bo respondent kliknil na URL kliknil, se bo prikazal zahtevek za vnos kode, ki jo je prejel po emailu.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_activate_4', 2, 'Selecting \"Email invitation with manual code entry\" activates the \"Yes\" option for individualized invitations and \"Manual entry\" for code entry. Respondents will be able to send 1KA an email containing individualized code and a uniform URL of the survey. When the respondent clicks on the click URL, a request to enter the code received by email will be displayed.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_archive_sent', 1, 'Klik na &#353;tevilo poslanih vabil prika&#382;e podroben pregled poslanih vabil');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_archive_sent', 2, 'Clicking on the number of sent invitations displays a detailed overview of the sent invitations');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_cnt_by_sending', 1, 'Tabela pove, koliko enotam email &#353;e ni bil poslan (0), koliko enot je dobilo email enkrat (1), koliko dvakrat (2), koliko trikrat (3) ...');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_cnt_by_sending', 2, 'The table shows how many units the email has not yet been sent (0), how many units have received the email once (1), how many twice (2), how many three times (3) ...');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_delay', 1, 'Pri po&#353;iljanju email vabil na ve&#269; naslovov je vklopljena zakasnitev, kar pomeni da med e-po&#353;tnim sporo&#269;ilom, poslanim enemu naslovniku, in e-po&#353;tnim sporo&#269;ilom, poslanim naslednjemu naslovniku, prete&#269;e najmanj 2 sekundi. Ta &#269;as lahko po potrebi spremenite (glede na zmogljivosti va&#353;ega stre&#382;nika). ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_delay', 2, 'Delays are enabled when sending email invitations to multiple addresses, which means that at least 2 seconds elapse between an email sent to one recipient and an email sent to the next recipient. You can change this time as needed (depending on the capacity of your server).');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_general_settings', 1, 'V spletnih anketah obi&#269;ajno zado&#353;&#269;ata dve splo&#353;ni emaili vabili vsem enotam (drugo vabilo je hkrati zahvala respondentom). \r\n\r\nV primeru manj&#353;ega &#353;tevila enot (npr. nekaj sto) uporabimo kar privzeti email sistem (npr. Gmail, Outlook...), &#269;e je enot ve&#269;, pa orodja za masovno po&#353;iljanje (npr. SqualoMail, MailChimp...).\r\n\r\nVe&#269;je &#353;tevilo vabil in sledenje respondentom je ve&#269;inoma nepotrebno, hkrati pa tudi zahtevno.\r\n\r\nEmail vabila lahko po&#353;iljamo tudi preko sistema 1KA, in to tako splo&#353;na kot individualizirana vabila s kodo in sledenjem. Sistem 1KA poleg tega podpira (dokumentira) tudi po&#353;iljanje vabil na preko po&#353;ta, SMS, ipd.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_general_settings', 2, 'In online surveys, two general email invitations to all units are usually sufficient (the second invitation is also a thank you to the respondents).\r\n\r\nIn the case of a smaller number of units (eg a few hundred) we use the default email system (eg Gmail, Outlook ...), and if there are more units, we use mass sending tools (eg SqualoMail, MailChimp ...).\r\n\r\nA large number of invitations and following the respondents is mostly unnecessary, but also demanding.\r\n\r\nEmail invitations can also be sent via the 1KA system, both general and individualized invitations with code and tracking. In addition, 1KA supports (documents) the sending of invitations by mail, SMS, etc. \"');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_message_title', 1, 'Sporo&#269;ilo, ki bo poslano po emailu. Vsebino sporo&#269;ila se lahko spreminja poljubno, vsako spreminjanje se shrani kot novo sporo&#269;ilo in do njega se lahko dostopa v levem oknu iz seznama sporo&#269;il.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_message_title', 2, 'Message to be sent by email. The content of the message can be changed at will, each change is saved as a new message and can be accessed in the left window of the message list.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_message_title_noEmail', 1, 'Sporo&#269;ilo, ki bo poslano po navadni po&#353;ti ali SMS-u, ga lahko v 1KA dokumentirate. Dokumentirate lahko ve&#269; verzij sporo&#269;il, do njih pa dostopate v levem stolpcu seznama sporo&#269;il.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_message_title_noEmail', 2, 'A message that will be sent by regular mail or SMS can be documented in 1KA. You can document multiple versions of messages and access them in the left column of the message list.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_no_code', 1, 'V anketo se lahko vstopa tudi brez vnosa kode. Posebej priporo&#269;ljivo, da se ozna&#269;i le avtor za testiranje vpra&#353;alnika.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_no_code', 2, 'You can also enter the survey without entering a code. It is especially recommended that only the author be identified for testing the questionnaire.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_recipiens_add_invalid_note', 1, '&#268;e &#382;elite dodati enote, ki nimajo emailov, naredite lo&#269;en seznam.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_recipiens_add_invalid_note', 2, 'Make a separate list to add units that do not have emails.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_sending_comment', 1, 'Zabele&#382;imo lahko kako posebnost ali zna&#269;ilnost (npr. preliminarno obvestilo, prvo vabilo, opomnik ipd). V primeru ro&#269;nega po&#353;iljanja je priporo&#269;ljivo navesti dejanski dan odpo&#353;iljanja (npr. preko po&#353;te), saj se lahko razlikuje od datuma priprave seznama.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_sending_comment', 2, 'We can record a special feature or characteristic (eg preliminary notice, first invitation, reminder, etc.). In the case of manual transmission, it is advisable to indicate the actual day of dispatch (eg by post), as it may differ from the date of preparation of the list.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_sending_double', 1, 'Odstranjujejo se podvojeni zapisi glede na email');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_sending_double', 2, 'Duplicate records based on email are removed');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_sending_type', 1, 'Vabila je mogo&#269;e po&#353;iljati tudi preko po&#353;te, SMS ali kako druga&#269;e izven 1KA sistema. \r\n\r\nUporaba individualizirane kode je mogo&#269;a pri obeh na&#269;inih po&#353;iljanja.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_inv_sending_type', 2, 'Invitations can also be sent by mail, SMS or otherwise outside the 1KA system.\r\n\r\nIndividualized code can be used for both transmission methods. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_item_nonresponse', 1, '<p>Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).</p>\r\n<p>Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) ).</p>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/neodgovor-spremenljivke\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_item_nonresponse', 2, '<p>Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).</p>\r\n<p>Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) )</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_izpolnjujejo', 1, 'Tu lahko nastavite omejitev izpolnjevanja ankete za razli&#269;ne tipe uporabnikov aplikacije 1KA - registrirane uporabnike, &#269;lane, managerje in administratorje. \r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/o-1ka/splosen-opis/nivoji-uporabnikov\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_izpolnjujejo', 2, 'Here you can set limitations for survey completion for different types of users - registered users, members, managers and administrators.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_izvozCSV_locitveni', 1, 'V csv format se bodo podatki zivozili z lo&#269;itvenim znakom \";\" ali \",\".');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_izvozCSV_locitveni', 2, 'In csv format, data will be driven with a delimiter \";\" or \",\".');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_izvozCSV_tekst', 1, 'V primeru, da je mo&#382;nost obkljukana se bodo izvozila tudi vpra&#353;anja in imena vpra&#353;anj.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_izvozCSV_tekst', 2, 'In case the option is checked, questions and variables names will also be exported.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_mail_mode', 1, '<p>Za uporabo lastnega stre&#382;nika morate vpisati SMTP nastavitve.</p>\r\n<p>Za podatke kontaktiraje administratorja va&#353;ega po&#353;tnega stre&#382;nika.</p>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/o-1ka/nacini-uporabe-storitve-1ka/lastna-namestitev\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_mail_mode', 2, '<p>To use your own server you must enter the SMTP settings.</p>\r\n<p>Contact the administrator of your web server for data.</p>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/about/uses-of-1ka-services/own-installation\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_menu_statistic', 1, 'Ve&#269; o osnovnih statisti&#269;nih analizah si poglejte v <a href=\"https://www.1ka.si/d/sl/pomoc/video/enostavna-anketa-7m-05s\" target=\"_blank\">video vodi&#269;u</a> in <a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/osnovne-analize-podatkov-0\" target=\"_blank\">priro&#269;niku</a>.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_menu_statistic', 2, 'For moreinformation on basic statistical analyses see the <a href=\" http://english.1ka.si/db/24/412/Manuals/Basic_data_analysis/?&cat=309&p1=226&p2=735&p3=867&p4=0&id=867&cat=309\" target=\"_blank\">manual.</a>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_missing_values', 1, '<p><b>Manjkajo&#269;e vrednosti respondenta</b> imajo v bazi podatkov negativne vrednosti in so iz analiz privzeto izlo&#269;ene. To pomeni, se v statisti&#269;nih analizah ne upo&#353;tevajo (razen, &#269;e sami nastavite druga&#269;e).</p>\r\n<p>Vrednosti odgovorov \"ne vem\", \"zavrnil\", \"neustrezno\", in \"ostalo\" se v bazo zapi&#353;ejo kot - 99, -98, -97 in -96.</p>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/statusi-enot-ustreznost-veljavnost-manjkajoce-vrednosti\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_missing_values', 2, '<p>Nonsubstantive and missing responses: Respondents\" missing values have negative values in the database and are by default excluded from the analysis. This means that they are not included in statistical analysis (unless you change the default settings).</p>\r\n<p>Response values \"do not know\", \"refused\", \"invalid\", and \"none of above\" are labeled as - 99, -98, -97 and -96 in the database.</p>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/status-of-units-relevance-validity-and-missing-values\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_mobilne_tabele', 1, '<h1>Prilagoditve za mobilno anketo</h1>  Spletno anketo, ustvarjeno z orodjem 1KA, lahko respondenti izpolnjujejo tudi preko mobilnega telefona. Zaradi manj&#353;ega zaslona so tako za optimalen prikaz ankete potrebne dolo&#269;ene prilagoditve. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/prilagoditve-za-mobilno-anketo\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_mobilne_tabele', 2, '<h1> Mobile Survey Adjustments </h1> Respondents can also complete the online survey created with the 1KA tool via mobile phone. Due to the smaller screen, certain adjustments are needed for the optimal display of the survey. <span class = \"qtip-more\"> <a href=\"https://www.1ka.si/d/en/help/manuals/mobile-survey-adjustments\" target=\"_blank\"> Read more</a> </span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_moje_ankete_setting', 1, 'Mo&#382;nost omogo&#269;a, da anketo prenesete med svoje ankete v knji&#382;nico. \r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/knjiznica\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_moje_ankete_setting', 2, 'This option allows you to save your survey in the \"My surveys\" category of the library.<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/user-guide/my-surveys/library\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_namig_setting', 1, 'Ob vklopu mo&#382;nosti se respondentu skozi anketo sproti pojavljajo opozorila, ki ste jih nastavili.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_namig_setting', 2, 'When you turn on this option, notifications will appear throughout the survey completion process.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nastavitve_API', 1, 'API je zbirka funkcij, ki omogo&#269;ajo, da uporabniki preko oddaljenega dostopa (torej ne preko spletnega vmesnika) izvajajo dolo&#269;ene operacije na 1KA. Kot je navedeno v navodilih, mora uporabnik najprej zgenerirati klju&#269; za uporabo API-ja, potem pa lahko z njim integrira funkcije v svoj program ali spletno stran. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/o-1ka/1ka-api\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nastavitve_API', 2, 'The API is a collection of features that allow users to perform certain operations on 1KA via remote access (i.e. not via a web interface). As stated in the instructions, the user must first generate a key to use the API, and then use it to integrate features into his program or website. <span class = \"qtip-more\"> <a href=\"https://www.1ka.si/d/en/about/1ka-api\" target=\"_blank\"> Read more</a> </span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nastavitve_enklik', 1, 'Pri izboru te opcije se vam bo v zavihku \"Moje ankete\" prikazal gumb \"Enklik kreiranje\", s klikom nanj pa se bo ustvarila anketa, ki se vam bo prikazala v urejevalnem pogledu.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nastavitve_enklik', 2, 'When you select this option, you will see an \"Oneclick Survey\" button in the \"My Surveys\" tab, and clicking on it will create a survey that will appear in the edit view.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nastavitve_jezik', 1, '<ol>Jezik je mo&#382;no prilagajati na treh nivojih:\r\n<li><strong>Osnovni jezik spletne strani za avtorja ankete:&nbsp;</strong>Nastavite lahko osnovni jezik aplikacije 1KA, izbirate pa lahko med sloven&scaron;&#269;ino in angle&scaron;&#269;ino.</li>\r\n<li><strong>Osnovni jezik ankete za respondente:&nbsp;</strong>Iz spustnega seznama izberete jezik za respondente.&nbsp;</li>\r\n<li><strong>Nastavitve dodatnih jezikov za respondente:&nbsp;</strong>Prevod osnovnega vpra&scaron;alnika v razli&#269;ne jezike.</li>\r\n</ol>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/urejanje/nastavitve/jezik\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nastavitve_jezik', 2, '<ol>The language can be customized on three levels:\r\n<li><strong>Base language for the author of the survey: & nbsp; </strong> You can set the base language of the 1KA application, and you can choose between Slovenian and English.</li>\r\n<li><strong>Respondent base language: & nbsp; </strong> Select a language for respondents from the drop-down list.</li>\r\n<li><strong>Additional language settings for respondents: & nbsp; </strong> Translation of the basic questionnaire into different languages. </li>\r\n</ol>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/user-guide/edit/settings/language\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nastavitve_zakleni', 1, 'Pri izboru te opcije, ankete po aktivaciji ne boste mogli urejati, saj se bo samodejno zaklenila, da med zbiranjem podatkov ne bi pri&#353;lo do ne&#382;elenih sprememb vpra&#353;alnika (Anketo lahko spet odklenete tako, da kliknete na ikono v obliki klju&#269;avnice poleg URL naslova ankete na vrhu zaslona).');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nastavitve_zakleni', 2, 'If you select this option, you will not be able to edit the survey after activation, as it will be locked automatically to prevent further changes to the questionnaire during data collection (You can unlock the survey again by clicking on the lock icon next to the survey URL at the top of the screen).');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nice_url', 1, 'V primeru, da boste povezavo (URL) do ankete po&#353;iljali preko pisnih dopisov, priporo&#269;amo uporabo mo&#382;nosti Lep URL. Namre&#269; namesto &#353;tevilk ankete izberete poljubno ime, kar omogo&#269;a respondentom la&#382;ji vpis URL-ja (npr.: www.1ka.si/imeankete). Bodite pozorni na velike in male &#269;rke, saj jih 1KA razlikuje. Ime ankete mora vsebovati minimalno 3 znake.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nice_url', 2, 'If you are planning to send the survey link (URL) via mail, we recommend using the custom URL option. Namely, instead of survey number you can choose any name you want, allowing respondents to facilitate the entry of the URL (eg .: www.1ka.si/surveyname). Pay attention to uppercase and lowercase letters as 1KA is case sensitive. Name of the survey must contain a minimum of 3 characters.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_novagrupa', 1, 'S klikom na <b>Nova stran</b> dodate v vpra&#353;alnik novo stran, ki se postavi pred zaklju&#269;ek in jo nato lahko poljubno uredite in premikate. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_novagrupa', 2, 'Clicking on <b> New Page </b> adds a new page to the questionnaire, which is placed before completion, and you can then edit and move it as you wish.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nova_shrani', 1, 'Iz spodnjega seznama lahko izberete v katero mapo iz seznama \"Moje ankete\", naj se anketa shrani.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_nova_shrani', 2, 'From the list below, you can choose which folder from the \"My Polls\" list to save the poll to.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_oblika_Ikone', 1, 'Sprememba barve in velikosti checkbox/radio button ikon za osebni ra&#269;unalnik.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_oblika_Ikone', 2, 'Change the color and size of checkbox / radio button icons for PC.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_oblika_slovarIKljucna', 1, ' Sprememba nastavitev klju&#269;nih besed pri slovarju. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/slovar-glossary-definicije\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_oblika_slovarIKljucna', 2, 'Change keyword settings in the glossary.<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/glossary\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_oblika_slovarSlovar', 1, 'Sprememba nastavitev za obla&#269;ek pri slovarju. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/slovar-glossary-definicije\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_oblika_slovarSlovar', 2, 'Change the balloon settings in the glossary pop-up. <span class = \"q tip-more\"> <a href=\"https://www.1ka.si/d/en/help/manuals/glossary\" target=\"_blank\"> Read more</a> </span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_obvescanje_odgovorZa', 1, 'V primeru ve&#269;ih prejemnikov obvestila se lahko dolo&#269;i osebo na katero je naslovljen odgovor na obvestilo.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_obvescanje_odgovorZa', 2, 'In the case of several recipients of the notification, the person to whom the reply to the notification is addressed may be identified.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_parapodatki', 1, 'Ob vklopu naprednih parapodatkov se lahko dostopa do informacij, kot so zaporedna &#353;tevilka zapisa, datum vnosa ankete, &#269;as vnosa do milisekunde natan&#269;no, katero napravo je uporabljal respondent itd. \r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/anketni-podatki-parapodatki-identifikatorji-sistemske-spremenljivke\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_parapodatki', 2, 'When activating advanced data, information can be accessed, such as the serial number of the record, the date of entry of the survey, the time of entry to the millisecond, exactly which device the respondent used, etc.\r\n\r\n<span class = \" qtip-more\"> <a href = \"https://www.1ka.si/d/en/help/manuals/survey-data-paradata-identifiers-and-system-variables\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_para_graph_link', 1, '<span class=\"qtip-more\"><a href=\"https://www.1ka.si/index.php?fl=2&lact=1&bid=477&parent=24\" target=\"_blank\">Preberi ve&#269;</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_para_neodgovori_link', 1, '<p>Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).</p>\r\n<p>Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) )</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_para_neodgovori_link', 2, '<p>The non-response of a variable means how many relevant answers we got given the total number of units that received a particular question. Or otherwise: statuses (-1) are excluded from all relevant units that received this question.</p>\r\n<p>Calculated according to the formula: (-1) * 100 / (valid) + (-1) + (-97) ) + (-98) + (-99))</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_podatki_urejanje_inline', 1, '<p>Vklju&#269;ili ste tudi neposredno urejanje v pregledovalniku.</p>\r\n<p>V kolikor &#382;elite vrednosti vpra&#353;anja izbirati iz rolete lahko to nastavite v urejanju kot napredno nastavitev vpra&#353;anja.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_podatki_urejanje_inline', 2, '<p>You enabled direct editing in the data viewer.</p>\r\n<p>If you want to select the values of the question from the blinds, you can set this in editing as an advanced setup of the question.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_popup_js', 1, '');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_privacy_setting', 1, 'Tukaj se vklopi prikaz politike zasebnosti na za&#269;etku ankete. Respondent lahko nadaljuje z anketo le, &#269;e se strinja s pogoji.<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-zasebnosti\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_privacy_setting', 2, 'Here you can turn on the privacy policy display at the beginning of the survey. Respondents can proceed with the survey only if they agree to the terms. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/about/terms-of-use/privacy-policy\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_recode_advanced_edit', 1, 'Napredno urejanje, kot je dodajanje, preimenovanje in brisanje kategorij je na voljo v urejanju vpra&#353;alnika.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_recode_advanced_edit', 2, 'Advanced editing such as adding, renaming and deleting categories is available in editing the questionnaire.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_recode_chart_advanced', 1, 'Osnovno rekodiranje je primerno, da se starost, katera je ve&#269;ja od 100 rekodira v -97 katero je neustrezno. Oziroma da se odgovori 9 - ne vem rekodirajo v neustrezno.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_recode_chart_advanced', 2, 'Basic recoding is appropriate so that an age greater than 100 is recoded to -97 which is inappropriate. That is, to answer 9 - I do not know recode in inappropriate.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_recode_h_actions', 1, '<ul>Funkcije rekodiranja so:\r\n<li>Dodaj - odpre okno za dodajanje rekodiranje za posamezno variablo</li>\r\n<li>Uredi - prika&#382;e okno za urejane rekodiranja posamezne variable</li>\r\n<li>Odstrani - odstrani oziroma v celoti izbri&#353;e rekodiranje posamezne variable</li>\r\n<li>Omogo&#269;eno - trenutno omogo&#269;i oziroma onemogo&#269;i rekodiranje posamezne variable</li>\r\n<li>Vidna - nastavi variablo vidno oziroma nevidno v vpra&#353;alniku</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_recode_h_actions', 2, '<ul>The recoding functions are:\r\n<li>Add If - opens a window to add decoding for each variable</li>\r\n<li>Edit - displays a window for edited decoding of each variable</li> \r\n<li>Remove - removes or completely deletes the decoding of an individual variable</li> \r\n<li>Enabled - currently enables or disables the decoding of an individual variable</li> \r\n<li>Visible - sets the variable visible or invisible in the questionnaire</li> \r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_reminder_tracking_quality', 1, '<p>Kakovostni indeks = 1 - ( &#8721;(&#353;tevilo spro&#382;enih opozoril/&#353;tevilo mo&#382;nih opozoril po vrsti opozorila) / &#353;tevilo respondentov )</p>\r\n<p>Haraldsen, G. (2005). Using Client Side Paradata as Process Quality Indicators in Web Surveys. Predstavljeno na delavnici ESF Workshop on Internet survey methodology, Dubrovnik, 26-28 September 2005.</p>\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/indeks-kakovosti-sledenje-opozorilom\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_reminder_tracking_quality', 2, 'Quality index = 1 - ( &#8721;(Activated errors/Possible errors) / Number of respondents ) </br> Haraldsen, G. (2005). Using Client Side Paradata as Process Quality Indicators in Web Surveys. Presented at the ESF Workshop on Internet survey methodology, Dubrovnik, 26-28 September 2005.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_return_finished', 1, 'Uporabniku, ki je &#382;e izpolnjeval ali &#382;e zaklju&#269;il z anketo, lahko z izbiro \"Mo&#382;nost naknadnega urejanja odgovorov\" omogo&#269;ite, da kasneje ureja svoje odgovore. Vendar to pomeni, da se bodo tudi podatki in analize naknadno spreminjale, zato velja dobro razmisliti, ali je taka mo&#382;nost primerna.\r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-ankete-0\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_return_finished', 2, 'Here you can specify whether a user who has completed a survey can subsequently edit his responses. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/survey-settings\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_show_progressbar', 1, 'Funkcija omogo&#269;a, da se pri izpolnjevanju ankete respondentu na vrhu strani prika&#382;e graf, ki ponazarja dele&#382; ankete, ki jo je respondent v tistem trenutku &#382;e izpolnil. Vklop je mo&#382;en le, &#269;e ima anketa ve&#269; strani.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_show_progressbar', 2, 'Opcija omogo&#269;a, da se pri izpolnjevanju ankete respondentu na vrhu strani prika&#382;e graf, ki ponazarja dele&#382; ankete, ki jo je respondent v tistem trenutku &#382;e izpolnil. Vklop je mo&#382;en samo, &#269;e ima anketa ve&#269; strani.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_sistemska_edit', 1, 'Sistemsko spremenljivko lahko uporabljamo (pokli&#269;emo) v email komunikaciji, torej pri obve&#353;&#269;anju in po&#353;iljanju email vabil. \r\n <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/anketni-podatki-parapodatki-identifikatorji-sistemske-spremenljivke\" target=\"_blank\"> Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_sistemska_edit', 2, 'We can use (call) system variables in email communication, i.e. communication and sending of email invitations. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/survey-data-paradata-identifiers-and-system-variables\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skala_edit', 1, '<p><b>Ordinalna skala:</b> Kategorije odgovorov je mogoce primerjati; racunamo lahko tudi povprecje. Npr. lestvice na skalah (strinjanje, zadovoljstvo,.)</p>\r\n<p><b>Nominalna skala:</b> Kategorij odgovorov ni mogoce primerjati niti ni mogoce racunati povprecij. Npr. spol, barva, regija, dr&#382;ava.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skala_edit', 2, '<p><b>Ordinal scale:</b> Response categories can be compared; you can also compute the average. Eg. Measurement scales (acceptance, satisfaction.)</p>\r\n<p><b>Nominal scale:</b> Response categories cannot be compared nor can we calculate averages. Eg. gender, color, region, or country.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skala_text_nom', 1, '<p><b>Ordinalna skala:</b> Kategorije odgovorov je mogoce primerjati; racunamo lahko tudi povprecje. Npr. lestvice na skalah (strinjanje, zadovoljstvo,.)</p>\r\n<p><b>Nominalna skala:</b> Kategorij odgovorov ni mogoce primerjati niti ni mogoce racunati povprecij. Npr. spol, barva, regija, dr&#382;ava.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skala_text_ord', 1, 'Ordinalna skala');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skala_text_ord', 2, '<p><b>Ordinal scale:</b> Response categories can be compared; you can also compute the average. Eg. Measurement scales (acceptance, satisfaction.)</p>\r\n<p><b>Nominal scale:</b> Response categories cannot be compared nor can we calculate averages. Eg. gender, color, region, or country.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Embed', 1, 'Za ankete, ki so vklju&#269;ene v drugo spletno stran.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Embed', 2, 'For surveys included in another website.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Embed2', 1, 'Za ankete, ki so vklju&#269;ene v drugo spletno stran (o&#382;ja razli&#269;ica).');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Embed2', 2, 'For surveys included in another website (this version).');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Fdv', 1, 'Samo za uporabnike, ki imajo dovoljenje s strani FDV.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Fdv', 2, 'Only for users licensed by FDV.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Slideshow', 1, 'Za prezentacijo.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Slideshow', 2, 'For the presentation.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Uni', 1, 'Samo za uporabnike, ki imajo dovoljenje s strani FDV.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skins_Uni', 2, 'Only for users licensed by FDV.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skupine', 1, '&#268;e &#382;elite analizirati posamezne skupine respondentov, ne &#382;elite pa jim postavljati vpra&#353;anja za identifikacijo, lahko naredite skupine &#353;e preden po&#353;ljete anketo v izpolnjevanje.\r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/ustvarjanje-skupin-respondentov\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_skupine', 2, 'If you wish to analyse individual respondent groups without asking for identification, you can simply create groups before you send out a survey. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/creating-respondent-groups\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_splosnenas_opozorilo', 1, '<h1>Opozorilo vpra&#353;anja</h1>\r\n<ul>Za vsa vpra&scaron;anja v anketi lahko nastavite:\r\n<li>Mehko opozorilo na vsa vpra&scaron;anja:respondentu se bo pojavilo opozorilo da ni odgovoril na vpra&scaron;anje, vendar pa mu za nadaljevanje ne bo potrebno odgovoriti.&nbsp;</li>\r\n<li>Trdo opozorilo na vsa vpra&scaron;anja: Respondentu se pojavi opozorilo, da ni odgovoril na vpra&scaron;anje. Za nadaljevanje mora obvezno odgovoriti na vsa vpra&scaron;anja.</li>\r\n<li>Odstranitev opozorila iz vseh vpra&scaron;anj:ODstranitev nastavljenega opozorila iz vpra&scaron;anj.</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_splosnenas_opozorilo', 2, '<h1>General question alert</h1>\r\n<ul>For all survey questions you can set:\r\n<li>Soft reminder for all questions: The respondent will be warned that he did not answer the question, but will not be required to answer. & nbsp;</li>\r\n<li>Strong reminder to all questions: The respondent is warned that he did not answer the question. He must answer all the questions in order to continue.</li>\r\n<li>Remove a remindes from all questions: Remove a set alert from a question.</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_spremenljivka_lock', 1, 'Zaklenjeno vpra&#353;anje lahko ureja samo avtor ankete.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_spremenljivka_lock', 2, 'Only the author of the survey can edit locked question. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_answer_state_title', 1, 'Stopnja odgovorov pove, kolik&#353;en odstotek vseh respondentov je pri navedenih kategorijah izpolnil anketo. \r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/stopnja-odgovorov\" target=\"_blank\">Preberi ve&#269;</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_answer_state_title', 2, 'The response rate tells you what percentage of all respondents filled out the survey according to the five categories. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/the-response-rate\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_info_title', 1, 'Tukaj so razvidne osnovne informacije o anketi (t.i. \"hitre informacije\"): ime ankete, &#353;tevilo enot in vpra&#353;anj, trajanje ankete, prvi in zadnji vnos, status aktivnosti ankete itd. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_info_title', 2, 'Here you can see the basic information about the survey (i.e. \"quick overview\"): survey name, number of units, survey timeline, the first and last entry, the activity status of the survey etc.\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_pages_state_title', 1, 'Tu se lahko spremlja potek ankete po straneh in se dobi vpogled v to, kako dale&#269; v anketi pridejo respondenti.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_pages_state_title', 2, 'Here you can see the responses by pages, where you can get an insight into how many respondents finished your survey. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_redirection_title', 1, 'Spisek preusmeritev prikazuje od kje prihajajo preusmeritve na anketo, oziroma koliko respondentov je kliknilo na anketo iz dolo&#269;ene spletne strani.  \r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/preusmeritve\" target=\"_blank\">Preberi ve&#269;</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_redirection_title', 2, 'The list of redirections shows from where the redirections to your survey come from. In other words: how many respondents clicked on your survey from a certain website. Category \"Direct click\" includes clicks from email invitations that have not been sent via 1KA, and all direct entries (type-in or cut-paste) of the survey URL. Surveys that are completed via the 1KA invitation can be viewed under 1KA \"Email - response\". <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/referrals\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_status_title', 1, 'Tukaj je razvidno, koliko respondentov je kliknilo na anketo, koliko je ustreznih oziroma neustreznih enot (respondentov, ki so na anketo kliknili, vendar je niso izpolnili) ter skupno &#353;tevilo respondentov.\r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/statusi-enot-ustreznost-veljavnost-manjkajoce-vrednosti\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_status_title', 2, 'Here you can see the number of respondents that clicked on the survey, the number of valid or invalid units (respondents that clicked on the survey, but did not fill out the survey) and the total number of respondents.<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/status-of-units-relevance-validity-and-missing-values\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_timeline_title', 1, '&#268;asovni potek pove, koliko respondentov je na dolo&#269;eno &#269;asovno obdobje kliknilo ali izpolnilo anketo. &#268;asovni potek se lahko preveri po mesecih, dnevih, urah itd.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistic_timeline_title', 2, 'You can check the survey timeline according to months, days, hours etc. The timeline tells you how many respondents clicked or filled our your survey in a specific time period (month, day or hour).');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistika', 1, 'Opcija ki se sicer redko uporablja, prikaze rezultate odgovora na naslednji strani.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_statistika', 2, 'This option is rarely used; it displays the results of a response on the next page.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_status_cas', 1, 'Dejanski povpre&#269;en &#269;as, ki so ga respondenti porabili za izpolnitev ankete.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_status_cas', 2, 'Actual average time spent by respondents completing the survey.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_status_cas0', 1, 'V primeru, da je mo&#382;nost obkljukana, dnevi, ki ne vsebujejo nobene enote, ne bodo prikazani.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_status_cas0', 2, 'If the option is checked, days that do not contain any units will not be displayed.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_status_koncni0', 1, 'V primeru, da je mo&#382;nost obkljukana, statusi, ki ne vsebujejo nobene enote, ne bodo prikazani.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_status_koncni0', 2, 'If the option is checked, statuses that do not contain any units will not be displayed.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_subsequent_answers', 1, 'V primeru, da je izbrana druga mo&#382;nost, uporabnik ne morenikoli naknadno urejati svojih odgovorov (npr. s klikom nazaj).\r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-ankete-0\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_subsequent_answers', 2, 'If the second option is selected, the user cannot edit their answers later (eg by clicking back).\r\n\r\n<span class = \" qtip-more\"> <a href=\"https://www.1ka.si/d/en/help/manuals/survey-settings\" target=\"_blank\">Read more</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_survey_type', 1, '<p>1KA upo&#353;teva, da enostavne ankete zahtevajo druga&#269;en vmesnik kot kompleksne.</p>\r\n<ul>1KA zato omogo&#269;a, da lahko vedno izberete optimalni vmesnik, pa&#269; glede na zahtevnost ankete, ki jo potrebujete: \r\n<li><b>GLASOVANJE:</b> anketa z enim samim vpra&#353;anjem, volitve, ipd., vendar z mo&#382;nostjo sprotnega prikaza rezultatov.</li>\r\n<li><b>FORMA</b> kratek enostranski vpra&#353;alnik, forma, registracija, obrazec, email lista, prijava na dogodek ipd.</li>\r\n<li><b>ANKETA S POGOJI IN BLOKI:</b> anketni vpra&#353;alnik potrebuje  preskoke, bloke, gnezdenje pogojev ipd.</li>\r\n</ul>\r\n<p>Med vmesniki lahko preklapljate tudi kasneje, razen seveda v primeru, ko bi prehod pomenil izbris dolo&#269;enih podatkov. Tako v primeru, ko imate pogoje, ni ve&#269; mogo&#269;e prehod na enostavnej&#353;e vmesnike. Podobno iz ve&#269;stranske ankete ni mogo&#269; prehod v formo, je pa mogo&#269; seveda prehod v anketo s pogoji.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_survey_type', 2, '<p>1KA notes that simple surveys require a different interface than complex ones.</p>\r\n<ul>1KA therefore allows you to always choose the optimal interface, depending on the complexity of the survey you need:\r\n<li><b>VOTING:</b> single-question survey, elections, etc., but with the possibility of displaying the results online.</li>\r\n<li><b>FORM:</b> short one-sided questionnaire, form, registration, form, email list, event registration, etc.</li>\r\n<li><b>CONDITION AND CONDITIONS SURVEY:</b> The survey questionnaire requires skips, blocks, nesting conditions, etc.</li>\r\n</ul>\r\n<p>You can also switch between interfaces later, unless, of course, the transition would mean deleting certain data. Thus, once you have the conditions, it is no longer possible to switch to simpler interfaces. Similarly, it is not possible to move from a multilateral survey to a form, but it is of course possible to move to a conditional survey.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_tabela_postopno', 1, 'Na&#269;in prikaza tabele, kjer se vpra&#353;anja iz tabele prikazujejo lo&#269;eno, ena za drugo. Ob odgovoru na vpra&#353;anje se prika&#382;e naslednje vpra&#353;anje iz tabele.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_tabela_postopno', 2, 'A way of displaying a table, where the questions from the table are displayed separately, one after the other. When answering the question, the following question from the table appears.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_telefon_anketarji', 1, 'Vklju&#269;ene so vse enote, ki jih je anketar poklical, tudi &#269;e se niso javile.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_telefon_anketarji', 2, 'All units called by the interviewer are included, even if they did not respond.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_telephone_help', 1, 'Izbrani modul nudi podporo pri telefonskem anketiranju. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/telefonska-anketa\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_telephone_help', 2, 'The selected module provides support for telephone surveys. <span class = \"qtip-more\"> <a href=\"https://www.1ka.si/d/en/help/manuals/telephone-survey\" target=\"_blank\">Read more</a> </span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_toolbox_add_advanced', 1, 'Dodaj tip vpra&#353;anja.\r\n\r\nS klikom na &#382;eljeni tip vpra&#353;anja se ta postavi za zadnje vpra&#353;anje v anketi.\r\n\r\nZ uporabo funkcije \"Drag&drop\" lahko zagrabite tip vpra&#353;anja in ga prestavite na &#382;eljeno mesto.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_toolbox_add_advanced', 2, 'Add the question types.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_ttest_interpretation', 1, 'Z uporabo T-testa lahko preverite domneve o statisti&#269;no zna&#269;ilnih razlikah. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/db/24/433/Prirocniki/Ttest/?&p1=226&p2=735&p3=0&p4=0&id=735\"target=\"_blank\"> Preberi ve&#269;</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_ttest_interpretation', 2, 'With T-test you can verify assumptions about statistically significant differences. For moreinformation about the interpretation of the T-test, see guide <ahref=\"http://english.1ka.si/index.php?fl=2&lact=1&bid=436&parent=24\" target=\"_blank\"> T-test. </a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_upload_limit', 1, '<h1>Omejitev nalaganja datoteke</h1>\r\n<ul>Dodatne omejitve za respondente pri nalaganju datoteke:\r\n<li>najve&#269;ja velikost posamezne datoteke, ki jo&nbsp;nalo&#382;i respondent je <strong>16 MB;</strong></li>\r\n<li>dovoljene vrste datotek so: <strong>\"jpeg\", \"jpg\", \"png\", \"gif\", \"pdf\", \"doc\", \"docx\", \"xls\", \"xlsx\";</strong></li>\r\n<li><strong>v posameznem vpra&scaron;anju</strong> so dovoljena najve&#269; <strong>4 vnosna polja</strong>, torej najve&#269; 4 datoteke, ki jih lahko respondent nalo&#382;i pri enem vpra&scaron;anju.</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_upload_limit', 2, '<h1>Upload restrictions</h1>\r\n<ul>Additional restrictions for respondents when uploading a file:\r\n<li>the maximum size of an individual file uploaded by the respondent is <strong>16 MB</strong>;</li>\r\n<li>allowed file types are: <strong>\"jpeg\", \"jpg\", \"png\", \"gif\", \"pdf\", \"doc\", \"docx\", \"xls\", \"xlsx\"</strong>;</li>\r\n<li>a maximum of <strong>4 input fields</strong> is allowed <strong>in an individual question</strong>, i.e. a maximum of 4 files that the respondent can upload for one question.</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_user_base_individual_invitaition_note', 1, 'Individualizirana vabila omogo&#269;ajo sledenje respondentom preko individualne kode oziroma gesla.\r\n\r\nURL ankete vklju&#269;uje individualizirano kodo, respondent pa mora zgolj klikniti na URL ali pa ro&#269;no vpisati podano generirano kodo.\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_user_base_individual_invitaition_note', 2, 'Individualized invitations enable tracking of respondents via individual code or password.\r\n\r\nThe survey URL includes individualized code, and the respondent only needs to click on the URL or manually enter the given generated code.\r\n');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_user_base_individual_invitaition_note2', 1, '<p>Z izbiro \"Ne\" je modul individualiziranih vabil izklopljen. Anketira se lahko vsak, ki vidi ali pozna URL naslov. Respondentov v takem primeru ne moreo slediti; ne vemo kdo je odgovoril in kdo ne.</p>\r\n<p>Sistem 1KA lahko kljub temu po&#353;lje (email) oziroma dokumentira (po&#353;ta, SMS, drugo) po&#353;iljanje splo&#353;nega ne-individualiziranega vabila, kjer vsi respondenti prejmejo enotni URL. To pomeni, da se zabele&#382;ilo, komu, kdaj in kako je bilo vabilo poslano, ne bo pa ozna&#269;eno, kdo je odgovoril in kdo ne.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_user_base_individual_invitaition_note2', 2, '<p>Selecting \"No\" disables the individualized invitation module. Anyone who sees or knows the URL can be interviewed. Respondents in such a case could not follow; we do not know who answered and who did not.</p>\r\n<p>1KA system can still send (email) or document (mail, SMS, other) the sending of a general non-individualized invitation, where all respondents receive a single URL. This means that it was recorded to whom, when and how the invitation was sent, but it will not be indicated who responded and who did not.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_user_from_cms', 1, 'Tu nastavite, ali naj se uporabnika sistema CMS (sistem za upravljanje vsebin) prepozna avtomatsko kot respondenta ali pa kot vna&#353;alca. \r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-ankete-0\" target=\"_blank\">Preberi ve&#269;</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_user_from_cms', 2, 'Recognize CMS user: here you can set if the CMS (content management system) user is automatically recognized as a respondent. You can also select \"No\". <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/survey-settings\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_vprasanje_max_marker_map', 1, '&#352;tevilo najve&#269; mo&#382;nih oddanih odgovorov/markerjev na zemljevidu');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_vprasanje_max_marker_map', 2, 'The number of possible responses / markers submitted on the map');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_vprasanje_tracking_setting', 1, 'Omogo&#269;a verzije na nivoju posameznih vpra&#353;anj in ne le na nivoju celotnega vpra&#353;alnika. Uporabljajo jo lahko le managerji in administratorji. \r\n\r\n<span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-ankete-0\" target=\"_blank\">Preberi ve&#269;</a></span> ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_vprasanje_tracking_setting', 2, 'Allows for versions at the level of individual questions and not only at the level of the entire questionnaire. Only administrators can use this option. <span class=\"qtip-more\"><a href=\"https://www.1ka.si/d/en/help/manuals/survey-settings\" target=\"_blank\">Read more</a></span>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_vprasanje_trak', 1, 'Na&#269;in prikaza v obliki ocenjevalne lestvice, kjer sta podana opisa za skrajni dve vrednosti, med njima pa je linearen nabor &#353;tevilk. Npr. 1 - zelo nezadovoljen, 2, 3, 4, 5 - zelo zadovoljen.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_vprasanje_trak', 2, 'The method of display in the form of an evaluation scale, where descriptions are given for the extreme two values, and between them is a linear set of numbers. Eg 1 - very dissatisfied, 2, 3, 4, 5 - very satisfied.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_vrednost_fastadd', 1, 'Hitro dodajanje kategorij je priporo&#269;ljivo uporabiti pri vpra&#353;anjih, kjer imamo ve&#269;je &#353;tevilo kategorij odgovorov. Kateogorije preprosto vnesemo tako, da  vsako kategorijo vnesemo v svojo vrstico. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_vrednost_fastadd', 2, 'Fast  add categories option is recommended to use on questions where we have a large number of categories, by simply entering each category into a new row. ');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_window_help', 1, 'Pri lastni in&#353;talaciji odsvetujemo urejanje oz. spreminjanje vsebine vpra&#353;aj&#269;kov, saj se bo z vsako posodobitvijo verzije vsebina prepisala. Za spremembo obvestite <a href=\"https://www.1ka.si//c/819/KONTAKT/\" target=\"_blank\">Center za pomo&#269;</a>.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('srv_window_help', 2, '');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('toolbox_advanced', 1, 'Pri anketi z ve&#269;jim &#353;tevilom vpra&#353;anj vam priporo&#269;amo, da anketo razdelite na vsebinsko smiselne bloke. \r\n\r\nBloke lahko po potrebi zapirate in razpirate ter si s tem omogo&#269;ite bolj&#353;i pregled nad anketo.');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('toolbox_advanced', 2, '<p>In a survey with a larger number of questions, we recommend that you divide the survey into meaningful blocks.</p><p>You can close and open the blocks as needed, giving you a better overview of the survey.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('usercode_required', 1, '<p><b>Avtomatsko</b>: koda se samodejno prenese v anketo iz URL povezave email vabila.</p>\r\n<p><b>Ro&#269;no</b>: uporabnik mora ro&#269;no vnesti kodo. Koda se generira in izvozi v zavihku \"Preglej\".</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('usercode_required', 2, '<p><b>Automatic</b>: The code is automatically transferred to the survey from the URL of the email invitation link.</p><p><b>Manual</b>: The user must enter the code manually. The code is generated and exported in the \"Browse\" tab.</p>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('usercode_skip', 1, '<ul>\r\n<li><b>Ne</b>: Za izpolnjevanje ankete mora respondent bodisi prejeti email vabilo, kjer klikne na povezavo za avtomatski prenos kode v anketo, bodisi mora respondent poznati kodo in jo ro&#269;no vnesti v anketo.</li>\r\n<li><b>Da</b>: Anketo lahko izpolnjujejo tudi uporabniki, ki niso prejeli kode.</li>\r\n<li><b>Samo avtor</b>: Poleg uporabnikov, ki imajo kodo, lahko anketo brez kode izpolnjujejo tudi avtorji ankete (predvsem v testne namene).</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('usercode_skip', 2, '<ul>\r\n<li><b>No</b>: To complete the survey, the respondent must either receive an email invitation where he / she clicks on the link to automatically transfer the code to the survey, or the respondent must know the code and enter it manually in the survey.</li>\r\n<li><b>Yes</b>: Users who did not receive the code can also take the survey.</li>\r\n<li><b>Author only</b>: In addition to users who have the code, they can the code-free survey is also completed by the authors of the survey (mainly for test purposes).</li>\r\n</ul>');
INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES('user_location_map', 2, 'The browser will try to determine the current location of the respondent. The respondent will first be asked by the browser for permission to share his location.');

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_dostop`
--

CREATE TABLE `srv_hierarhija_dostop` (
  `user_id` int(11) NOT NULL,
  `dostop` tinyint(4) DEFAULT 0,
  `ustanova` varchar(255) DEFAULT NULL,
  `aai_email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_koda`
--

CREATE TABLE `srv_hierarhija_koda` (
  `koda` varchar(10) NOT NULL,
  `anketa_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `vloga` enum('ucitelj','ucenec') NOT NULL,
  `srv_user_id` int(11) DEFAULT NULL,
  `user_id` int(15) NOT NULL,
  `hierarhija_struktura_id` int(15) NOT NULL,
  `datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_options`
--

CREATE TABLE `srv_hierarhija_options` (
  `id` int(11) NOT NULL,
  `anketa_id` int(11) NOT NULL,
  `option_name` varchar(200) NOT NULL,
  `option_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_ravni`
--

CREATE TABLE `srv_hierarhija_ravni` (
  `id` int(11) NOT NULL,
  `anketa_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `level` tinyint(4) DEFAULT NULL,
  `ime` varchar(255) DEFAULT NULL,
  `unikaten` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_shrani`
--

CREATE TABLE `srv_hierarhija_shrani` (
  `id` int(11) NOT NULL,
  `anketa_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ime` varchar(255) DEFAULT NULL,
  `hierarhija` longtext DEFAULT NULL,
  `struktura` longtext DEFAULT NULL,
  `st_uciteljev` int(11) DEFAULT NULL,
  `st_vseh_uporabnikov` int(11) DEFAULT NULL,
  `komentar` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `uporabniki_list` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_sifranti`
--

CREATE TABLE `srv_hierarhija_sifranti` (
  `id` int(11) NOT NULL,
  `hierarhija_ravni_id` int(11) NOT NULL,
  `ime` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_sifrant_vrednost`
--

CREATE TABLE `srv_hierarhija_sifrant_vrednost` (
  `sifrant_id` int(11) NOT NULL,
  `vrednost_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_struktura`
--

CREATE TABLE `srv_hierarhija_struktura` (
  `id` int(11) NOT NULL,
  `anketa_id` int(11) NOT NULL,
  `hierarhija_ravni_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `hierarhija_sifranti_id` int(11) NOT NULL,
  `level` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_struktura_users`
--

CREATE TABLE `srv_hierarhija_struktura_users` (
  `hierarhija_struktura_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_supersifra`
--

CREATE TABLE `srv_hierarhija_supersifra` (
  `koda` varchar(10) NOT NULL,
  `anketa_id` int(11) NOT NULL,
  `kode` text NOT NULL,
  `datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_supersifra_resevanje`
--

CREATE TABLE `srv_hierarhija_supersifra_resevanje` (
  `user_id` int(11) NOT NULL,
  `supersifra` varchar(10) NOT NULL,
  `koda` varchar(10) NOT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `datetime` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_users`
--

CREATE TABLE `srv_hierarhija_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `anketa_id` int(11) NOT NULL,
  `type` tinyint(4) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hotspot_regions`
--

CREATE TABLE `srv_hotspot_regions` (
  `id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `region_name` text NOT NULL,
  `region_coords` text NOT NULL,
  `region_index` int(11) NOT NULL,
  `variable` varchar(15) NOT NULL DEFAULT '',
  `vrstni_red` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_if`
--

CREATE TABLE `srv_if` (
  `id` int(11) NOT NULL,
  `number` int(11) NOT NULL DEFAULT 0,
  `tip` tinyint(4) NOT NULL DEFAULT 0,
  `label` varchar(200) CHARACTER SET utf8 DEFAULT NULL,
  `collapsed` tinyint(4) NOT NULL DEFAULT 0,
  `folder` int(11) NOT NULL DEFAULT 0,
  `enabled` enum('0','1','2') NOT NULL,
  `tab` enum('0','1') NOT NULL,
  `horizontal` enum('0','1','2') NOT NULL DEFAULT '0',
  `random` int(11) NOT NULL DEFAULT -1,
  `thread` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_if`
--

INSERT INTO `srv_if` (`id`, `number`, `tip`, `label`, `collapsed`, `folder`, `enabled`, `tab`, `horizontal`, `random`, `thread`) VALUES(0, 0, 0, 'system', 0, 0, '0', '0', '0', -1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `srv_invitations_archive`
--

CREATE TABLE `srv_invitations_archive` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `date_send` datetime DEFAULT NULL,
  `subject_text` varchar(100) NOT NULL,
  `body_text` mediumtext DEFAULT NULL,
  `tip` tinyint(4) NOT NULL DEFAULT -1,
  `cnt_succsess` int(11) NOT NULL,
  `cnt_error` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `comment` char(100) NOT NULL,
  `naslov` char(100) NOT NULL,
  `rec_in_db` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_invitations_archive_recipients`
--

CREATE TABLE `srv_invitations_archive_recipients` (
  `arch_id` int(11) NOT NULL,
  `rec_id` int(11) NOT NULL,
  `success` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_invitations_mapping`
--

CREATE TABLE `srv_invitations_mapping` (
  `sid` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `field` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_invitations_messages`
--

CREATE TABLE `srv_invitations_messages` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `naslov` varchar(100) NOT NULL,
  `subject_text` varchar(100) NOT NULL,
  `body_text` mediumtext DEFAULT NULL,
  `reply_to` varchar(100) NOT NULL,
  `isdefault` enum('0','1') DEFAULT '0',
  `uid` int(11) NOT NULL,
  `insert_time` datetime NOT NULL,
  `comment` char(100) NOT NULL,
  `edit_uid` int(11) NOT NULL,
  `edit_time` datetime NOT NULL,
  `url` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_invitations_recipients`
--

CREATE TABLE `srv_invitations_recipients` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `firstname` varchar(45) DEFAULT NULL,
  `lastname` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  `cookie` char(32) NOT NULL,
  `salutation` varchar(45) DEFAULT NULL,
  `phone` varchar(45) DEFAULT NULL,
  `custom` varchar(100) DEFAULT NULL,
  `relation` int(11) DEFAULT NULL,
  `sent` enum('0','1') DEFAULT '1',
  `responded` enum('0','1') DEFAULT '1',
  `unsubscribed` enum('0','1') DEFAULT '1',
  `deleted` enum('0','1') DEFAULT '0',
  `date_inserted` datetime NOT NULL,
  `date_sent` datetime NOT NULL,
  `date_expired` datetime NOT NULL,
  `date_responded` datetime NOT NULL,
  `date_unsubscribed` datetime NOT NULL,
  `date_deleted` datetime NOT NULL,
  `uid_deleted` int(11) NOT NULL,
  `last_status` tinyint(4) NOT NULL DEFAULT 0,
  `inserted_uid` int(11) NOT NULL DEFAULT 0,
  `list_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_invitations_recipients_profiles`
--

CREATE TABLE `srv_invitations_recipients_profiles` (
  `pid` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `uid` int(11) NOT NULL,
  `fields` varchar(100) DEFAULT NULL,
  `respondents` text NOT NULL,
  `insert_time` datetime NOT NULL,
  `comment` char(100) NOT NULL,
  `from_survey` int(11) NOT NULL,
  `edit_uid` int(11) NOT NULL,
  `edit_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_invitations_recipients_profiles_access`
--

CREATE TABLE `srv_invitations_recipients_profiles_access` (
  `pid` int(11) NOT NULL,
  `uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_invitations_tracking`
--

CREATE TABLE `srv_invitations_tracking` (
  `inv_arch_id` int(11) NOT NULL,
  `time_insert` datetime DEFAULT NULL,
  `res_id` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `uniq` mediumint(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_language`
--

CREATE TABLE `srv_language` (
  `ank_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `language` varchar(255) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_language_grid`
--

CREATE TABLE `srv_language_grid` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `naslov` text CHARACTER SET utf8 NOT NULL,
  `podnaslov` text CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_language_slider`
--

CREATE TABLE `srv_language_slider` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `label` text CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_language_spremenljivka`
--

CREATE TABLE `srv_language_spremenljivka` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `naslov` text CHARACTER SET utf8 NOT NULL,
  `info` text CHARACTER SET utf8 NOT NULL,
  `vsota` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_language_vrednost`
--

CREATE TABLE `srv_language_vrednost` (
  `ank_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `naslov` text CHARACTER SET utf8 NOT NULL,
  `naslov2` text CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_library_anketa`
--

CREATE TABLE `srv_library_anketa` (
  `ank_id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `folder` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_library_folder`
--

CREATE TABLE `srv_library_folder` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT 0,
  `tip` tinyint(4) NOT NULL DEFAULT 0,
  `naslov` varchar(50) NOT NULL,
  `parent` int(11) NOT NULL,
  `lang` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_library_folder`
--

INSERT INTO `srv_library_folder` (`id`, `uid`, `tip`, `naslov`, `parent`, `lang`) VALUES(1, 0, 0, 'Sistemske', 0, 1);
INSERT INTO `srv_library_folder` (`id`, `uid`, `tip`, `naslov`, `parent`, `lang`) VALUES(2, 0, 1, 'Public surveys', 0, 2);
INSERT INTO `srv_library_folder` (`id`, `uid`, `tip`, `naslov`, `parent`, `lang`) VALUES(3, 0, 0, 'Public questions', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `srv_lock`
--

CREATE TABLE `srv_lock` (
  `lock_key` varchar(32) NOT NULL,
  `locked` enum('0','1') NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `last_lock_date` datetime NOT NULL,
  `last_unlock_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_loop`
--

CREATE TABLE `srv_loop` (
  `if_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `max` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_loop_data`
--

CREATE TABLE `srv_loop_data` (
  `id` int(11) NOT NULL,
  `if_id` int(11) NOT NULL,
  `vre_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_loop_vre`
--

CREATE TABLE `srv_loop_vre` (
  `if_id` int(11) NOT NULL,
  `vre_id` int(11) DEFAULT NULL,
  `tip` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_mc_element`
--

CREATE TABLE `srv_mc_element` (
  `id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL DEFAULT 0,
  `spr` varchar(255) NOT NULL DEFAULT '',
  `parent` varchar(255) NOT NULL DEFAULT '',
  `vrstni_red` int(11) NOT NULL DEFAULT 0,
  `position` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_mc_table`
--

CREATE TABLE `srv_mc_table` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `time_created` datetime NOT NULL,
  `title` text NOT NULL,
  `numerus` enum('0','1') NOT NULL DEFAULT '1',
  `percent` enum('0','1') NOT NULL DEFAULT '0',
  `sums` enum('0','1') NOT NULL DEFAULT '0',
  `navVsEno` enum('0','1') NOT NULL DEFAULT '1',
  `avgVar` varchar(255) NOT NULL DEFAULT '',
  `delezVar` varchar(255) NOT NULL DEFAULT '',
  `delez` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_misc`
--

CREATE TABLE `srv_misc` (
  `what` varchar(255) NOT NULL DEFAULT '',
  `value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `srv_misc`
--

INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_data_font_size', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_data_numbering', '0');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_data_PB', '1');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_data_show_if', '0');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_data_show_recnum', '0');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_data_skip_empty', '1');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_data_skip_empty_sub', '1');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_font_size', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_numbering', '1');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_show_if', '1');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('export_show_intro', '1');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('mobile_friendly', '1');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('mobile_tables', '1');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('question_comment_text', 'Va&#154; komentar k vpra&#154;anju');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_1', '5');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_16', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_17', '5');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_18', '5');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_19', '20');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_2', '5');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_20', '20');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_3', '5');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_6', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_kategorija_max_3', '20');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_stran', '5');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_1', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_16', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_17', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_18', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_19', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_2', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_20', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_21', '20');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_3', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_4', '20');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_5', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_6', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_7', '10');
INSERT INTO `srv_misc` (`what`, `value`) VALUES('timing_vprasanje_8', '10');

-- --------------------------------------------------------

--
-- Table structure for table `srv_missing_profiles`
--

CREATE TABLE `srv_missing_profiles` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT 0,
  `display_mv_type` tinyint(4) NOT NULL DEFAULT 0,
  `merge_missing` tinyint(1) NOT NULL DEFAULT 0,
  `show_zerro` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_missing_profiles_values`
--

CREATE TABLE `srv_missing_profiles_values` (
  `missing_pid` int(11) NOT NULL,
  `missing_value` int(11) NOT NULL,
  `type` int(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_missing_values`
--

CREATE TABLE `srv_missing_values` (
  `sid` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 3,
  `value` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '-99',
  `text` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'Ne vem',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `systemValue` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_module`
--

CREATE TABLE `srv_module` (
  `id` int(11) NOT NULL,
  `module_name` text NOT NULL,
  `active` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `srv_module`
--

INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(1, 'hierarhija', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(2, 'evalvacija', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(3, '360', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(4, 'evoli', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(5, 'gfksurvey', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(6, 'mfdps', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(7, '360_1ka', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(8, 'mju', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(9, 'evoli_teammeter', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(10, 'maza', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(11, 'excell_matrix', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(12, 'gorenje', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(13, 'borza', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(14, 'advanced_paradata', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(15, 'wpn', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(16, 'evoli_employmeter', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(17, 'evoli_quality_climate', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(18, 'evoli_teamship_meter', '0');
INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES(19, 'evoli_organizational_employeeship_meter', '0');

-- --------------------------------------------------------

--
-- Table structure for table `srv_mysurvey_anketa`
--

CREATE TABLE `srv_mysurvey_anketa` (
  `ank_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `folder` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_mysurvey_folder`
--

CREATE TABLE `srv_mysurvey_folder` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `parent` int(11) NOT NULL DEFAULT 0,
  `naslov` varchar(50) NOT NULL DEFAULT '',
  `open` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_nice_links`
--

CREATE TABLE `srv_nice_links` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `link` varbinary(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_nice_links_skupine`
--

CREATE TABLE `srv_nice_links_skupine` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `nice_link_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `link` varbinary(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_notifications`
--

CREATE TABLE `srv_notifications` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `recipient` int(11) NOT NULL DEFAULT 0,
  `viewed` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_notifications_messages`
--

CREATE TABLE `srv_notifications_messages` (
  `id` int(11) NOT NULL,
  `author` int(11) NOT NULL DEFAULT 0,
  `date` date NOT NULL DEFAULT '0000-00-00',
  `title` varchar(50) NOT NULL DEFAULT '',
  `text` mediumtext NOT NULL,
  `force_show` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_panel_if`
--

CREATE TABLE `srv_panel_if` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `if_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_panel_settings`
--

CREATE TABLE `srv_panel_settings` (
  `ank_id` int(11) NOT NULL,
  `user_id_name` varchar(100) NOT NULL DEFAULT 'SID',
  `status_name` varchar(100) NOT NULL DEFAULT 'status',
  `status_default` varchar(20) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_parapodatki`
--

CREATE TABLE `srv_parapodatki` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `what` varchar(150) NOT NULL,
  `what2` varchar(150) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `gru_id` varchar(50) NOT NULL,
  `spr_id` varchar(50) NOT NULL,
  `item` text NOT NULL,
  `spr_id_variable` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_password`
--

CREATE TABLE `srv_password` (
  `ank_id` int(11) NOT NULL,
  `password` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_profile_manager`
--

CREATE TABLE `srv_profile_manager` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `name` text NOT NULL,
  `comment` text NOT NULL,
  `ssp` int(11) DEFAULT NULL,
  `svp` int(11) DEFAULT NULL,
  `scp` int(11) DEFAULT NULL,
  `stp` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_quiz_settings`
--

CREATE TABLE `srv_quiz_settings` (
  `ank_id` int(11) NOT NULL,
  `results` enum('0','1') NOT NULL DEFAULT '1',
  `results_chart` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_quiz_vrednost`
--

CREATE TABLE `srv_quiz_vrednost` (
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `vre_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_quota`
--

CREATE TABLE `srv_quota` (
  `id` int(11) NOT NULL,
  `cnd_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `operator` smallint(6) NOT NULL,
  `value` int(11) NOT NULL DEFAULT 0,
  `left_bracket` smallint(6) NOT NULL,
  `right_bracket` smallint(6) NOT NULL,
  `vrstni_red` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_recode`
--

CREATE TABLE `srv_recode` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `vrstni_red` int(11) NOT NULL,
  `search` varchar(15) COLLATE utf8_bin NOT NULL,
  `value` varchar(15) COLLATE utf8_bin NOT NULL,
  `operator` enum('0','1','2','3','4','5','6') COLLATE utf8_bin DEFAULT '0',
  `enabled` enum('0','1') COLLATE utf8_bin NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `srv_recode_number`
--

CREATE TABLE `srv_recode_number` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `vrstni_red` int(11) NOT NULL,
  `search` varchar(15) COLLATE utf8_bin NOT NULL,
  `operator` enum('0','1','2','3','4','5','6') COLLATE utf8_bin DEFAULT '0',
  `vred_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `srv_recode_spremenljivka`
--

CREATE TABLE `srv_recode_spremenljivka` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `recode_type` tinyint(1) NOT NULL DEFAULT 0,
  `to_spr_id` int(11) NOT NULL DEFAULT 0,
  `enabled` enum('0','1') NOT NULL DEFAULT '1',
  `usr_id` int(11) NOT NULL,
  `rec_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_recode_vrednost`
--

CREATE TABLE `srv_recode_vrednost` (
  `ank_id` int(11) NOT NULL,
  `spr1` int(11) NOT NULL,
  `vre1` int(11) NOT NULL,
  `spr2` int(11) NOT NULL,
  `vre2` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_respondents`
--

CREATE TABLE `srv_respondents` (
  `pid` int(11) NOT NULL,
  `line` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_respondent_profiles`
--

CREATE TABLE `srv_respondent_profiles` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `variables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_simple_mail_invitation`
--

CREATE TABLE `srv_simple_mail_invitation` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `email` varchar(250) NOT NULL,
  `send_time` datetime NOT NULL,
  `state` enum('ok','error','quota_exceeded') DEFAULT NULL,
  `usr_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_slideshow_settings`
--

CREATE TABLE `srv_slideshow_settings` (
  `ank_id` int(11) NOT NULL,
  `fixed_interval` tinyint(1) NOT NULL DEFAULT 0,
  `timer` mediumint(9) NOT NULL DEFAULT 5,
  `save_entries` tinyint(4) NOT NULL DEFAULT 0,
  `autostart` tinyint(4) NOT NULL DEFAULT 0,
  `next_btn` tinyint(4) NOT NULL DEFAULT 1,
  `back_btn` tinyint(4) NOT NULL DEFAULT 1,
  `pause_btn` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_specialdata_vrednost`
--

CREATE TABLE `srv_specialdata_vrednost` (
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `vre_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_spremenljivka`
--

CREATE TABLE `srv_spremenljivka` (
  `id` int(11) NOT NULL,
  `gru_id` int(11) NOT NULL DEFAULT 0,
  `naslov` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `info` text CHARACTER SET utf8 DEFAULT NULL,
  `variable` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `variable_custom` tinyint(1) NOT NULL DEFAULT 0,
  `label` varchar(200) CHARACTER SET utf8 DEFAULT NULL,
  `tip` tinyint(4) NOT NULL DEFAULT 0,
  `vrstni_red` int(11) NOT NULL DEFAULT 0,
  `random` tinyint(4) NOT NULL DEFAULT 0,
  `size` tinyint(4) NOT NULL DEFAULT 5,
  `undecided` tinyint(4) NOT NULL DEFAULT 0,
  `rejected` tinyint(4) NOT NULL DEFAULT 0,
  `inappropriate` tinyint(4) NOT NULL DEFAULT 0,
  `stat` int(11) NOT NULL DEFAULT 0,
  `orientation` tinyint(1) NOT NULL DEFAULT 1,
  `checkboxhide` tinyint(1) NOT NULL DEFAULT 0,
  `reminder` tinyint(4) NOT NULL DEFAULT 0,
  `alert_show_99` enum('0','1') NOT NULL DEFAULT '0',
  `alert_show_98` enum('0','1') NOT NULL DEFAULT '0',
  `alert_show_97` enum('0','1') NOT NULL DEFAULT '0',
  `visible` tinyint(4) NOT NULL DEFAULT 1,
  `locked` enum('0','1') NOT NULL DEFAULT '0',
  `textfield` tinyint(4) NOT NULL DEFAULT 0,
  `textfield_label` varchar(250) NOT NULL,
  `cela` tinyint(4) NOT NULL DEFAULT 4,
  `decimalna` tinyint(4) NOT NULL DEFAULT 0,
  `enota` tinyint(1) NOT NULL DEFAULT 0,
  `timer` mediumint(9) NOT NULL DEFAULT 0,
  `sistem` tinyint(4) NOT NULL DEFAULT 0,
  `folder` int(11) NOT NULL DEFAULT 1,
  `params` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `antonucci` tinyint(1) NOT NULL DEFAULT 0,
  `design` tinyint(1) NOT NULL DEFAULT 0,
  `podpora` tinyint(1) NOT NULL DEFAULT 0,
  `grids` tinyint(4) NOT NULL DEFAULT 5,
  `grids_edit` tinyint(4) NOT NULL DEFAULT 0,
  `grid_subtitle1` text CHARACTER SET utf8 DEFAULT NULL,
  `grid_subtitle2` text CHARACTER SET utf8 DEFAULT NULL,
  `ranking_k` tinyint(4) NOT NULL DEFAULT 0,
  `vsota` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `vsota_limit` int(11) NOT NULL DEFAULT 0,
  `vsota_min` int(11) NOT NULL DEFAULT 0,
  `skala` tinyint(4) NOT NULL DEFAULT -1,
  `vsota_reminder` tinyint(4) NOT NULL DEFAULT 0,
  `vsota_limittype` tinyint(4) NOT NULL DEFAULT 0,
  `vsota_show` tinyint(4) NOT NULL DEFAULT 1,
  `num_useMax` enum('0','1') NOT NULL DEFAULT '0',
  `num_useMin` enum('0','1') NOT NULL DEFAULT '0',
  `num_min2` int(11) NOT NULL DEFAULT 0,
  `num_max2` int(11) NOT NULL DEFAULT 0,
  `num_useMax2` enum('0','1') NOT NULL DEFAULT '0',
  `num_useMin2` enum('0','1') NOT NULL DEFAULT '0',
  `thread` int(11) NOT NULL DEFAULT 0,
  `text_kosov` tinyint(4) NOT NULL DEFAULT 1,
  `text_orientation` tinyint(4) NOT NULL DEFAULT 0,
  `note` text CHARACTER SET utf8 DEFAULT NULL,
  `upload` tinyint(4) NOT NULL DEFAULT 0,
  `signature` tinyint(4) NOT NULL DEFAULT 0,
  `dostop` tinyint(4) NOT NULL DEFAULT 4,
  `inline_edit` tinyint(4) NOT NULL DEFAULT 0,
  `onchange_submit` tinyint(4) NOT NULL DEFAULT 0,
  `hidden_default` tinyint(4) NOT NULL DEFAULT 0,
  `naslov_graf` varchar(255) NOT NULL DEFAULT '',
  `edit_graf` tinyint(4) NOT NULL DEFAULT 0,
  `wide_graf` tinyint(4) NOT NULL DEFAULT 1,
  `coding` int(11) NOT NULL DEFAULT 0,
  `dynamic_mg` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0',
  `showOnAllPages` enum('0','1') NOT NULL DEFAULT '0',
  `skupine` enum('0','1','2','3') NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_spremenljivka`
--

INSERT INTO `srv_spremenljivka` (`id`, `gru_id`, `naslov`, `info`, `variable`, `variable_custom`, `label`, `tip`, `vrstni_red`, `random`, `size`, `undecided`, `rejected`, `inappropriate`, `stat`, `orientation`, `checkboxhide`, `reminder`, `alert_show_99`, `alert_show_98`, `alert_show_97`, `visible`, `locked`, `textfield`, `textfield_label`, `cela`, `decimalna`, `enota`, `timer`, `sistem`, `folder`, `params`, `antonucci`, `design`, `podpora`, `grids`, `grids_edit`, `grid_subtitle1`, `grid_subtitle2`, `ranking_k`, `vsota`, `vsota_limit`, `vsota_min`, `skala`, `vsota_reminder`, `vsota_limittype`, `vsota_show`, `num_useMax`, `num_useMin`, `num_min2`, `num_max2`, `num_useMax2`, `num_useMin2`, `thread`, `text_kosov`, `text_orientation`, `note`, `upload`, `signature`, `dostop`, `inline_edit`, `onchange_submit`, `hidden_default`, `naslov_graf`, `edit_graf`, `wide_graf`, `coding`, `dynamic_mg`, `showOnAllPages`, `skupine`, `timestamp`) VALUES(-4, 0, 'system', '', '', 0, '', 0, 0, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, '', 0, 1, 0, '0', '0', '0', '2018-03-05 09:50:11');
INSERT INTO `srv_spremenljivka` (`id`, `gru_id`, `naslov`, `info`, `variable`, `variable_custom`, `label`, `tip`, `vrstni_red`, `random`, `size`, `undecided`, `rejected`, `inappropriate`, `stat`, `orientation`, `checkboxhide`, `reminder`, `alert_show_99`, `alert_show_98`, `alert_show_97`, `visible`, `locked`, `textfield`, `textfield_label`, `cela`, `decimalna`, `enota`, `timer`, `sistem`, `folder`, `params`, `antonucci`, `design`, `podpora`, `grids`, `grids_edit`, `grid_subtitle1`, `grid_subtitle2`, `ranking_k`, `vsota`, `vsota_limit`, `vsota_min`, `skala`, `vsota_reminder`, `vsota_limittype`, `vsota_show`, `num_useMax`, `num_useMin`, `num_min2`, `num_max2`, `num_useMax2`, `num_useMin2`, `thread`, `text_kosov`, `text_orientation`, `note`, `upload`, `signature`, `dostop`, `inline_edit`, `onchange_submit`, `hidden_default`, `naslov_graf`, `edit_graf`, `wide_graf`, `coding`, `dynamic_mg`, `showOnAllPages`, `skupine`, `timestamp`) VALUES(-3, 0, 'system', '', '', 0, '', 0, 0, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, '', 0, 1, 0, '0', '0', '0', '2018-03-05 09:50:11');
INSERT INTO `srv_spremenljivka` (`id`, `gru_id`, `naslov`, `info`, `variable`, `variable_custom`, `label`, `tip`, `vrstni_red`, `random`, `size`, `undecided`, `rejected`, `inappropriate`, `stat`, `orientation`, `checkboxhide`, `reminder`, `alert_show_99`, `alert_show_98`, `alert_show_97`, `visible`, `locked`, `textfield`, `textfield_label`, `cela`, `decimalna`, `enota`, `timer`, `sistem`, `folder`, `params`, `antonucci`, `design`, `podpora`, `grids`, `grids_edit`, `grid_subtitle1`, `grid_subtitle2`, `ranking_k`, `vsota`, `vsota_limit`, `vsota_min`, `skala`, `vsota_reminder`, `vsota_limittype`, `vsota_show`, `num_useMax`, `num_useMin`, `num_min2`, `num_max2`, `num_useMax2`, `num_useMin2`, `thread`, `text_kosov`, `text_orientation`, `note`, `upload`, `signature`, `dostop`, `inline_edit`, `onchange_submit`, `hidden_default`, `naslov_graf`, `edit_graf`, `wide_graf`, `coding`, `dynamic_mg`, `showOnAllPages`, `skupine`, `timestamp`) VALUES(-2, 0, 'system', '', '', 0, '', 0, 1, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, 'system', 0, 0, 0, '0', '0', '0', '2018-03-05 09:50:11');
INSERT INTO `srv_spremenljivka` (`id`, `gru_id`, `naslov`, `info`, `variable`, `variable_custom`, `label`, `tip`, `vrstni_red`, `random`, `size`, `undecided`, `rejected`, `inappropriate`, `stat`, `orientation`, `checkboxhide`, `reminder`, `alert_show_99`, `alert_show_98`, `alert_show_97`, `visible`, `locked`, `textfield`, `textfield_label`, `cela`, `decimalna`, `enota`, `timer`, `sistem`, `folder`, `params`, `antonucci`, `design`, `podpora`, `grids`, `grids_edit`, `grid_subtitle1`, `grid_subtitle2`, `ranking_k`, `vsota`, `vsota_limit`, `vsota_min`, `skala`, `vsota_reminder`, `vsota_limittype`, `vsota_show`, `num_useMax`, `num_useMin`, `num_min2`, `num_max2`, `num_useMax2`, `num_useMin2`, `thread`, `text_kosov`, `text_orientation`, `note`, `upload`, `signature`, `dostop`, `inline_edit`, `onchange_submit`, `hidden_default`, `naslov_graf`, `edit_graf`, `wide_graf`, `coding`, `dynamic_mg`, `showOnAllPages`, `skupine`, `timestamp`) VALUES(-1, 0, 'system', '', '', 0, '', 0, 2, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, 'system', 0, 0, 0, '0', '0', '0', '2018-03-05 09:50:11');
INSERT INTO `srv_spremenljivka` (`id`, `gru_id`, `naslov`, `info`, `variable`, `variable_custom`, `label`, `tip`, `vrstni_red`, `random`, `size`, `undecided`, `rejected`, `inappropriate`, `stat`, `orientation`, `checkboxhide`, `reminder`, `alert_show_99`, `alert_show_98`, `alert_show_97`, `visible`, `locked`, `textfield`, `textfield_label`, `cela`, `decimalna`, `enota`, `timer`, `sistem`, `folder`, `params`, `antonucci`, `design`, `podpora`, `grids`, `grids_edit`, `grid_subtitle1`, `grid_subtitle2`, `ranking_k`, `vsota`, `vsota_limit`, `vsota_min`, `skala`, `vsota_reminder`, `vsota_limittype`, `vsota_show`, `num_useMax`, `num_useMin`, `num_min2`, `num_max2`, `num_useMax2`, `num_useMin2`, `thread`, `text_kosov`, `text_orientation`, `note`, `upload`, `signature`, `dostop`, `inline_edit`, `onchange_submit`, `hidden_default`, `naslov_graf`, `edit_graf`, `wide_graf`, `coding`, `dynamic_mg`, `showOnAllPages`, `skupine`, `timestamp`) VALUES(0, 0, 'system', '', '', 0, '', 0, 3, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, 'system', 0, 0, 0, '0', '0', '0', '2018-03-05 09:50:11');

-- --------------------------------------------------------

--
-- Table structure for table `srv_spremenljivka_tracking`
--

CREATE TABLE `srv_spremenljivka_tracking` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `tracking_id` int(11) NOT NULL,
  `tracking_uid` int(11) NOT NULL,
  `tracking_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_squalo_anketa`
--

CREATE TABLE `srv_squalo_anketa` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `active` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_statistic_profile`
--

CREATE TABLE `srv_statistic_profile` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `starts` datetime DEFAULT NULL,
  `ends` datetime DEFAULT NULL,
  `interval_txt` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_status_casi`
--

CREATE TABLE `srv_status_casi` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT 0,
  `statusnull` tinyint(1) NOT NULL DEFAULT 0,
  `status0` tinyint(1) NOT NULL DEFAULT 0,
  `status1` tinyint(1) NOT NULL DEFAULT 0,
  `status2` tinyint(1) NOT NULL DEFAULT 0,
  `status3` tinyint(1) NOT NULL DEFAULT 0,
  `status4` tinyint(1) NOT NULL DEFAULT 0,
  `status5` tinyint(1) NOT NULL DEFAULT 0,
  `status6` tinyint(1) NOT NULL DEFAULT 0,
  `statuslurker` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `srv_status_casi`
--

INSERT INTO `srv_status_casi` (`id`, `uid`, `name`, `system`, `statusnull`, `status0`, `status1`, `status2`, `status3`, `status4`, `status5`, `status6`, `statuslurker`) VALUES(1, 0, 'Koncal anketo', 1, 0, 0, 0, 0, 0, 0, 0, 1, 0);
INSERT INTO `srv_status_casi` (`id`, `uid`, `name`, `system`, `statusnull`, `status0`, `status1`, `status2`, `status3`, `status4`, `status5`, `status6`, `statuslurker`) VALUES(2, 0, 'Vsi statusi', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `srv_status_profile`
--

CREATE TABLE `srv_status_profile` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT 0,
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT 0,
  `statusnull` tinyint(1) NOT NULL DEFAULT 0,
  `status0` tinyint(1) NOT NULL DEFAULT 0,
  `status1` tinyint(1) NOT NULL DEFAULT 0,
  `status2` tinyint(1) NOT NULL DEFAULT 0,
  `status3` tinyint(1) NOT NULL DEFAULT 0,
  `status4` tinyint(1) NOT NULL DEFAULT 0,
  `status5` tinyint(1) NOT NULL DEFAULT 0,
  `status6` tinyint(1) NOT NULL DEFAULT 0,
  `statuslurker` tinyint(1) NOT NULL DEFAULT 0,
  `statustestni` tinyint(1) NOT NULL DEFAULT 2,
  `statusnonusable` tinyint(1) NOT NULL DEFAULT 1,
  `statuspartusable` tinyint(1) NOT NULL DEFAULT 1,
  `statususable` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `srv_status_profile`
--

INSERT INTO `srv_status_profile` (`id`, `uid`, `ank_id`, `name`, `system`, `statusnull`, `status0`, `status1`, `status2`, `status3`, `status4`, `status5`, `status6`, `statuslurker`, `statustestni`, `statusnonusable`, `statuspartusable`, `statususable`) VALUES(1, 0, 0, 'Vsi statusi', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1);
INSERT INTO `srv_status_profile` (`id`, `uid`, `ank_id`, `name`, `system`, `statusnull`, `status0`, `status1`, `status2`, `status3`, `status4`, `status5`, `status6`, `statuslurker`, `statustestni`, `statusnonusable`, `statuspartusable`, `statususable`) VALUES(2, 0, 0, 'Ustrezni', 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 2, 1, 1, 1);
INSERT INTO `srv_status_profile` (`id`, `uid`, `ank_id`, `name`, `system`, `statusnull`, `status0`, `status1`, `status2`, `status3`, `status4`, `status5`, `status6`, `statuslurker`, `statustestni`, `statusnonusable`, `statuspartusable`, `statususable`) VALUES(3, 0, 0, 'Koncani', 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 2, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `srv_survey_conditions`
--

CREATE TABLE `srv_survey_conditions` (
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `if_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_survey_list`
--

CREATE TABLE `srv_survey_list` (
  `id` int(11) NOT NULL,
  `lib_glb` enum('0','1') NOT NULL DEFAULT '0',
  `lib_usr` enum('0','1') NOT NULL,
  `answers` int(11) NOT NULL DEFAULT 0,
  `variables` int(11) NOT NULL DEFAULT 0,
  `approp` int(11) NOT NULL DEFAULT 0,
  `i_name` varchar(255) NOT NULL DEFAULT '',
  `i_surname` varchar(255) NOT NULL DEFAULT '',
  `i_email` varchar(255) NOT NULL DEFAULT '',
  `e_name` varchar(255) NOT NULL DEFAULT '',
  `e_surname` varchar(255) NOT NULL DEFAULT '',
  `e_email` varchar(255) NOT NULL DEFAULT '',
  `a_first` datetime DEFAULT NULL,
  `a_last` datetime DEFAULT NULL,
  `updated` enum('0','1') NOT NULL DEFAULT '0',
  `last_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_survey_misc`
--

CREATE TABLE `srv_survey_misc` (
  `sid` int(11) NOT NULL,
  `what` varchar(255) NOT NULL DEFAULT '',
  `value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_survey_session`
--

CREATE TABLE `srv_survey_session` (
  `ank_id` int(11) NOT NULL,
  `what` varchar(255) NOT NULL,
  `value` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_survey_unsubscribe`
--

CREATE TABLE `srv_survey_unsubscribe` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `unsubscribe_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_survey_unsubscribe_codes`
--

CREATE TABLE `srv_survey_unsubscribe_codes` (
  `ank_id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `code` char(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_sys_filters`
--

CREATE TABLE `srv_sys_filters` (
  `id` int(11) NOT NULL,
  `fid` varchar(100) COLLATE utf8_bin NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `filter` varchar(100) COLLATE utf8_bin NOT NULL,
  `text` varchar(200) COLLATE utf8_bin NOT NULL,
  `uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `srv_sys_filters`
--

INSERT INTO `srv_sys_filters` (`id`, `fid`, `type`, `filter`, `text`, `uid`) VALUES(1, '-1', 1, '-1', 'Ni odgovoril', 0);
INSERT INTO `srv_sys_filters` (`id`, `fid`, `type`, `filter`, `text`, `uid`) VALUES(2, '-2', 1, '-2', 'Preskok (if)', 0);
INSERT INTO `srv_sys_filters` (`id`, `fid`, `type`, `filter`, `text`, `uid`) VALUES(3, '-3', 1, '-3', 'Prekinjeno', 0);
INSERT INTO `srv_sys_filters` (`id`, `fid`, `type`, `filter`, `text`, `uid`) VALUES(4, '-4', 1, '-4', 'Naknadno vprasanje', 0);
INSERT INTO `srv_sys_filters` (`id`, `fid`, `type`, `filter`, `text`, `uid`) VALUES(5, '99', 2, '-99', 'Ne vem', 0);
INSERT INTO `srv_sys_filters` (`id`, `fid`, `type`, `filter`, `text`, `uid`) VALUES(6, '98', 2, '-98', 'Zavrnil', 0);
INSERT INTO `srv_sys_filters` (`id`, `fid`, `type`, `filter`, `text`, `uid`) VALUES(7, '97', 2, '-97', 'Neustrezno', 0);
INSERT INTO `srv_sys_filters` (`id`, `fid`, `type`, `filter`, `text`, `uid`) VALUES(8, '-5', 1, '-5', 'Prazna enota', 0);

-- --------------------------------------------------------

--
-- Table structure for table `srv_telephone_comment`
--

CREATE TABLE `srv_telephone_comment` (
  `rec_id` int(11) UNSIGNED NOT NULL,
  `comment_time` datetime NOT NULL,
  `comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_telephone_current`
--

CREATE TABLE `srv_telephone_current` (
  `rec_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `started_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_telephone_history`
--

CREATE TABLE `srv_telephone_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `survey_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `rec_id` int(10) UNSIGNED NOT NULL,
  `insert_time` datetime NOT NULL,
  `status` enum('A','Z','N','R','T','P','U','D') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_telephone_schedule`
--

CREATE TABLE `srv_telephone_schedule` (
  `rec_id` int(10) UNSIGNED NOT NULL,
  `call_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_telephone_setting`
--

CREATE TABLE `srv_telephone_setting` (
  `survey_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status_z` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status_n` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status_d` int(10) NOT NULL DEFAULT 0,
  `max_calls` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `call_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_testdata_archive`
--

CREATE TABLE `srv_testdata_archive` (
  `ank_id` int(11) NOT NULL,
  `add_date` datetime DEFAULT NULL,
  `add_uid` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_theme_editor`
--

CREATE TABLE `srv_theme_editor` (
  `profile_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_theme_editor_mobile`
--

CREATE TABLE `srv_theme_editor_mobile` (
  `profile_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_theme_profiles`
--

CREATE TABLE `srv_theme_profiles` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `skin` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_theme_profiles_mobile`
--

CREATE TABLE `srv_theme_profiles_mobile` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `skin` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_time_profile`
--

CREATE TABLE `srv_time_profile` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `starts` datetime DEFAULT NULL,
  `ends` datetime DEFAULT NULL,
  `interval_txt` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_tracking_active`
--

CREATE TABLE `srv_tracking_active` (
  `ank_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `ip` varchar(16) NOT NULL,
  `user` int(11) NOT NULL,
  `get` text NOT NULL,
  `post` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `time_seconds` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_tracking_api`
--

CREATE TABLE `srv_tracking_api` (
  `ank_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `ip` varchar(16) NOT NULL,
  `user` int(20) NOT NULL,
  `action` text NOT NULL,
  `kategorija` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_tracking_archive1`
--

CREATE TABLE `srv_tracking_archive1` (
  `ank_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `ip` varchar(16) NOT NULL,
  `user` int(11) NOT NULL,
  `get` text NOT NULL,
  `post` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `time_seconds` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_tracking_archive2`
--

CREATE TABLE `srv_tracking_archive2` (
  `ank_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `ip` varchar(16) NOT NULL,
  `user` int(11) NOT NULL,
  `get` text NOT NULL,
  `post` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `time_seconds` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user`
--

CREATE TABLE `srv_user` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `preview` tinyint(4) NOT NULL DEFAULT 0,
  `testdata` tinyint(4) NOT NULL DEFAULT 0,
  `email` varchar(100) NOT NULL,
  `cookie` char(32) NOT NULL,
  `pass` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(20) NOT NULL,
  `time_insert` datetime NOT NULL,
  `time_edit` datetime NOT NULL,
  `recnum` int(11) NOT NULL DEFAULT 0,
  `javascript` tinyint(4) NOT NULL,
  `useragent` varchar(250) NOT NULL,
  `device` enum('0','1','2','3') NOT NULL DEFAULT '0',
  `browser` varchar(250) NOT NULL,
  `os` varchar(250) NOT NULL,
  `referer` varchar(250) NOT NULL,
  `last_status` tinyint(4) NOT NULL DEFAULT -1,
  `lurker` tinyint(4) NOT NULL DEFAULT 1,
  `unsubscribed` tinyint(4) NOT NULL DEFAULT 0,
  `inv_res_id` int(11) DEFAULT NULL,
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  `language` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_userbase`
--

CREATE TABLE `srv_userbase` (
  `usr_id` int(11) NOT NULL,
  `tip` tinyint(4) NOT NULL,
  `datetime` datetime NOT NULL,
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_userbase_invitations`
--

CREATE TABLE `srv_userbase_invitations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `text` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `srv_userbase_invitations`
--

INSERT INTO `srv_userbase_invitations` (`id`, `name`, `subject`, `text`) VALUES(1, 'Privzet text', 'Spletna anketa', '<p>Prosimo, &#269;e si vzamete nekaj minut in izpolnite spodnjo anketo.</p><p>Hvala.</p><p>#URL#</p>');

-- --------------------------------------------------------

--
-- Table structure for table `srv_userbase_respondents`
--

CREATE TABLE `srv_userbase_respondents` (
  `id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `line` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_userbase_respondents_lists`
--

CREATE TABLE `srv_userbase_respondents_lists` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `variables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_userbase_setting`
--

CREATE TABLE `srv_userbase_setting` (
  `ank_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `text` text NOT NULL,
  `replyto` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_userstatus`
--

CREATE TABLE `srv_userstatus` (
  `usr_id` int(11) NOT NULL,
  `tip` tinyint(4) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_grupa_active`
--

CREATE TABLE `srv_user_grupa_active` (
  `gru_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `time_edit` datetime NOT NULL,
  `preskocena` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_grupa_archive1`
--

CREATE TABLE `srv_user_grupa_archive1` (
  `gru_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `time_edit` datetime NOT NULL,
  `preskocena` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_grupa_archive2`
--

CREATE TABLE `srv_user_grupa_archive2` (
  `gru_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `time_edit` datetime NOT NULL,
  `preskocena` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_session`
--

CREATE TABLE `srv_user_session` (
  `ank_id` int(11) NOT NULL DEFAULT 0,
  `usr_id` int(11) NOT NULL DEFAULT 0,
  `data` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_setting`
--

CREATE TABLE `srv_user_setting` (
  `usr_id` int(11) NOT NULL,
  `survey_list_order` varchar(255) NOT NULL DEFAULT '',
  `survey_list_order_by` varchar(20) NOT NULL DEFAULT '',
  `survey_list_rows_per_page` int(11) NOT NULL DEFAULT 25,
  `survey_list_visible` varchar(255) NOT NULL DEFAULT '',
  `survey_list_widths` varchar(255) NOT NULL DEFAULT '',
  `icons_always_on` tinyint(1) NOT NULL DEFAULT 0,
  `full_screen_edit` tinyint(1) NOT NULL DEFAULT 0,
  `showAnalizaPreview` enum('0','1') NOT NULL DEFAULT '1',
  `lockSurvey` enum('0','1') NOT NULL DEFAULT '1',
  `autoActiveSurvey` enum('0','1') NOT NULL DEFAULT '0',
  `advancedMySurveys` enum('0','1') NOT NULL DEFAULT '0',
  `showIntro` enum('0','1') NOT NULL DEFAULT '1',
  `showConcl` enum('0','1') NOT NULL DEFAULT '1',
  `showSurveyTitle` enum('0','1') NOT NULL DEFAULT '1',
  `oneclickCreateMySurveys` enum('0','1') NOT NULL DEFAULT '0',
  `survey_list_folders` enum('0','1') NOT NULL DEFAULT '0',
  `manage_domain` varchar(50) NOT NULL DEFAULT '',
  `activeComments` enum('0','1') NOT NULL DEFAULT '0',
  `showLanguageShortcut` enum('0','1') NOT NULL DEFAULT '0',
  `showSAicon` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_setting_for_survey`
--

CREATE TABLE `srv_user_setting_for_survey` (
  `sid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `what` varchar(200) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_setting_misc`
--

CREATE TABLE `srv_user_setting_misc` (
  `uid` int(11) NOT NULL,
  `what` varchar(255) NOT NULL DEFAULT '',
  `value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_validation`
--

CREATE TABLE `srv_validation` (
  `spr_id` int(11) NOT NULL,
  `if_id` int(11) NOT NULL,
  `reminder` enum('0','1','2') NOT NULL,
  `reminder_text` varchar(255) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_variable_profiles`
--

CREATE TABLE `srv_variable_profiles` (
  `id` int(11) NOT NULL,
  `sid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `variables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_vrednost`
--

CREATE TABLE `srv_vrednost` (
  `id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT 0,
  `naslov` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `naslov2` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `variable` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `variable_custom` tinyint(1) NOT NULL DEFAULT 0,
  `vrstni_red` int(11) NOT NULL DEFAULT 0,
  `random` tinyint(1) NOT NULL DEFAULT 0,
  `other` int(11) NOT NULL DEFAULT 0,
  `if_id` int(11) NOT NULL DEFAULT 0,
  `size` int(11) NOT NULL DEFAULT 0,
  `naslov_graf` varchar(255) NOT NULL DEFAULT '',
  `hidden` enum('0','1','2') NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_vrednost`
--

INSERT INTO `srv_vrednost` (`id`, `spr_id`, `naslov`, `naslov2`, `variable`, `variable_custom`, `vrstni_red`, `random`, `other`, `if_id`, `size`, `naslov_graf`, `hidden`, `timestamp`) VALUES(-4, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12');
INSERT INTO `srv_vrednost` (`id`, `spr_id`, `naslov`, `naslov2`, `variable`, `variable_custom`, `vrstni_red`, `random`, `other`, `if_id`, `size`, `naslov_graf`, `hidden`, `timestamp`) VALUES(-3, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12');
INSERT INTO `srv_vrednost` (`id`, `spr_id`, `naslov`, `naslov2`, `variable`, `variable_custom`, `vrstni_red`, `random`, `other`, `if_id`, `size`, `naslov_graf`, `hidden`, `timestamp`) VALUES(-2, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12');
INSERT INTO `srv_vrednost` (`id`, `spr_id`, `naslov`, `naslov2`, `variable`, `variable_custom`, `vrstni_red`, `random`, `other`, `if_id`, `size`, `naslov_graf`, `hidden`, `timestamp`) VALUES(-1, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12');
INSERT INTO `srv_vrednost` (`id`, `spr_id`, `naslov`, `naslov2`, `variable`, `variable_custom`, `vrstni_red`, `random`, `other`, `if_id`, `size`, `naslov_graf`, `hidden`, `timestamp`) VALUES(0, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12');

-- --------------------------------------------------------

--
-- Table structure for table `srv_vrednost_map`
--

CREATE TABLE `srv_vrednost_map` (
  `id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `overlay_id` int(11) DEFAULT 0,
  `overlay_type` varchar(25) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `lat` float(19,15) NOT NULL,
  `lng` float(19,15) NOT NULL,
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `vrstni_red` int(11) NOT NULL DEFAULT 0,
  `timestamp` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_zanka_profiles`
--

CREATE TABLE `srv_zanka_profiles` (
  `id` int(11) NOT NULL,
  `sid` int(11) NOT NULL DEFAULT 0,
  `uid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(200) NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT 0,
  `variables` text NOT NULL,
  `mnozenje` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_zoom_profiles`
--

CREATE TABLE `srv_zoom_profiles` (
  `id` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `vars` text NOT NULL,
  `conditions` text NOT NULL,
  `if_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 3,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `approved` tinyint(1) NOT NULL DEFAULT 1,
  `gdpr_agree` tinyint(1) NOT NULL DEFAULT -1,
  `email` varchar(255) NOT NULL,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'Nepodpisani',
  `surname` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `pass` varchar(255) DEFAULT NULL,
  `came_from` tinyint(1) NOT NULL DEFAULT 0,
  `when_reg` date NOT NULL DEFAULT '2003-01-01',
  `show_email` tinyint(1) NOT NULL DEFAULT 1,
  `lost_password` varchar(255) NOT NULL DEFAULT '',
  `lost_password_code` varchar(255) NOT NULL DEFAULT '',
  `lang` int(11) NOT NULL DEFAULT 1,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:01',
  `LastLP` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `manuallyApproved` enum('Y','N') NOT NULL DEFAULT 'N',
  `eduroam` enum('1','0') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `type`, `status`, `approved`, `gdpr_agree`, `email`, `name`, `surname`, `pass`, `came_from`, `when_reg`, `show_email`, `lost_password`, `lost_password_code`, `lang`, `last_login`, `LastLP`, `manuallyApproved`, `eduroam`) VALUES(1045, 0, 1, 1, -1, 'admin', 'admin', 'admin', '', 1, '2010-10-28', 1, '', '', 1, '2022-03-21 10:15:17', 0, 'N', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_to_be`
--

CREATE TABLE `users_to_be` (
  `id` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 3,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `approved` tinyint(1) NOT NULL DEFAULT 1,
  `gdpr_agree` tinyint(1) NOT NULL DEFAULT -1,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'Nepodpisani',
  `surname` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `pass` varchar(255) DEFAULT NULL,
  `came_from` tinyint(1) NOT NULL DEFAULT 0,
  `when_reg` date NOT NULL DEFAULT '2003-01-01',
  `show_email` tinyint(1) NOT NULL DEFAULT 1,
  `user_groups` int(11) NOT NULL,
  `timecode` varchar(15) NOT NULL DEFAULT '',
  `code` varchar(255) NOT NULL DEFAULT '',
  `lang` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_access`
--

CREATE TABLE `user_access` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `time_activate` datetime(3) NOT NULL,
  `time_expire` datetime(3) NOT NULL,
  `package_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_access_anketa`
--

CREATE TABLE `user_access_anketa` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `time_activate` datetime(3) NOT NULL,
  `time_expire` datetime(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_access_narocilo`
--

CREATE TABLE `user_access_narocilo` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `status` int(3) NOT NULL DEFAULT 0,
  `time` datetime(3) NOT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'sl',
  `package_id` int(11) NOT NULL,
  `trajanje` int(11) NOT NULL DEFAULT 1,
  `payment_method` int(3) NOT NULL DEFAULT 0,
  `discount` decimal(7,2) NOT NULL DEFAULT 0.00,
  `cebelica_id_predracun` int(11) NOT NULL DEFAULT 0,
  `cebelica_id_racun` int(11) NOT NULL DEFAULT 0,
  `phone` varchar(30) NOT NULL DEFAULT '',
  `podjetje_ime` varchar(255) NOT NULL DEFAULT '',
  `podjetje_naslov` varchar(255) NOT NULL DEFAULT '',
  `podjetje_postna` varchar(20) NOT NULL DEFAULT '',
  `podjetje_posta` varchar(100) NOT NULL DEFAULT '',
  `podjetje_drzava` varchar(255) NOT NULL DEFAULT 'Slovenija',
  `podjetje_davcna` varchar(20) NOT NULL DEFAULT '',
  `podjetje_no_ddv` enum('0','1') NOT NULL DEFAULT '0',
  `podjetje_eracun` enum('0','1') NOT NULL DEFAULT '0',
  `ime` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_access_paket`
--

CREATE TABLE `user_access_paket` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `price` decimal(7,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_access_paket`
--

INSERT INTO `user_access_paket` (`id`, `name`, `description`, `price`) VALUES(1, '1ka', '', '0.00');
INSERT INTO `user_access_paket` (`id`, `name`, `description`, `price`) VALUES(2, '2ka', '', '13.90');
INSERT INTO `user_access_paket` (`id`, `name`, `description`, `price`) VALUES(3, '3ka', '', '19.90');

-- --------------------------------------------------------

--
-- Table structure for table `user_access_paypal_transaction`
--

CREATE TABLE `user_access_paypal_transaction` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(100) NOT NULL DEFAULT '',
  `narocilo_id` int(11) NOT NULL DEFAULT 0,
  `price` decimal(7,2) NOT NULL DEFAULT 0.00,
  `currency_type` varchar(100) NOT NULL DEFAULT '',
  `time` datetime(3) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_access_placilo`
--

CREATE TABLE `user_access_placilo` (
  `id` int(11) NOT NULL,
  `narocilo_id` int(11) NOT NULL DEFAULT 0,
  `note` varchar(255) NOT NULL DEFAULT '',
  `time` datetime(3) NOT NULL,
  `price` decimal(7,2) NOT NULL DEFAULT 0.00,
  `payment_method` int(3) NOT NULL DEFAULT 0,
  `canceled` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_access_stripe_charge`
--

CREATE TABLE `user_access_stripe_charge` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) NOT NULL DEFAULT '',
  `narocilo_id` int(11) NOT NULL DEFAULT 0,
  `description` varchar(255) NOT NULL DEFAULT '',
  `price` decimal(7,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(7,2) NOT NULL DEFAULT 0.00,
  `status` varchar(100) NOT NULL DEFAULT '',
  `balance_transaction` varchar(255) NOT NULL DEFAULT '',
  `time` datetime(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_cronjob`
--

CREATE TABLE `user_cronjob` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `phase` varchar(100) NOT NULL DEFAULT '',
  `phase_time` datetime(3) NOT NULL,
  `email_sent` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_emails`
--

CREATE TABLE `user_emails` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_login_tracker`
--

CREATE TABLE `user_login_tracker` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED DEFAULT NULL,
  `IP` varchar(255) NOT NULL DEFAULT 'N/A',
  `kdaj` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_login_tracker`
--

INSERT INTO `user_login_tracker` (`id`, `uid`, `IP`, `kdaj`) VALUES(3, 1045, '172.18.0.1', '2022-03-21 09:13:57');
INSERT INTO `user_login_tracker` (`id`, `uid`, `IP`, `kdaj`) VALUES(4, 1045, '172.18.0.1', '2022-03-21 10:15:17');

-- --------------------------------------------------------

--
-- Table structure for table `user_options`
--

CREATE TABLE `user_options` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `option_name` varchar(255) NOT NULL,
  `option_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_tracker`
--

CREATE TABLE `user_tracker` (
  `uid` varchar(10) DEFAULT NULL,
  `timestamp` mediumtext DEFAULT NULL,
  `what` varchar(254) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_tracking`
--

CREATE TABLE `user_tracking` (
  `datetime` datetime NOT NULL,
  `ip` varchar(16) NOT NULL DEFAULT '',
  `user` int(11) NOT NULL DEFAULT 0,
  `get` text NOT NULL,
  `post` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `time_seconds` float NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_tracking`
--

INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:13:57', '172.18.0.1', 1045, 'lang: \"1\", a: \"\", m: \"\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:22:44', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:22:52', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"test\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:22:53', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:22:53', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:05', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:06', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:07', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:07', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:12', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"dfg\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:12', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:12', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:21', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"asd\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:21', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:23:21', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:26:12', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:26:15', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:26:18', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"test\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:26:18', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:26:18', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:26:27', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:26:28', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:26:39', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"global_user_settings\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:26:42', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:30:05', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:33:51', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:34:44', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:36:42', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:37:39', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:40:42', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:40:49', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:40:49', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:40:49', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:41:02', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"0\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"new\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:41:02', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:41:02', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:41:37', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"1\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:41:37', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:41:38', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:43:05', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:43:05', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:43:10', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"new\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:43:10', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:43:10', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:44:03', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:44:04', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:44:08', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"new\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:44:08', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:44:09', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:01', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:02', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:05', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"gsdf\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:05', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:05', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:25', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:26', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:29', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"ads\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:29', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:45:29', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:46:41', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:46:42', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:46:43', '172.18.0.1', 1045, 'a: \"diagnostics\", t: \"uporabniki\", m: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:46:45', '172.18.0.1', 1045, 't: \"dostop\", a: \"my_users_list\"', 'draw: \"1\", columns: \", 0: [data: 0 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 1: [data: 1 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 2: [data: 2 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 3: [data: 3 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 4: [data: 4 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 5: [data: 5 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 6: [data: 6 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 7: [data: 7 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 8: [data: 8 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 9: [data: 9 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 10: [data: 10 name:  searchable: true orderable: true , search: [value:  regex: false ] ] \", order: \", 0: [column: 0 dir: asc ] \", start: \"0\", length: \"50\", search: \"value:  regex: false \"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:46:48', '172.18.0.1', 1045, 'a: \"knjiznica\", m: \"\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:46:50', '172.18.0.1', 1045, 'a: \"diagnostics\", t: \"uporabniki\", m: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:46:50', '172.18.0.1', 1045, 't: \"dostop\", a: \"my_users_list\"', 'draw: \"1\", columns: \", 0: [data: 0 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 1: [data: 1 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 2: [data: 2 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 3: [data: 3 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 4: [data: 4 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 5: [data: 5 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 6: [data: 6 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 7: [data: 7 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 8: [data: 8 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 9: [data: 9 name:  searchable: true orderable: true , search: [value:  regex: false ] ] , 10: [data: 10 name:  searchable: true orderable: true , search: [value:  regex: false ] ] \", order: \", 0: [column: 0 dir: asc ] \", start: \"0\", length: \"50\", search: \"value:  regex: false \"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:46:55', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:48:07', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:48:14', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"aaa\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:48:14', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:48:14', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:48:24', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"0\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:48:24', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:48:25', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:50:23', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:50:23', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:50:29', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"new\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:50:29', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:50:29', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:51:03', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:51:04', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:51:06', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"adsf\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:51:06', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:51:06', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:51:16', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:51:45', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:51:47', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:52:08', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:52:09', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:52:16', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:52:16', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:52:18', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:52:42', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:10', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:10', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:10', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:11', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:17', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:18', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:18', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:18', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:26', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:26', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:27', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:27', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:53', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:53', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:55', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:53:55', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:55:08', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:55:08', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:56:41', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:56:41', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:56:42', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:56:42', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:56:51', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"new\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:56:51', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:56:51', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:07', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"0\", as_installation_type: \"1\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:07', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:08', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:18', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"1\", as_installation_type: \"\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:18', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:19', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:38', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:38', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:50', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"0\", as_installation_type: \"1\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"new\", as_app_settings-commercial_packages: \"\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\", as_cebelica_api: \"\", as_stripe-key: \"\", as_stripe-secret: \"\", as_paypal-account: \"\", as_paypal-client_id: \"\", as_paypal-secret: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:50', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:57:51', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:59:15', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 09:59:15', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 10:00:57', '172.18.0.1', 1045, 'a: \"editanketasettings\", m: \"system\", t: \"\", mode: \"\"', 'location: \"nastavitve\", submited: \"1\", as_debug: \"0\", as_installation_type: \"0\", as_confirm_registration: \"\", as_confirm_registration_admin: \"\", as_gdpr_admin_email: \"\", as_meta_admin_ids: \"\", SurveyDostop: \"3\", SurveyCookie: \"-1\", as_app_settings-app_name: \"\", as_app_settings-admin_email: \"\", as_app_settings-owner: \"\", as_app_settings-owner_website: \"\", as_app_settings-footer_custom: \"\", as_app_settings-footer_text: \"\", as_app_settings-footer_survey_custom: \"\", as_app_settings-footer_survey_text: \"\", as_app_settings-email_signature_custom: \"\", as_app_settings-email_signature_text: \"\", as_app_settings-survey_finish_url: \"\", as_app_settings-export_type: \"new\", as_app_limits-clicks_per_minute_limit: \"\", as_app_limits-question_count_limit: \"\", as_app_limits-response_count_limit: \"\", as_app_limits-invitation_count_limit: \"\", as_app_limits-admin_allow_only_ip: \"\", as_email_server_settings-SMTPFrom: \"\", as_email_server_settings-SMTPFromNice: \"\", as_email_server_settings-SMTPReplyTo: \"\", as_email_server_settings-SMTPHost: \"\", as_email_server_settings-SMTPPort: \"\", as_email_server_settings-SMTPSecure: \"\", as_email_server_settings-SMTPAuth: \"\", as_email_server_settings-SMTPUsername: \"\", as_email_server_settings-SMTPPassword: \"\", as_email_server_fromSurvey: \"\", as_google-recaptcha_sitekey: \"\", as_google-secret_captcha: \"\", as_google-login_client_id: \"\", as_google-login_client_secret: \"\", as_google-maps_API_key: \"\", as_facebook-appid: \"\", as_facebook-appsecret: \"\", as_maza-FCM_server_key: \"\", as_maza-APP_special_login_key: \"\", as_maza-NextPinMainToken: \"\", as_maza-NextPinMainPassword: \"\", as_hierarhija-folder_id: \"\", as_hierarhija-default_id: \"\", as_squalo-user: \"\", as_squalo-key: \"\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 10:00:57', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"system\", s: \"1\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 10:00:58', '172.18.0.1', 1045, 'a: \"display_success_save\", m: \"\", t: \"\", mode: \"\"', 'anketa: \"undefined\"', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 10:15:17', '172.18.0.1', 1045, 'lang: \"1\", a: \"\", m: \"\", t: \"\", mode: \"\"', '', 0, 0);
INSERT INTO `user_tracking` (`datetime`, `ip`, `user`, `get`, `post`, `status`, `time_seconds`) VALUES('2022-03-21 10:15:19', '172.18.0.1', 1045, 'a: \"nastavitve\", m: \"\", t: \"\", mode: \"\"', '', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `views`
--

CREATE TABLE `views` (
  `pid` int(11) NOT NULL DEFAULT 0,
  `uid` int(11) NOT NULL DEFAULT 0,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD UNIQUE KEY `what` (`what`,`domain`);

--
-- Indexes for table `browser_notifications_respondents`
--
ALTER TABLE `browser_notifications_respondents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `countries_locations`
--
ALTER TABLE `countries_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fb_users`
--
ALTER TABLE `fb_users`
  ADD KEY `id` (`id`);

--
-- Indexes for table `forum`
--
ALTER TABLE `forum`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_index` (`admin`),
  ADD KEY `user_index` (`user`);
ALTER TABLE `forum` ADD FULLTEXT KEY `naslov` (`naslov`);
ALTER TABLE `forum` ADD FULLTEXT KEY `NiceLink` (`NiceLink`);
ALTER TABLE `forum` ADD FULLTEXT KEY `NiceLink_2` (`NiceLink`);

--
-- Indexes for table `maza_app_users`
--
ALTER TABLE `maza_app_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maza_srv_activity`
--
ALTER TABLE `maza_srv_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_maza_activity_srv_ank_id` (`ank_id`);

--
-- Indexes for table `maza_srv_alarms`
--
ALTER TABLE `maza_srv_alarms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_maza_srv_ank_id` (`ank_id`);

--
-- Indexes for table `maza_srv_entry`
--
ALTER TABLE `maza_srv_entry`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_entry_srv_ank_id` (`ank_id`);

--
-- Indexes for table `maza_srv_geofences`
--
ALTER TABLE `maza_srv_geofences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ank_id` (`ank_id`);

--
-- Indexes for table `maza_srv_repeaters`
--
ALTER TABLE `maza_srv_repeaters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_maza_repeater_srv_ank_id` (`ank_id`);

--
-- Indexes for table `maza_srv_tracking`
--
ALTER TABLE `maza_srv_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_maza_tracking_srv_ank_id` (`ank_id`);

--
-- Indexes for table `maza_srv_triggered_activities`
--
ALTER TABLE `maza_srv_triggered_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `act_id` (`act_id`),
  ADD KEY `maza_user_id` (`maza_user_id`);

--
-- Indexes for table `maza_srv_triggered_geofences`
--
ALTER TABLE `maza_srv_triggered_geofences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `geof_id` (`geof_id`),
  ADD KEY `maza_user_id` (`maza_user_id`);

--
-- Indexes for table `maza_srv_users`
--
ALTER TABLE `maza_srv_users`
  ADD KEY `fk_maza_app_users_maza_srv_users` (`maza_user_id`),
  ADD KEY `fk_srv_user_maza_srv_users` (`srv_user_id`),
  ADD KEY `fk_maza_srv_users_loc_id` (`loc_id`),
  ADD KEY `fk_maza_srv_users_tact_id` (`tact_id`),
  ADD KEY `fk_maza_srv_users_tgeof_id` (`tgeof_id`);

--
-- Indexes for table `maza_survey`
--
ALTER TABLE `maza_survey`
  ADD KEY `fk_srv_id_maza_survey` (`srv_id`);

--
-- Indexes for table `maza_user_activity_recognition`
--
ALTER TABLE `maza_user_activity_recognition`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_maza_user_activity_recognition_user_id` (`maza_user_id`);

--
-- Indexes for table `maza_user_locations`
--
ALTER TABLE `maza_user_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_maza_user_locations_user_id` (`maza_user_id`),
  ADD KEY `fk_maza_user_locations_tgeof_id` (`tgeof_id`);

--
-- Indexes for table `maza_user_srv_access`
--
ALTER TABLE `maza_user_srv_access`
  ADD KEY `fk_maza_app_users_maza_user_srv_access` (`ank_id`),
  ADD KEY `fk_srv_anketa_maza_user_srv_access` (`maza_user_id`);

--
-- Indexes for table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fid_index` (`fid`),
  ADD KEY `tid_index` (`tid`),
  ADD KEY `admin_index` (`admin`),
  ADD KEY `time2_index` (`time2`);
ALTER TABLE `post` ADD FULLTEXT KEY `naslov` (`naslov`);
ALTER TABLE `post` ADD FULLTEXT KEY `naslov_2` (`naslov`);
ALTER TABLE `post` ADD FULLTEXT KEY `user_2` (`user`);
ALTER TABLE `post` ADD FULLTEXT KEY `vsebina_2` (`vsebina`);
ALTER TABLE `post` ADD FULLTEXT KEY `NiceLink` (`NiceLink`);

--
-- Indexes for table `restrict_fk_srv_anketa`
--
ALTER TABLE `restrict_fk_srv_anketa`
  ADD PRIMARY KEY (`ank_id`);

--
-- Indexes for table `restrict_fk_srv_grupa`
--
ALTER TABLE `restrict_fk_srv_grupa`
  ADD PRIMARY KEY (`gru_id`);

--
-- Indexes for table `restrict_fk_srv_if`
--
ALTER TABLE `restrict_fk_srv_if`
  ADD PRIMARY KEY (`if_id`);

--
-- Indexes for table `restrict_fk_srv_spremenljivka`
--
ALTER TABLE `restrict_fk_srv_spremenljivka`
  ADD PRIMARY KEY (`spr_id`);

--
-- Indexes for table `restrict_fk_srv_vrednost`
--
ALTER TABLE `restrict_fk_srv_vrednost`
  ADD PRIMARY KEY (`vre_id`);

--
-- Indexes for table `srv_activity`
--
ALTER TABLE `srv_activity`
  ADD UNIQUE KEY `sid` (`sid`,`starts`,`expire`,`uid`);

--
-- Indexes for table `srv_advanced_paradata_alert`
--
ALTER TABLE `srv_advanced_paradata_alert`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_advanced_paradata_alert_page_id` (`page_id`);

--
-- Indexes for table `srv_advanced_paradata_movement`
--
ALTER TABLE `srv_advanced_paradata_movement`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_advanced_paradata_movement_page_id` (`page_id`);

--
-- Indexes for table `srv_advanced_paradata_other`
--
ALTER TABLE `srv_advanced_paradata_other`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_advanced_paradata_other_page_id` (`page_id`);

--
-- Indexes for table `srv_advanced_paradata_page`
--
ALTER TABLE `srv_advanced_paradata_page`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_advanced_paradata_page_ank_id` (`ank_id`),
  ADD KEY `fk_srv_advanced_paradata_page_usr_id` (`usr_id`);

--
-- Indexes for table `srv_advanced_paradata_question`
--
ALTER TABLE `srv_advanced_paradata_question`
  ADD PRIMARY KEY (`page_id`,`spr_id`),
  ADD KEY `fk_srv_advanced_paradata_question_spr_id` (`spr_id`);

--
-- Indexes for table `srv_advanced_paradata_settings`
--
ALTER TABLE `srv_advanced_paradata_settings`
  ADD PRIMARY KEY (`ank_id`);

--
-- Indexes for table `srv_advanced_paradata_vrednost`
--
ALTER TABLE `srv_advanced_paradata_vrednost`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_advanced_paradata_vrednost_page_id` (`page_id`);

--
-- Indexes for table `srv_alert`
--
ALTER TABLE `srv_alert`
  ADD PRIMARY KEY (`ank_id`),
  ADD KEY `finish_respondent_if` (`finish_respondent_if`),
  ADD KEY `finish_respondent_cms_if` (`finish_respondent_cms_if`),
  ADD KEY `finish_other_if` (`finish_other_if`);

--
-- Indexes for table `srv_alert_custom`
--
ALTER TABLE `srv_alert_custom`
  ADD UNIQUE KEY `ank_id` (`ank_id`,`type`,`uid`);

--
-- Indexes for table `srv_analysis_archive`
--
ALTER TABLE `srv_analysis_archive`
  ADD PRIMARY KEY (`id`,`sid`),
  ADD KEY `fk_srv_analysis_archive_sid` (`sid`);

--
-- Indexes for table `srv_anketa`
--
ALTER TABLE `srv_anketa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hash` (`hash`),
  ADD KEY `fk_srv_anketa_folder` (`folder`),
  ADD KEY `active` (`active`);

--
-- Indexes for table `srv_anketa_module`
--
ALTER TABLE `srv_anketa_module`
  ADD PRIMARY KEY (`ank_id`,`modul`);

--
-- Indexes for table `srv_anketa_template`
--
ALTER TABLE `srv_anketa_template`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_api_auth`
--
ALTER TABLE `srv_api_auth`
  ADD PRIMARY KEY (`usr_id`);

--
-- Indexes for table `srv_branching`
--
ALTER TABLE `srv_branching`
  ADD PRIMARY KEY (`ank_id`,`parent`,`element_spr`,`element_if`),
  ADD KEY `element_spr` (`element_spr`),
  ADD KEY `element_spr_if` (`element_spr`,`element_if`),
  ADD KEY `parent` (`parent`),
  ADD KEY `element_if` (`element_if`);

--
-- Indexes for table `srv_calculation`
--
ALTER TABLE `srv_calculation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cnd_id` (`cnd_id`),
  ADD KEY `spr_id` (`spr_id`,`vre_id`),
  ADD KEY `fk_srv_calculation_vre_id` (`vre_id`);

--
-- Indexes for table `srv_call_current`
--
ALTER TABLE `srv_call_current`
  ADD PRIMARY KEY (`usr_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `started_time` (`started_time`);

--
-- Indexes for table `srv_call_history`
--
ALTER TABLE `srv_call_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_id` (`usr_id`),
  ADD KEY `time` (`insert_time`),
  ADD KEY `status` (`status`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `srv_call_schedule`
--
ALTER TABLE `srv_call_schedule`
  ADD PRIMARY KEY (`usr_id`);

--
-- Indexes for table `srv_call_setting`
--
ALTER TABLE `srv_call_setting`
  ADD PRIMARY KEY (`survey_id`);

--
-- Indexes for table `srv_captcha`
--
ALTER TABLE `srv_captcha`
  ADD PRIMARY KEY (`ank_id`,`spr_id`,`usr_id`),
  ADD KEY `srv_captcha_usr_id` (`usr_id`),
  ADD KEY `srv_captcha_spr_id` (`spr_id`);

--
-- Indexes for table `srv_chart_skin`
--
ALTER TABLE `srv_chart_skin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_chat_settings`
--
ALTER TABLE `srv_chat_settings`
  ADD PRIMARY KEY (`ank_id`);

--
-- Indexes for table `srv_clicks`
--
ALTER TABLE `srv_clicks`
  ADD PRIMARY KEY (`ank_id`);

--
-- Indexes for table `srv_comment_resp`
--
ALTER TABLE `srv_comment_resp`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_condition`
--
ALTER TABLE `srv_condition`
  ADD PRIMARY KEY (`id`),
  ADD KEY `if_id` (`if_id`),
  ADD KEY `spr_id` (`spr_id`,`vre_id`),
  ADD KEY `fk_srv_condition_vre_id` (`vre_id`);

--
-- Indexes for table `srv_condition_grid`
--
ALTER TABLE `srv_condition_grid`
  ADD PRIMARY KEY (`cond_id`,`grd_id`);

--
-- Indexes for table `srv_condition_profiles`
--
ALTER TABLE `srv_condition_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sid` (`sid`,`uid`,`if_id`),
  ADD KEY `if_id` (`if_id`);

--
-- Indexes for table `srv_condition_vre`
--
ALTER TABLE `srv_condition_vre`
  ADD PRIMARY KEY (`cond_id`,`vre_id`),
  ADD KEY `fk_srv_condition_vre_vre_id` (`vre_id`);

--
-- Indexes for table `srv_custom_report`
--
ALTER TABLE `srv_custom_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_custom_report_profiles`
--
ALTER TABLE `srv_custom_report_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_custom_report_share`
--
ALTER TABLE `srv_custom_report_share`
  ADD PRIMARY KEY (`ank_id`,`profile_id`,`author_usr_id`,`share_usr_id`);

--
-- Indexes for table `srv_datasetting_profile`
--
ALTER TABLE `srv_datasetting_profile`
  ADD PRIMARY KEY (`id`,`uid`),
  ADD UNIQUE KEY `id` (`id`,`uid`,`name`);

--
-- Indexes for table `srv_data_checkgrid_active`
--
ALTER TABLE `srv_data_checkgrid_active`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`grd_id`,`loop_id`),
  ADD KEY `fk_srv_data_checkgrid_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_checkgrid_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_checkgrid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_checkgrid_archive1`
--
ALTER TABLE `srv_data_checkgrid_archive1`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`grd_id`,`loop_id`),
  ADD KEY `fk_srv_data_checkgrid_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_checkgrid_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_checkgrid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_checkgrid_archive2`
--
ALTER TABLE `srv_data_checkgrid_archive2`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`grd_id`,`loop_id`),
  ADD KEY `fk_srv_data_checkgrid_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_checkgrid_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_checkgrid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_files`
--
ALTER TABLE `srv_data_files`
  ADD PRIMARY KEY (`sid`),
  ADD UNIQUE KEY `sid` (`sid`);

--
-- Indexes for table `srv_data_glasovanje`
--
ALTER TABLE `srv_data_glasovanje`
  ADD PRIMARY KEY (`spr_id`,`usr_id`),
  ADD KEY `fk_srv_data_glasovanje_usr_id` (`usr_id`);

--
-- Indexes for table `srv_data_grid_active`
--
ALTER TABLE `srv_data_grid_active`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `vre_id` (`vre_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_grid_active_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_grid_archive1`
--
ALTER TABLE `srv_data_grid_archive1`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `vre_id` (`vre_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_grid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_grid_archive2`
--
ALTER TABLE `srv_data_grid_archive2`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `vre_id` (`vre_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_grid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_heatmap`
--
ALTER TABLE `srv_data_heatmap`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `loop_id` (`loop_id`),
  ADD KEY `ank_id` (`ank_id`),
  ADD KEY `spr_id` (`spr_id`),
  ADD KEY `vre_id` (`vre_id`);

--
-- Indexes for table `srv_data_imena`
--
ALTER TABLE `srv_data_imena`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_data_imena_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_imena_spr_id` (`spr_id`);

--
-- Indexes for table `srv_data_map`
--
ALTER TABLE `srv_data_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `loop_id` (`loop_id`),
  ADD KEY `ank_id` (`ank_id`),
  ADD KEY `spr_id` (`spr_id`),
  ADD KEY `vre_id` (`vre_id`);

--
-- Indexes for table `srv_data_number`
--
ALTER TABLE `srv_data_number`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `fk_srv_data_number_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_number_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_number_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_random_blockContent`
--
ALTER TABLE `srv_data_random_blockContent`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usr_id` (`usr_id`,`block_id`),
  ADD KEY `fk_srv_data_random_blockContent_block_id` (`block_id`);

--
-- Indexes for table `srv_data_random_spremenljivkaContent`
--
ALTER TABLE `srv_data_random_spremenljivkaContent`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usr_id` (`usr_id`,`spr_id`),
  ADD KEY `fk_srv_data_random_spremenljivkaContent_spr_id` (`spr_id`);

--
-- Indexes for table `srv_data_rating`
--
ALTER TABLE `srv_data_rating`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `fk_srv_data_rating_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_rating_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_textgrid_active`
--
ALTER TABLE `srv_data_textgrid_active`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`grd_id`,`loop_id`),
  ADD KEY `fk_srv_data_textgrid_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_textgrid_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_textgrid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_textgrid_archive1`
--
ALTER TABLE `srv_data_textgrid_archive1`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`grd_id`,`loop_id`),
  ADD KEY `fk_srv_data_textgrid_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_textgrid_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_textgrid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_textgrid_archive2`
--
ALTER TABLE `srv_data_textgrid_archive2`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`grd_id`,`loop_id`),
  ADD KEY `fk_srv_data_textgrid_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_textgrid_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_textgrid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_text_active`
--
ALTER TABLE `srv_data_text_active`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_text_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_text_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_text_archive1`
--
ALTER TABLE `srv_data_text_archive1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_text_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_text_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_text_archive2`
--
ALTER TABLE `srv_data_text_archive2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_text_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_text_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_upload`
--
ALTER TABLE `srv_data_upload`
  ADD KEY `srv_data_upload_ank_id` (`ank_id`),
  ADD KEY `srv_data_upload_usr_id` (`usr_id`);

--
-- Indexes for table `srv_data_vrednost_active`
--
ALTER TABLE `srv_data_vrednost_active`
  ADD UNIQUE KEY `spr_id_2` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `spr_id` (`spr_id`,`usr_id`),
  ADD KEY `vre_usr` (`vre_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_vrednost_active_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_vrednost_archive1`
--
ALTER TABLE `srv_data_vrednost_archive1`
  ADD UNIQUE KEY `spr_id_2` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `spr_id` (`spr_id`,`usr_id`),
  ADD KEY `vre_usr` (`vre_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_vrednost_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_vrednost_archive2`
--
ALTER TABLE `srv_data_vrednost_archive2`
  ADD UNIQUE KEY `spr_id_2` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `spr_id` (`spr_id`,`usr_id`),
  ADD KEY `vre_usr` (`vre_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_vrednost_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_vrednost_cond`
--
ALTER TABLE `srv_data_vrednost_cond`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_vrednost_cond_id` (`vre_id`),
  ADD KEY `fk_srv_data_vrednost_cond_loop_id` (`loop_id`);

--
-- Indexes for table `srv_dostop`
--
ALTER TABLE `srv_dostop`
  ADD PRIMARY KEY (`ank_id`,`uid`),
  ADD KEY `alert_complete_if` (`alert_complete_if`);

--
-- Indexes for table `srv_dostop_language`
--
ALTER TABLE `srv_dostop_language`
  ADD PRIMARY KEY (`ank_id`,`uid`,`lang_id`),
  ADD KEY `fk_srv_dostop_language_ank_id_lang_id` (`ank_id`,`lang_id`);

--
-- Indexes for table `srv_dostop_manage`
--
ALTER TABLE `srv_dostop_manage`
  ADD PRIMARY KEY (`manager`,`user`);

--
-- Indexes for table `srv_evoli_landingpage_access`
--
ALTER TABLE `srv_evoli_landingpage_access`
  ADD PRIMARY KEY (`ank_id`,`email`,`pass`);

--
-- Indexes for table `srv_evoli_teammeter`
--
ALTER TABLE `srv_evoli_teammeter`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_evoli_teammeter_ank_id` (`ank_id`),
  ADD KEY `fk_srv_evoli_teammeter_skupina_id` (`skupina_id`);

--
-- Indexes for table `srv_evoli_teammeter_data_department`
--
ALTER TABLE `srv_evoli_teammeter_data_department`
  ADD PRIMARY KEY (`department_id`,`usr_id`),
  ADD KEY `fk_srv_evoli_data_department_usr_id` (`usr_id`);

--
-- Indexes for table `srv_evoli_teammeter_delayed`
--
ALTER TABLE `srv_evoli_teammeter_delayed`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_evoli_teammeter_delayed_ank_id` (`ank_id`);

--
-- Indexes for table `srv_evoli_teammeter_department`
--
ALTER TABLE `srv_evoli_teammeter_department`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_evoli_teammeter_department_tm_id` (`tm_id`);

--
-- Indexes for table `srv_fieldwork`
--
ALTER TABLE `srv_fieldwork`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_folder`
--
ALTER TABLE `srv_folder`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent` (`parent`);

--
-- Indexes for table `srv_gdpr_anketa`
--
ALTER TABLE `srv_gdpr_anketa`
  ADD PRIMARY KEY (`ank_id`);

--
-- Indexes for table `srv_gdpr_requests`
--
ALTER TABLE `srv_gdpr_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_gdpr_requests_usr_id` (`usr_id`),
  ADD KEY `fk_srv_gdpr_requests_ank_id` (`ank_id`);

--
-- Indexes for table `srv_gdpr_user`
--
ALTER TABLE `srv_gdpr_user`
  ADD PRIMARY KEY (`usr_id`);

--
-- Indexes for table `srv_glasovanje`
--
ALTER TABLE `srv_glasovanje`
  ADD PRIMARY KEY (`ank_id`,`spr_id`),
  ADD KEY `fk_srv_glasovanje_spr_id` (`spr_id`);

--
-- Indexes for table `srv_grid`
--
ALTER TABLE `srv_grid`
  ADD PRIMARY KEY (`id`,`spr_id`),
  ADD KEY `spr_id` (`spr_id`);

--
-- Indexes for table `srv_grid_multiple`
--
ALTER TABLE `srv_grid_multiple`
  ADD KEY `srv_grid_multiple_ank_id` (`ank_id`),
  ADD KEY `srv_grid_multiple_parent` (`parent`),
  ADD KEY `srv_grid_multiple_spr_id` (`spr_id`);

--
-- Indexes for table `srv_grupa`
--
ALTER TABLE `srv_grupa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ank_id` (`ank_id`),
  ADD KEY `ank_id_2` (`ank_id`,`vrstni_red`);

--
-- Indexes for table `srv_hash_url`
--
ALTER TABLE `srv_hash_url`
  ADD PRIMARY KEY (`hash`),
  ADD UNIQUE KEY `hash` (`hash`,`anketa`),
  ADD KEY `FK_srv_hash_url` (`anketa`);

--
-- Indexes for table `srv_help`
--
ALTER TABLE `srv_help`
  ADD PRIMARY KEY (`what`,`lang`);

--
-- Indexes for table `srv_hierarhija_dostop`
--
ALTER TABLE `srv_hierarhija_dostop`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `srv_hierarhija_koda`
--
ALTER TABLE `srv_hierarhija_koda`
  ADD PRIMARY KEY (`koda`),
  ADD UNIQUE KEY `koda` (`koda`),
  ADD KEY `anketa_id` (`anketa_id`),
  ADD KEY `hierarhija_struktura_id` (`hierarhija_struktura_id`),
  ADD KEY `srv_user_id` (`srv_user_id`);

--
-- Indexes for table `srv_hierarhija_options`
--
ALTER TABLE `srv_hierarhija_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anketa_id` (`anketa_id`);

--
-- Indexes for table `srv_hierarhija_ravni`
--
ALTER TABLE `srv_hierarhija_ravni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anketa_id` (`anketa_id`);

--
-- Indexes for table `srv_hierarhija_shrani`
--
ALTER TABLE `srv_hierarhija_shrani`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anketa_id` (`anketa_id`);

--
-- Indexes for table `srv_hierarhija_sifranti`
--
ALTER TABLE `srv_hierarhija_sifranti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hierarhija_ravni_id` (`hierarhija_ravni_id`);

--
-- Indexes for table `srv_hierarhija_sifrant_vrednost`
--
ALTER TABLE `srv_hierarhija_sifrant_vrednost`
  ADD KEY `sifrant_id` (`sifrant_id`),
  ADD KEY `vrednost_id` (`vrednost_id`);

--
-- Indexes for table `srv_hierarhija_struktura`
--
ALTER TABLE `srv_hierarhija_struktura`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hierarhija_ravni_id` (`hierarhija_ravni_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `hierarhija_sifranti_id` (`hierarhija_sifranti_id`),
  ADD KEY `anketa_id` (`anketa_id`);

--
-- Indexes for table `srv_hierarhija_struktura_users`
--
ALTER TABLE `srv_hierarhija_struktura_users`
  ADD KEY `hierarhija_struktura_id` (`hierarhija_struktura_id`);

--
-- Indexes for table `srv_hierarhija_supersifra`
--
ALTER TABLE `srv_hierarhija_supersifra`
  ADD PRIMARY KEY (`koda`),
  ADD UNIQUE KEY `koda` (`koda`),
  ADD KEY `anketa_id` (`anketa_id`);

--
-- Indexes for table `srv_hierarhija_supersifra_resevanje`
--
ALTER TABLE `srv_hierarhija_supersifra_resevanje`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `supersifra` (`supersifra`),
  ADD KEY `koda` (`koda`);

--
-- Indexes for table `srv_hierarhija_users`
--
ALTER TABLE `srv_hierarhija_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anketa_id` (`anketa_id`);

--
-- Indexes for table `srv_hotspot_regions`
--
ALTER TABLE `srv_hotspot_regions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_hotspot_regions_spr_id` (`spr_id`),
  ADD KEY `fk_srv_hotspot_regions_vre_id` (`vre_id`);

--
-- Indexes for table `srv_if`
--
ALTER TABLE `srv_if`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folder` (`folder`);

--
-- Indexes for table `srv_invitations_archive`
--
ALTER TABLE `srv_invitations_archive`
  ADD PRIMARY KEY (`id`),
  ADD KEY `srv_invitations_archive_ank_id` (`ank_id`);

--
-- Indexes for table `srv_invitations_archive_recipients`
--
ALTER TABLE `srv_invitations_archive_recipients`
  ADD KEY `srv_invitations_archive_recipients_arch_id` (`arch_id`),
  ADD KEY `srv_invitations_archive_recipients_rec_id` (`rec_id`);

--
-- Indexes for table `srv_invitations_messages`
--
ALTER TABLE `srv_invitations_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `srv_invitations_messages_ank_id` (`ank_id`);

--
-- Indexes for table `srv_invitations_recipients`
--
ALTER TABLE `srv_invitations_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ank_id_unique` (`ank_id`,`email`,`firstname`,`lastname`,`salutation`,`phone`,`custom`);

--
-- Indexes for table `srv_invitations_recipients_profiles`
--
ALTER TABLE `srv_invitations_recipients_profiles`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `srv_invitations_tracking`
--
ALTER TABLE `srv_invitations_tracking`
  ADD PRIMARY KEY (`uniq`),
  ADD UNIQUE KEY `arc_res_status` (`inv_arch_id`,`res_id`,`status`),
  ADD KEY `srv_invitations_tracking_res_id` (`res_id`);

--
-- Indexes for table `srv_language`
--
ALTER TABLE `srv_language`
  ADD PRIMARY KEY (`ank_id`,`lang_id`);

--
-- Indexes for table `srv_language_grid`
--
ALTER TABLE `srv_language_grid`
  ADD PRIMARY KEY (`spr_id`,`grd_id`,`lang_id`),
  ADD KEY `fk_srv_language_grid_ank_id_lang_id` (`ank_id`,`lang_id`);

--
-- Indexes for table `srv_language_slider`
--
ALTER TABLE `srv_language_slider`
  ADD PRIMARY KEY (`spr_id`,`label_id`,`lang_id`),
  ADD KEY `fk_srv_language_slider_ank_id_lang_id` (`ank_id`,`lang_id`);

--
-- Indexes for table `srv_language_spremenljivka`
--
ALTER TABLE `srv_language_spremenljivka`
  ADD PRIMARY KEY (`ank_id`,`spr_id`,`lang_id`),
  ADD KEY `fk_srv_language_spremenljivka_spr_id` (`spr_id`),
  ADD KEY `fk_srv_language_spremenljivka_ank_id_lang_id` (`ank_id`,`lang_id`);

--
-- Indexes for table `srv_language_vrednost`
--
ALTER TABLE `srv_language_vrednost`
  ADD PRIMARY KEY (`ank_id`,`vre_id`,`lang_id`),
  ADD KEY `fk_srv_language_vrednost_ank_id_lang_id` (`ank_id`,`lang_id`),
  ADD KEY `fk_srv_language_vrednost_vre_id` (`vre_id`);

--
-- Indexes for table `srv_library_anketa`
--
ALTER TABLE `srv_library_anketa`
  ADD PRIMARY KEY (`ank_id`,`uid`);

--
-- Indexes for table `srv_library_folder`
--
ALTER TABLE `srv_library_folder`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent` (`parent`);

--
-- Indexes for table `srv_lock`
--
ALTER TABLE `srv_lock`
  ADD PRIMARY KEY (`lock_key`);

--
-- Indexes for table `srv_loop`
--
ALTER TABLE `srv_loop`
  ADD PRIMARY KEY (`if_id`),
  ADD KEY `fk_srv_loop_spr_id` (`spr_id`);

--
-- Indexes for table `srv_loop_data`
--
ALTER TABLE `srv_loop_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `if_id` (`if_id`,`vre_id`);

--
-- Indexes for table `srv_loop_vre`
--
ALTER TABLE `srv_loop_vre`
  ADD UNIQUE KEY `if_id` (`if_id`,`vre_id`),
  ADD KEY `fk_srv_loop_vre_vre_id` (`vre_id`);

--
-- Indexes for table `srv_mc_element`
--
ALTER TABLE `srv_mc_element`
  ADD PRIMARY KEY (`id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `srv_mc_table`
--
ALTER TABLE `srv_mc_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_misc`
--
ALTER TABLE `srv_misc`
  ADD UNIQUE KEY `what` (`what`);

--
-- Indexes for table `srv_missing_profiles`
--
ALTER TABLE `srv_missing_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`,`name`);

--
-- Indexes for table `srv_missing_profiles_values`
--
ALTER TABLE `srv_missing_profiles_values`
  ADD PRIMARY KEY (`missing_pid`,`missing_value`,`type`),
  ADD UNIQUE KEY `pid` (`missing_pid`,`missing_value`,`type`);

--
-- Indexes for table `srv_missing_values`
--
ALTER TABLE `srv_missing_values`
  ADD UNIQUE KEY `sid` (`sid`,`type`,`value`);

--
-- Indexes for table `srv_module`
--
ALTER TABLE `srv_module`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_mysurvey_anketa`
--
ALTER TABLE `srv_mysurvey_anketa`
  ADD PRIMARY KEY (`ank_id`,`usr_id`);

--
-- Indexes for table `srv_mysurvey_folder`
--
ALTER TABLE `srv_mysurvey_folder`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_nice_links`
--
ALTER TABLE `srv_nice_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `link` (`link`),
  ADD KEY `srv_nice_links_ank_id` (`ank_id`);

--
-- Indexes for table `srv_nice_links_skupine`
--
ALTER TABLE `srv_nice_links_skupine`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_notifications`
--
ALTER TABLE `srv_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_notifications_messages`
--
ALTER TABLE `srv_notifications_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_panel_if`
--
ALTER TABLE `srv_panel_if`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ank_id` (`ank_id`,`if_id`),
  ADD KEY `fk_srv_panel_if_if_id` (`if_id`);

--
-- Indexes for table `srv_panel_settings`
--
ALTER TABLE `srv_panel_settings`
  ADD PRIMARY KEY (`ank_id`);

--
-- Indexes for table `srv_parapodatki`
--
ALTER TABLE `srv_parapodatki`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_parapodatki_ank_id` (`ank_id`),
  ADD KEY `fk_srv_parapodatki_usr_id` (`usr_id`);

--
-- Indexes for table `srv_password`
--
ALTER TABLE `srv_password`
  ADD KEY `srv_password_ank_id` (`ank_id`);

--
-- Indexes for table `srv_profile_manager`
--
ALTER TABLE `srv_profile_manager`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_quiz_settings`
--
ALTER TABLE `srv_quiz_settings`
  ADD PRIMARY KEY (`ank_id`);

--
-- Indexes for table `srv_quiz_vrednost`
--
ALTER TABLE `srv_quiz_vrednost`
  ADD PRIMARY KEY (`spr_id`,`vre_id`),
  ADD KEY `fk_srv_quiz_vrednost_vre_id` (`vre_id`);

--
-- Indexes for table `srv_quota`
--
ALTER TABLE `srv_quota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cnd_id` (`cnd_id`),
  ADD KEY `spr_id` (`spr_id`,`vre_id`);

--
-- Indexes for table `srv_recode`
--
ALTER TABLE `srv_recode`
  ADD UNIQUE KEY `ank_id` (`ank_id`,`spr_id`,`search`,`operator`);

--
-- Indexes for table `srv_recode_number`
--
ALTER TABLE `srv_recode_number`
  ADD UNIQUE KEY `ank_id` (`ank_id`,`spr_id`,`search`,`operator`);

--
-- Indexes for table `srv_recode_spremenljivka`
--
ALTER TABLE `srv_recode_spremenljivka`
  ADD UNIQUE KEY `ank_id` (`ank_id`,`spr_id`);

--
-- Indexes for table `srv_respondents`
--
ALTER TABLE `srv_respondents`
  ADD KEY `fk_srv_respondents_pid` (`pid`);

--
-- Indexes for table `srv_respondent_profiles`
--
ALTER TABLE `srv_respondent_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`,`name`);

--
-- Indexes for table `srv_simple_mail_invitation`
--
ALTER TABLE `srv_simple_mail_invitation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_slideshow_settings`
--
ALTER TABLE `srv_slideshow_settings`
  ADD PRIMARY KEY (`ank_id`),
  ADD UNIQUE KEY `ank_id` (`ank_id`);

--
-- Indexes for table `srv_specialdata_vrednost`
--
ALTER TABLE `srv_specialdata_vrednost`
  ADD PRIMARY KEY (`spr_id`,`usr_id`),
  ADD KEY `fk_srv_specialdata_vrednost_usr_id` (`usr_id`),
  ADD KEY `fk_srv_specialdata_vrednost_vre_id` (`vre_id`);

--
-- Indexes for table `srv_spremenljivka`
--
ALTER TABLE `srv_spremenljivka`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gru_id` (`gru_id`),
  ADD KEY `gru_id_2` (`gru_id`,`vrstni_red`);

--
-- Indexes for table `srv_spremenljivka_tracking`
--
ALTER TABLE `srv_spremenljivka_tracking`
  ADD KEY `srv_spremenljivka_tracking_ank_id` (`ank_id`),
  ADD KEY `srv_spremenljivka_tracking_tracking_id` (`spr_id`);

--
-- Indexes for table `srv_squalo_anketa`
--
ALTER TABLE `srv_squalo_anketa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ank_id` (`ank_id`);

--
-- Indexes for table `srv_statistic_profile`
--
ALTER TABLE `srv_statistic_profile`
  ADD PRIMARY KEY (`id`,`uid`),
  ADD UNIQUE KEY `id` (`id`,`uid`,`name`);

--
-- Indexes for table `srv_status_casi`
--
ALTER TABLE `srv_status_casi`
  ADD PRIMARY KEY (`id`,`uid`),
  ADD UNIQUE KEY `id` (`id`,`uid`,`name`);

--
-- Indexes for table `srv_status_profile`
--
ALTER TABLE `srv_status_profile`
  ADD PRIMARY KEY (`id`,`uid`),
  ADD UNIQUE KEY `id` (`id`,`uid`,`name`);

--
-- Indexes for table `srv_survey_conditions`
--
ALTER TABLE `srv_survey_conditions`
  ADD KEY `srv_survey_conditions_ank_id` (`ank_id`),
  ADD KEY `srv_survey_conditions_if_id` (`if_id`);

--
-- Indexes for table `srv_survey_list`
--
ALTER TABLE `srv_survey_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_survey_misc`
--
ALTER TABLE `srv_survey_misc`
  ADD UNIQUE KEY `sid` (`sid`,`what`);

--
-- Indexes for table `srv_survey_session`
--
ALTER TABLE `srv_survey_session`
  ADD PRIMARY KEY (`ank_id`,`what`);

--
-- Indexes for table `srv_survey_unsubscribe`
--
ALTER TABLE `srv_survey_unsubscribe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ank_id` (`ank_id`,`email`);

--
-- Indexes for table `srv_survey_unsubscribe_codes`
--
ALTER TABLE `srv_survey_unsubscribe_codes`
  ADD UNIQUE KEY `ank_id` (`ank_id`,`email`);

--
-- Indexes for table `srv_sys_filters`
--
ALTER TABLE `srv_sys_filters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `filter` (`filter`,`text`),
  ADD UNIQUE KEY `type` (`type`,`filter`);

--
-- Indexes for table `srv_telephone_comment`
--
ALTER TABLE `srv_telephone_comment`
  ADD PRIMARY KEY (`rec_id`);

--
-- Indexes for table `srv_telephone_current`
--
ALTER TABLE `srv_telephone_current`
  ADD PRIMARY KEY (`rec_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `started_time` (`started_time`);

--
-- Indexes for table `srv_telephone_history`
--
ALTER TABLE `srv_telephone_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rec_id` (`rec_id`),
  ADD KEY `time` (`insert_time`),
  ADD KEY `status` (`status`),
  ADD KEY `survey_id` (`survey_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `srv_telephone_schedule`
--
ALTER TABLE `srv_telephone_schedule`
  ADD PRIMARY KEY (`rec_id`);

--
-- Indexes for table `srv_telephone_setting`
--
ALTER TABLE `srv_telephone_setting`
  ADD PRIMARY KEY (`survey_id`);

--
-- Indexes for table `srv_testdata_archive`
--
ALTER TABLE `srv_testdata_archive`
  ADD UNIQUE KEY `ank_usr_id` (`ank_id`,`usr_id`);

--
-- Indexes for table `srv_theme_editor`
--
ALTER TABLE `srv_theme_editor`
  ADD PRIMARY KEY (`profile_id`,`id`,`type`);

--
-- Indexes for table `srv_theme_editor_mobile`
--
ALTER TABLE `srv_theme_editor_mobile`
  ADD PRIMARY KEY (`profile_id`,`id`,`type`);

--
-- Indexes for table `srv_theme_profiles`
--
ALTER TABLE `srv_theme_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_theme_profiles_mobile`
--
ALTER TABLE `srv_theme_profiles_mobile`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_time_profile`
--
ALTER TABLE `srv_time_profile`
  ADD PRIMARY KEY (`id`,`uid`),
  ADD UNIQUE KEY `id` (`id`,`uid`,`name`);

--
-- Indexes for table `srv_tracking_active`
--
ALTER TABLE `srv_tracking_active`
  ADD KEY `ank_id` (`ank_id`,`datetime`,`user`);

--
-- Indexes for table `srv_tracking_api`
--
ALTER TABLE `srv_tracking_api`
  ADD KEY `ank_id` (`ank_id`,`datetime`,`user`);

--
-- Indexes for table `srv_tracking_archive1`
--
ALTER TABLE `srv_tracking_archive1`
  ADD KEY `ank_id` (`ank_id`,`datetime`,`user`);

--
-- Indexes for table `srv_tracking_archive2`
--
ALTER TABLE `srv_tracking_archive2`
  ADD KEY `ank_id` (`ank_id`,`datetime`,`user`);

--
-- Indexes for table `srv_user`
--
ALTER TABLE `srv_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `srv_user_cookie` (`cookie`),
  ADD UNIQUE KEY `ank_id_2` (`ank_id`,`pass`),
  ADD KEY `ank_id` (`ank_id`),
  ADD KEY `preview` (`preview`),
  ADD KEY `recnum` (`ank_id`,`recnum`,`preview`,`deleted`),
  ADD KEY `response` (`ank_id`,`testdata`,`preview`,`last_status`,`time_edit`,`time_insert`);

--
-- Indexes for table `srv_userbase`
--
ALTER TABLE `srv_userbase`
  ADD PRIMARY KEY (`usr_id`,`tip`);

--
-- Indexes for table `srv_userbase_invitations`
--
ALTER TABLE `srv_userbase_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `name_2` (`name`);

--
-- Indexes for table `srv_userbase_respondents`
--
ALTER TABLE `srv_userbase_respondents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `list_id` (`list_id`);

--
-- Indexes for table `srv_userbase_respondents_lists`
--
ALTER TABLE `srv_userbase_respondents_lists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `srv_userbase_setting`
--
ALTER TABLE `srv_userbase_setting`
  ADD PRIMARY KEY (`ank_id`);

--
-- Indexes for table `srv_userstatus`
--
ALTER TABLE `srv_userstatus`
  ADD PRIMARY KEY (`usr_id`,`tip`);

--
-- Indexes for table `srv_user_grupa_active`
--
ALTER TABLE `srv_user_grupa_active`
  ADD PRIMARY KEY (`gru_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`);

--
-- Indexes for table `srv_user_grupa_archive1`
--
ALTER TABLE `srv_user_grupa_archive1`
  ADD PRIMARY KEY (`gru_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`);

--
-- Indexes for table `srv_user_grupa_archive2`
--
ALTER TABLE `srv_user_grupa_archive2`
  ADD PRIMARY KEY (`gru_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`);

--
-- Indexes for table `srv_user_session`
--
ALTER TABLE `srv_user_session`
  ADD PRIMARY KEY (`ank_id`,`usr_id`);

--
-- Indexes for table `srv_user_setting`
--
ALTER TABLE `srv_user_setting`
  ADD PRIMARY KEY (`usr_id`);

--
-- Indexes for table `srv_user_setting_for_survey`
--
ALTER TABLE `srv_user_setting_for_survey`
  ADD UNIQUE KEY `sid` (`sid`,`uid`,`what`);

--
-- Indexes for table `srv_user_setting_misc`
--
ALTER TABLE `srv_user_setting_misc`
  ADD UNIQUE KEY `uid` (`uid`,`what`);

--
-- Indexes for table `srv_validation`
--
ALTER TABLE `srv_validation`
  ADD PRIMARY KEY (`spr_id`,`if_id`),
  ADD KEY `fk_srv_validation_if_id` (`if_id`);

--
-- Indexes for table `srv_variable_profiles`
--
ALTER TABLE `srv_variable_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `srv_vrednost`
--
ALTER TABLE `srv_vrednost`
  ADD PRIMARY KEY (`id`),
  ADD KEY `spr_id` (`spr_id`),
  ADD KEY `if_id` (`if_id`),
  ADD KEY `vrednost_if` (`if_id`);

--
-- Indexes for table `srv_vrednost_map`
--
ALTER TABLE `srv_vrednost_map`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_srv_vrednost_map_vre_id` (`vre_id`),
  ADD KEY `fk_srv_vrednost_map_spr_id` (`spr_id`);

--
-- Indexes for table `srv_zanka_profiles`
--
ALTER TABLE `srv_zanka_profiles`
  ADD PRIMARY KEY (`id`,`sid`,`uid`),
  ADD UNIQUE KEY `id` (`id`,`sid`,`uid`,`name`),
  ADD KEY `fk_srv_zanka_profiles_sid` (`sid`);

--
-- Indexes for table `srv_zoom_profiles`
--
ALTER TABLE `srv_zoom_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `users_to_be`
--
ALTER TABLE `users_to_be`
  ADD KEY `id` (`id`),
  ADD KEY `fk_user_to_be_user_id` (`user_id`);

--
-- Indexes for table `user_access`
--
ALTER TABLE `user_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usr_id` (`usr_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `user_access_anketa`
--
ALTER TABLE `user_access_anketa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_access_anketa_usr_id` (`usr_id`),
  ADD KEY `fk_user_access_anketa_ank_id` (`ank_id`);

--
-- Indexes for table `user_access_narocilo`
--
ALTER TABLE `user_access_narocilo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_access_narocilo_usr_id` (`usr_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `user_access_paket`
--
ALTER TABLE `user_access_paket`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_access_paypal_transaction`
--
ALTER TABLE `user_access_paypal_transaction`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD UNIQUE KEY `narocilo_id` (`narocilo_id`);

--
-- Indexes for table `user_access_placilo`
--
ALTER TABLE `user_access_placilo`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_access_stripe_charge`
--
ALTER TABLE `user_access_stripe_charge`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_cronjob`
--
ALTER TABLE `user_cronjob`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usr_id` (`usr_id`);

--
-- Indexes for table `user_emails`
--
ALTER TABLE `user_emails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_emails_users_id` (`user_id`);

--
-- Indexes for table `user_login_tracker`
--
ALTER TABLE `user_login_tracker`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_options`
--
ALTER TABLE `user_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_options_users_id` (`user_id`);

--
-- Indexes for table `user_tracking`
--
ALTER TABLE `user_tracking`
  ADD KEY `datetime` (`datetime`,`user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `browser_notifications_respondents`
--
ALTER TABLE `browser_notifications_respondents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries_locations`
--
ALTER TABLE `countries_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fb_users`
--
ALTER TABLE `fb_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum`
--
ALTER TABLE `forum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_app_users`
--
ALTER TABLE `maza_app_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_srv_activity`
--
ALTER TABLE `maza_srv_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_srv_alarms`
--
ALTER TABLE `maza_srv_alarms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_srv_entry`
--
ALTER TABLE `maza_srv_entry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_srv_geofences`
--
ALTER TABLE `maza_srv_geofences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_srv_repeaters`
--
ALTER TABLE `maza_srv_repeaters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_srv_tracking`
--
ALTER TABLE `maza_srv_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_srv_triggered_activities`
--
ALTER TABLE `maza_srv_triggered_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_srv_triggered_geofences`
--
ALTER TABLE `maza_srv_triggered_geofences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_user_activity_recognition`
--
ALTER TABLE `maza_user_activity_recognition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maza_user_locations`
--
ALTER TABLE `maza_user_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post`
--
ALTER TABLE `post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_advanced_paradata_alert`
--
ALTER TABLE `srv_advanced_paradata_alert`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_advanced_paradata_movement`
--
ALTER TABLE `srv_advanced_paradata_movement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_advanced_paradata_other`
--
ALTER TABLE `srv_advanced_paradata_other`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_advanced_paradata_page`
--
ALTER TABLE `srv_advanced_paradata_page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_advanced_paradata_vrednost`
--
ALTER TABLE `srv_advanced_paradata_vrednost`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_analysis_archive`
--
ALTER TABLE `srv_analysis_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_anketa`
--
ALTER TABLE `srv_anketa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `srv_anketa_template`
--
ALTER TABLE `srv_anketa_template`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_calculation`
--
ALTER TABLE `srv_calculation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_call_history`
--
ALTER TABLE `srv_call_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_chart_skin`
--
ALTER TABLE `srv_chart_skin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_comment_resp`
--
ALTER TABLE `srv_comment_resp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_condition`
--
ALTER TABLE `srv_condition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_condition_profiles`
--
ALTER TABLE `srv_condition_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_custom_report`
--
ALTER TABLE `srv_custom_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_custom_report_profiles`
--
ALTER TABLE `srv_custom_report_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_datasetting_profile`
--
ALTER TABLE `srv_datasetting_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_heatmap`
--
ALTER TABLE `srv_data_heatmap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_imena`
--
ALTER TABLE `srv_data_imena`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_map`
--
ALTER TABLE `srv_data_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_random_blockContent`
--
ALTER TABLE `srv_data_random_blockContent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_random_spremenljivkaContent`
--
ALTER TABLE `srv_data_random_spremenljivkaContent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_text_active`
--
ALTER TABLE `srv_data_text_active`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_text_archive1`
--
ALTER TABLE `srv_data_text_archive1`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_text_archive2`
--
ALTER TABLE `srv_data_text_archive2`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_vrednost_cond`
--
ALTER TABLE `srv_data_vrednost_cond`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_evoli_teammeter`
--
ALTER TABLE `srv_evoli_teammeter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_evoli_teammeter_delayed`
--
ALTER TABLE `srv_evoli_teammeter_delayed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_evoli_teammeter_department`
--
ALTER TABLE `srv_evoli_teammeter_department`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_fieldwork`
--
ALTER TABLE `srv_fieldwork`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_folder`
--
ALTER TABLE `srv_folder`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `srv_gdpr_requests`
--
ALTER TABLE `srv_gdpr_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_grupa`
--
ALTER TABLE `srv_grupa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `srv_hierarhija_options`
--
ALTER TABLE `srv_hierarhija_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_hierarhija_ravni`
--
ALTER TABLE `srv_hierarhija_ravni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_hierarhija_shrani`
--
ALTER TABLE `srv_hierarhija_shrani`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_hierarhija_sifranti`
--
ALTER TABLE `srv_hierarhija_sifranti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_hierarhija_struktura`
--
ALTER TABLE `srv_hierarhija_struktura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_hierarhija_users`
--
ALTER TABLE `srv_hierarhija_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_hotspot_regions`
--
ALTER TABLE `srv_hotspot_regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_if`
--
ALTER TABLE `srv_if`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_invitations_archive`
--
ALTER TABLE `srv_invitations_archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_invitations_messages`
--
ALTER TABLE `srv_invitations_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_invitations_recipients`
--
ALTER TABLE `srv_invitations_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_invitations_recipients_profiles`
--
ALTER TABLE `srv_invitations_recipients_profiles`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_invitations_tracking`
--
ALTER TABLE `srv_invitations_tracking`
  MODIFY `uniq` mediumint(9) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_library_folder`
--
ALTER TABLE `srv_library_folder`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `srv_loop_data`
--
ALTER TABLE `srv_loop_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_mc_element`
--
ALTER TABLE `srv_mc_element`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_mc_table`
--
ALTER TABLE `srv_mc_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_missing_profiles`
--
ALTER TABLE `srv_missing_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_module`
--
ALTER TABLE `srv_module`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `srv_mysurvey_folder`
--
ALTER TABLE `srv_mysurvey_folder`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_nice_links`
--
ALTER TABLE `srv_nice_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `srv_nice_links_skupine`
--
ALTER TABLE `srv_nice_links_skupine`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_notifications`
--
ALTER TABLE `srv_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_notifications_messages`
--
ALTER TABLE `srv_notifications_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_panel_if`
--
ALTER TABLE `srv_panel_if`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_parapodatki`
--
ALTER TABLE `srv_parapodatki`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `srv_profile_manager`
--
ALTER TABLE `srv_profile_manager`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_quota`
--
ALTER TABLE `srv_quota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_respondent_profiles`
--
ALTER TABLE `srv_respondent_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_simple_mail_invitation`
--
ALTER TABLE `srv_simple_mail_invitation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_spremenljivka`
--
ALTER TABLE `srv_spremenljivka`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9709;

--
-- AUTO_INCREMENT for table `srv_squalo_anketa`
--
ALTER TABLE `srv_squalo_anketa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_statistic_profile`
--
ALTER TABLE `srv_statistic_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_status_casi`
--
ALTER TABLE `srv_status_casi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `srv_status_profile`
--
ALTER TABLE `srv_status_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `srv_survey_unsubscribe`
--
ALTER TABLE `srv_survey_unsubscribe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_sys_filters`
--
ALTER TABLE `srv_sys_filters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `srv_telephone_history`
--
ALTER TABLE `srv_telephone_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_theme_profiles`
--
ALTER TABLE `srv_theme_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_theme_profiles_mobile`
--
ALTER TABLE `srv_theme_profiles_mobile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_time_profile`
--
ALTER TABLE `srv_time_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_user`
--
ALTER TABLE `srv_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `srv_userbase_invitations`
--
ALTER TABLE `srv_userbase_invitations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `srv_userbase_respondents`
--
ALTER TABLE `srv_userbase_respondents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_userbase_respondents_lists`
--
ALTER TABLE `srv_userbase_respondents_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_variable_profiles`
--
ALTER TABLE `srv_variable_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_vrednost`
--
ALTER TABLE `srv_vrednost`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29026;

--
-- AUTO_INCREMENT for table `srv_vrednost_map`
--
ALTER TABLE `srv_vrednost_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_zoom_profiles`
--
ALTER TABLE `srv_zoom_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1046;

--
-- AUTO_INCREMENT for table `users_to_be`
--
ALTER TABLE `users_to_be`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_access`
--
ALTER TABLE `user_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_access_anketa`
--
ALTER TABLE `user_access_anketa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_access_narocilo`
--
ALTER TABLE `user_access_narocilo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_access_paket`
--
ALTER TABLE `user_access_paket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_access_paypal_transaction`
--
ALTER TABLE `user_access_paypal_transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_access_placilo`
--
ALTER TABLE `user_access_placilo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_access_stripe_charge`
--
ALTER TABLE `user_access_stripe_charge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_cronjob`
--
ALTER TABLE `user_cronjob`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_emails`
--
ALTER TABLE `user_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_login_tracker`
--
ALTER TABLE `user_login_tracker`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_options`
--
ALTER TABLE `user_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `maza_srv_activity`
--
ALTER TABLE `maza_srv_activity`
  ADD CONSTRAINT `fk_maza_activity_srv_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_srv_alarms`
--
ALTER TABLE `maza_srv_alarms`
  ADD CONSTRAINT `fk_maza_srv_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_srv_entry`
--
ALTER TABLE `maza_srv_entry`
  ADD CONSTRAINT `fk_entry_srv_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_srv_geofences`
--
ALTER TABLE `maza_srv_geofences`
  ADD CONSTRAINT `fk_maza_geofences_srv_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_srv_repeaters`
--
ALTER TABLE `maza_srv_repeaters`
  ADD CONSTRAINT `fk_maza_repeater_srv_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_srv_tracking`
--
ALTER TABLE `maza_srv_tracking`
  ADD CONSTRAINT `fk_maza_tracking_srv_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_srv_triggered_activities`
--
ALTER TABLE `maza_srv_triggered_activities`
  ADD CONSTRAINT `fk_maza_srv_triggered_activities_act_id` FOREIGN KEY (`act_id`) REFERENCES `maza_srv_activity` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maza_srv_triggered_activities_maza_user_id` FOREIGN KEY (`maza_user_id`) REFERENCES `maza_app_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_srv_triggered_geofences`
--
ALTER TABLE `maza_srv_triggered_geofences`
  ADD CONSTRAINT `fk_maza_srv_triggered_geofences_geof_id` FOREIGN KEY (`geof_id`) REFERENCES `maza_srv_geofences` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maza_srv_triggered_geofences_maza_user_id` FOREIGN KEY (`maza_user_id`) REFERENCES `maza_app_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_srv_users`
--
ALTER TABLE `maza_srv_users`
  ADD CONSTRAINT `fk_maza_app_users_maza_srv_users` FOREIGN KEY (`maza_user_id`) REFERENCES `maza_app_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maza_srv_users_loc_id` FOREIGN KEY (`loc_id`) REFERENCES `maza_user_locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maza_srv_users_tact_id` FOREIGN KEY (`tact_id`) REFERENCES `maza_srv_triggered_activities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maza_srv_users_tgeof_id` FOREIGN KEY (`tgeof_id`) REFERENCES `maza_srv_triggered_geofences` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_user_maza_srv_users` FOREIGN KEY (`srv_user_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_survey`
--
ALTER TABLE `maza_survey`
  ADD CONSTRAINT `fk_srv_id_maza_survey` FOREIGN KEY (`srv_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_user_activity_recognition`
--
ALTER TABLE `maza_user_activity_recognition`
  ADD CONSTRAINT `fk_maza_user_activity_recognition_user_id` FOREIGN KEY (`maza_user_id`) REFERENCES `maza_app_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_user_locations`
--
ALTER TABLE `maza_user_locations`
  ADD CONSTRAINT `fk_maza_user_locations_tgeof_id` FOREIGN KEY (`tgeof_id`) REFERENCES `maza_srv_triggered_geofences` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maza_user_locations_user_id` FOREIGN KEY (`maza_user_id`) REFERENCES `maza_app_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maza_user_srv_access`
--
ALTER TABLE `maza_user_srv_access`
  ADD CONSTRAINT `fk_maza_app_users_maza_user_srv_access` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_anketa_maza_user_srv_access` FOREIGN KEY (`maza_user_id`) REFERENCES `maza_app_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `restrict_fk_srv_anketa`
--
ALTER TABLE `restrict_fk_srv_anketa`
  ADD CONSTRAINT `restrict_fk_srv_anketa_ibfk_1` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `restrict_fk_srv_grupa`
--
ALTER TABLE `restrict_fk_srv_grupa`
  ADD CONSTRAINT `restrict_fk_srv_grupa_ibfk_1` FOREIGN KEY (`gru_id`) REFERENCES `srv_grupa` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `restrict_fk_srv_if`
--
ALTER TABLE `restrict_fk_srv_if`
  ADD CONSTRAINT `restrict_fk_srv_if_ibfk_1` FOREIGN KEY (`if_id`) REFERENCES `srv_if` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `restrict_fk_srv_spremenljivka`
--
ALTER TABLE `restrict_fk_srv_spremenljivka`
  ADD CONSTRAINT `restrict_fk_srv_spremenljivka_ibfk_1` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `restrict_fk_srv_vrednost`
--
ALTER TABLE `restrict_fk_srv_vrednost`
  ADD CONSTRAINT `restrict_fk_srv_vrednost_ibfk_1` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `srv_activity`
--
ALTER TABLE `srv_activity`
  ADD CONSTRAINT `fk_srv_activity_sid` FOREIGN KEY (`sid`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_advanced_paradata_alert`
--
ALTER TABLE `srv_advanced_paradata_alert`
  ADD CONSTRAINT `fk_srv_advanced_paradata_alert_page_id` FOREIGN KEY (`page_id`) REFERENCES `srv_advanced_paradata_page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_advanced_paradata_movement`
--
ALTER TABLE `srv_advanced_paradata_movement`
  ADD CONSTRAINT `fk_srv_advanced_paradata_movement_page_id` FOREIGN KEY (`page_id`) REFERENCES `srv_advanced_paradata_page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_advanced_paradata_other`
--
ALTER TABLE `srv_advanced_paradata_other`
  ADD CONSTRAINT `fk_srv_advanced_paradata_other_page_id` FOREIGN KEY (`page_id`) REFERENCES `srv_advanced_paradata_page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_advanced_paradata_page`
--
ALTER TABLE `srv_advanced_paradata_page`
  ADD CONSTRAINT `fk_srv_advanced_paradata_page_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_advanced_paradata_question`
--
ALTER TABLE `srv_advanced_paradata_question`
  ADD CONSTRAINT `fk_srv_advanced_paradata_question_page_id` FOREIGN KEY (`page_id`) REFERENCES `srv_advanced_paradata_page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_advanced_paradata_question_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_advanced_paradata_settings`
--
ALTER TABLE `srv_advanced_paradata_settings`
  ADD CONSTRAINT `fk_srv_advanced_paradata_settings_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_advanced_paradata_vrednost`
--
ALTER TABLE `srv_advanced_paradata_vrednost`
  ADD CONSTRAINT `fk_srv_advanced_paradata_vrednost_page_id` FOREIGN KEY (`page_id`) REFERENCES `srv_advanced_paradata_page` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_alert`
--
ALTER TABLE `srv_alert`
  ADD CONSTRAINT `fk_srv_alert_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_alert_ibfk_1` FOREIGN KEY (`finish_respondent_if`) REFERENCES `srv_if` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_alert_ibfk_2` FOREIGN KEY (`finish_respondent_cms_if`) REFERENCES `srv_if` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_alert_ibfk_3` FOREIGN KEY (`finish_other_if`) REFERENCES `srv_if` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `srv_alert_custom`
--
ALTER TABLE `srv_alert_custom`
  ADD CONSTRAINT `srv_alert_custom_ibfk_1` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_analysis_archive`
--
ALTER TABLE `srv_analysis_archive`
  ADD CONSTRAINT `fk_srv_analysis_archive_sid` FOREIGN KEY (`sid`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_anketa`
--
ALTER TABLE `srv_anketa`
  ADD CONSTRAINT `fk_srv_anketa_folder` FOREIGN KEY (`folder`) REFERENCES `srv_folder` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `srv_anketa_module`
--
ALTER TABLE `srv_anketa_module`
  ADD CONSTRAINT `fk_srv_anketa_module_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_branching`
--
ALTER TABLE `srv_branching`
  ADD CONSTRAINT `fk_srv_branching_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_calculation`
--
ALTER TABLE `srv_calculation`
  ADD CONSTRAINT `fk_srv_calculation_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_calculation_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_call_current`
--
ALTER TABLE `srv_call_current`
  ADD CONSTRAINT `fk_srv_call_current_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_call_history`
--
ALTER TABLE `srv_call_history`
  ADD CONSTRAINT `fk_srv_call_history_survey_id` FOREIGN KEY (`survey_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_call_history_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_call_schedule`
--
ALTER TABLE `srv_call_schedule`
  ADD CONSTRAINT `fk_srv_call_schedule_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_call_setting`
--
ALTER TABLE `srv_call_setting`
  ADD CONSTRAINT `fk_srv_call_setting_survey_id` FOREIGN KEY (`survey_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_captcha`
--
ALTER TABLE `srv_captcha`
  ADD CONSTRAINT `srv_captcha_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_captcha_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_captcha_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_chat_settings`
--
ALTER TABLE `srv_chat_settings`
  ADD CONSTRAINT `fk_srv_chat_settings_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_clicks`
--
ALTER TABLE `srv_clicks`
  ADD CONSTRAINT `fk_srv_clicks_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_condition`
--
ALTER TABLE `srv_condition`
  ADD CONSTRAINT `fk_srv_condition_if_id` FOREIGN KEY (`if_id`) REFERENCES `srv_if` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_condition_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_condition_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_condition_grid`
--
ALTER TABLE `srv_condition_grid`
  ADD CONSTRAINT `fk_srv_condition_grid_cond_id` FOREIGN KEY (`cond_id`) REFERENCES `srv_condition` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_condition_profiles`
--
ALTER TABLE `srv_condition_profiles`
  ADD CONSTRAINT `srv_condition_profiles_ibfk_1` FOREIGN KEY (`sid`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_condition_profiles_ibfk_2` FOREIGN KEY (`if_id`) REFERENCES `srv_if` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_condition_vre`
--
ALTER TABLE `srv_condition_vre`
  ADD CONSTRAINT `fk_srv_condition_vre_cond_id` FOREIGN KEY (`cond_id`) REFERENCES `srv_condition` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_condition_vre_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_checkgrid_archive1`
--
ALTER TABLE `srv_data_checkgrid_archive1`
  ADD CONSTRAINT `fk_srv_data_checkgrid_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_checkgrid_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_checkgrid_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_checkgrid_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_files`
--
ALTER TABLE `srv_data_files`
  ADD CONSTRAINT `fk_srv_data_files_sid` FOREIGN KEY (`sid`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_glasovanje`
--
ALTER TABLE `srv_data_glasovanje`
  ADD CONSTRAINT `fk_srv_data_glasovanje_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_glasovanje_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_grid_active`
--
ALTER TABLE `srv_data_grid_active`
  ADD CONSTRAINT `fk_srv_data_grid_active_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_grid_active_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_grid_active_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_grid_archive1`
--
ALTER TABLE `srv_data_grid_archive1`
  ADD CONSTRAINT `fk_srv_data_grid_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_grid_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_grid_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_heatmap`
--
ALTER TABLE `srv_data_heatmap`
  ADD CONSTRAINT `fk_srv_data_heatmap_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_heatmap_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_heatmap_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_heatmap_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_imena`
--
ALTER TABLE `srv_data_imena`
  ADD CONSTRAINT `fk_srv_data_imena_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_imena_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_map`
--
ALTER TABLE `srv_data_map`
  ADD CONSTRAINT `fk_srv_data_map_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_map_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_map_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_map_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_number`
--
ALTER TABLE `srv_data_number`
  ADD CONSTRAINT `fk_srv_data_number_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_number_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_number_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_number_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_random_blockContent`
--
ALTER TABLE `srv_data_random_blockContent`
  ADD CONSTRAINT `fk_srv_data_random_blockContent_block_id` FOREIGN KEY (`block_id`) REFERENCES `srv_if` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_random_blockContent_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_random_spremenljivkaContent`
--
ALTER TABLE `srv_data_random_spremenljivkaContent`
  ADD CONSTRAINT `fk_srv_data_random_spremenljivkaContent_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_random_spremenljivkaContent_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_rating`
--
ALTER TABLE `srv_data_rating`
  ADD CONSTRAINT `fk_srv_data_rating_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_rating_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_rating_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_textgrid_archive1`
--
ALTER TABLE `srv_data_textgrid_archive1`
  ADD CONSTRAINT `fk_srv_data_textgrid_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_textgrid_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_textgrid_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_textgrid_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_text_archive1`
--
ALTER TABLE `srv_data_text_archive1`
  ADD CONSTRAINT `fk_srv_data_text_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_text_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_text_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_upload`
--
ALTER TABLE `srv_data_upload`
  ADD CONSTRAINT `srv_data_upload_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_data_upload_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_vrednost_active`
--
ALTER TABLE `srv_data_vrednost_active`
  ADD CONSTRAINT `fk_srv_data_vrednost_active_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_active_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_active_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_active_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_vrednost_archive1`
--
ALTER TABLE `srv_data_vrednost_archive1`
  ADD CONSTRAINT `fk_srv_data_vrednost_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_vrednost_cond`
--
ALTER TABLE `srv_data_vrednost_cond`
  ADD CONSTRAINT `fk_srv_data_vrednost_cond_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_cond_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_cond_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_dostop`
--
ALTER TABLE `srv_dostop`
  ADD CONSTRAINT `fk_srv_dostop_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_dostop_ibfk_1` FOREIGN KEY (`alert_complete_if`) REFERENCES `srv_if` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `srv_dostop_language`
--
ALTER TABLE `srv_dostop_language`
  ADD CONSTRAINT `fk_srv_dostop_language_ank_id_lang_id` FOREIGN KEY (`ank_id`,`lang_id`) REFERENCES `srv_language` (`ank_id`, `lang_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_dostop_language_ank_id_uid` FOREIGN KEY (`ank_id`,`uid`) REFERENCES `srv_dostop` (`ank_id`, `uid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_evoli_landingpage_access`
--
ALTER TABLE `srv_evoli_landingpage_access`
  ADD CONSTRAINT `fk_srv_evoli_landingPage_access_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_evoli_teammeter`
--
ALTER TABLE `srv_evoli_teammeter`
  ADD CONSTRAINT `fk_srv_evoli_teammeter_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_evoli_teammeter_skupina_id` FOREIGN KEY (`skupina_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_evoli_teammeter_data_department`
--
ALTER TABLE `srv_evoli_teammeter_data_department`
  ADD CONSTRAINT `fk_srv_evoli_data_department_department_id` FOREIGN KEY (`department_id`) REFERENCES `srv_evoli_teammeter_department` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_evoli_data_department_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_evoli_teammeter_delayed`
--
ALTER TABLE `srv_evoli_teammeter_delayed`
  ADD CONSTRAINT `fk_srv_evoli_teammeter_delayed_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_evoli_teammeter_department`
--
ALTER TABLE `srv_evoli_teammeter_department`
  ADD CONSTRAINT `fk_srv_evoli_teammeter_department_tm_id` FOREIGN KEY (`tm_id`) REFERENCES `srv_evoli_teammeter` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_gdpr_anketa`
--
ALTER TABLE `srv_gdpr_anketa`
  ADD CONSTRAINT `fk_srv_gdpr_anketa_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_gdpr_requests`
--
ALTER TABLE `srv_gdpr_requests`
  ADD CONSTRAINT `fk_srv_gdpr_requests_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_srv_gdpr_requests_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_gdpr_user`
--
ALTER TABLE `srv_gdpr_user`
  ADD CONSTRAINT `fk_srv_gdpr_user_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_glasovanje`
--
ALTER TABLE `srv_glasovanje`
  ADD CONSTRAINT `fk_srv_glasovanje_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_glasovanje_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_grid`
--
ALTER TABLE `srv_grid`
  ADD CONSTRAINT `fk_srv_grid_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_grid_multiple`
--
ALTER TABLE `srv_grid_multiple`
  ADD CONSTRAINT `srv_grid_multiple_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_grid_multiple_parent` FOREIGN KEY (`parent`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_grid_multiple_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_grupa`
--
ALTER TABLE `srv_grupa`
  ADD CONSTRAINT `fk_srv_grupa_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_hash_url`
--
ALTER TABLE `srv_hash_url`
  ADD CONSTRAINT `FK_srv_hash_url` FOREIGN KEY (`anketa`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_hierarhija_koda`
--
ALTER TABLE `srv_hierarhija_koda`
  ADD CONSTRAINT `srv_hierarhija_koda_ibfk_1` FOREIGN KEY (`anketa_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `srv_hierarhija_koda_ibfk_2` FOREIGN KEY (`hierarhija_struktura_id`) REFERENCES `srv_hierarhija_struktura` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `srv_hierarhija_koda_ibfk_3` FOREIGN KEY (`srv_user_id`) REFERENCES `srv_user` (`id`);

--
-- Constraints for table `srv_hierarhija_options`
--
ALTER TABLE `srv_hierarhija_options`
  ADD CONSTRAINT `srv_hierarhija_options_ibfk_1` FOREIGN KEY (`anketa_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hierarhija_ravni`
--
ALTER TABLE `srv_hierarhija_ravni`
  ADD CONSTRAINT `srv_hierarhija_ravni_ibfk_1` FOREIGN KEY (`anketa_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hierarhija_shrani`
--
ALTER TABLE `srv_hierarhija_shrani`
  ADD CONSTRAINT `srv_hierarhija_shrani_ibfk_1` FOREIGN KEY (`anketa_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hierarhija_sifranti`
--
ALTER TABLE `srv_hierarhija_sifranti`
  ADD CONSTRAINT `srv_hierarhija_sifranti_ibfk_1` FOREIGN KEY (`hierarhija_ravni_id`) REFERENCES `srv_hierarhija_ravni` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hierarhija_sifrant_vrednost`
--
ALTER TABLE `srv_hierarhija_sifrant_vrednost`
  ADD CONSTRAINT `srv_hierarhija_sifrant_vrednost_ibfk_1` FOREIGN KEY (`sifrant_id`) REFERENCES `srv_hierarhija_sifranti` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `srv_hierarhija_sifrant_vrednost_ibfk_2` FOREIGN KEY (`vrednost_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hierarhija_struktura`
--
ALTER TABLE `srv_hierarhija_struktura`
  ADD CONSTRAINT `srv_hierarhija_struktura_ibfk_1` FOREIGN KEY (`hierarhija_ravni_id`) REFERENCES `srv_hierarhija_ravni` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `srv_hierarhija_struktura_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `srv_hierarhija_struktura` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `srv_hierarhija_struktura_ibfk_3` FOREIGN KEY (`hierarhija_sifranti_id`) REFERENCES `srv_hierarhija_sifranti` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `srv_hierarhija_struktura_ibfk_4` FOREIGN KEY (`anketa_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hierarhija_struktura_users`
--
ALTER TABLE `srv_hierarhija_struktura_users`
  ADD CONSTRAINT `srv_hierarhija_struktura_users_ibfk_1` FOREIGN KEY (`hierarhija_struktura_id`) REFERENCES `srv_hierarhija_struktura` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hierarhija_supersifra`
--
ALTER TABLE `srv_hierarhija_supersifra`
  ADD CONSTRAINT `srv_hierarhija_supersifra_ibfk_1` FOREIGN KEY (`anketa_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hierarhija_supersifra_resevanje`
--
ALTER TABLE `srv_hierarhija_supersifra_resevanje`
  ADD CONSTRAINT `srv_hierarhija_supersifra_resevanje_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `srv_hierarhija_supersifra_resevanje_ibfk_2` FOREIGN KEY (`supersifra`) REFERENCES `srv_hierarhija_supersifra` (`koda`) ON DELETE CASCADE,
  ADD CONSTRAINT `srv_hierarhija_supersifra_resevanje_ibfk_3` FOREIGN KEY (`koda`) REFERENCES `srv_hierarhija_koda` (`koda`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hierarhija_users`
--
ALTER TABLE `srv_hierarhija_users`
  ADD CONSTRAINT `srv_hierarhija_users_ibfk_1` FOREIGN KEY (`anketa_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `srv_hotspot_regions`
--
ALTER TABLE `srv_hotspot_regions`
  ADD CONSTRAINT `fk_srv_hotspot_regions_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_hotspot_regions_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_invitations_archive`
--
ALTER TABLE `srv_invitations_archive`
  ADD CONSTRAINT `srv_invitations_archive_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_invitations_archive_recipients`
--
ALTER TABLE `srv_invitations_archive_recipients`
  ADD CONSTRAINT `srv_invitations_archive_recipients_arch_id` FOREIGN KEY (`arch_id`) REFERENCES `srv_invitations_archive` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_invitations_archive_recipients_rec_id` FOREIGN KEY (`rec_id`) REFERENCES `srv_invitations_recipients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_invitations_messages`
--
ALTER TABLE `srv_invitations_messages`
  ADD CONSTRAINT `srv_invitations_messages_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_invitations_recipients`
--
ALTER TABLE `srv_invitations_recipients`
  ADD CONSTRAINT `srv_invitations_recipients_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_invitations_tracking`
--
ALTER TABLE `srv_invitations_tracking`
  ADD CONSTRAINT `srv_invitations_tracking_arch_id` FOREIGN KEY (`inv_arch_id`) REFERENCES `srv_invitations_archive` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_invitations_tracking_res_id` FOREIGN KEY (`res_id`) REFERENCES `srv_invitations_recipients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_language`
--
ALTER TABLE `srv_language`
  ADD CONSTRAINT `fk_srv_language_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_language_grid`
--
ALTER TABLE `srv_language_grid`
  ADD CONSTRAINT `fk_srv_language_grid_ank_id_lang_id` FOREIGN KEY (`ank_id`,`lang_id`) REFERENCES `srv_language` (`ank_id`, `lang_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_language_grid_spr_id_grd_id` FOREIGN KEY (`spr_id`,`grd_id`) REFERENCES `srv_grid` (`spr_id`, `id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_language_slider`
--
ALTER TABLE `srv_language_slider`
  ADD CONSTRAINT `fk_srv_language_slider_ank_id_lang_id` FOREIGN KEY (`ank_id`,`lang_id`) REFERENCES `srv_language` (`ank_id`, `lang_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_language_slider_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_language_spremenljivka`
--
ALTER TABLE `srv_language_spremenljivka`
  ADD CONSTRAINT `fk_srv_language_spremenljivka_ank_id_lang_id` FOREIGN KEY (`ank_id`,`lang_id`) REFERENCES `srv_language` (`ank_id`, `lang_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_language_spremenljivka_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_language_vrednost`
--
ALTER TABLE `srv_language_vrednost`
  ADD CONSTRAINT `fk_srv_language_vrednost_ank_id_lang_id` FOREIGN KEY (`ank_id`,`lang_id`) REFERENCES `srv_language` (`ank_id`, `lang_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_language_vrednost_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_library_anketa`
--
ALTER TABLE `srv_library_anketa`
  ADD CONSTRAINT `fk_srv_library_anketa_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_loop`
--
ALTER TABLE `srv_loop`
  ADD CONSTRAINT `fk_srv_loop_if_id` FOREIGN KEY (`if_id`) REFERENCES `srv_if` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_loop_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_loop_data`
--
ALTER TABLE `srv_loop_data`
  ADD CONSTRAINT `fk_srv_loop_data_if_id_vre_id` FOREIGN KEY (`if_id`,`vre_id`) REFERENCES `srv_loop_vre` (`if_id`, `vre_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_loop_vre`
--
ALTER TABLE `srv_loop_vre`
  ADD CONSTRAINT `fk_srv_loop_vre_if_id` FOREIGN KEY (`if_id`) REFERENCES `srv_loop` (`if_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_loop_vre_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_mc_element`
--
ALTER TABLE `srv_mc_element`
  ADD CONSTRAINT `srv_mc_element_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `srv_mc_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_missing_profiles_values`
--
ALTER TABLE `srv_missing_profiles_values`
  ADD CONSTRAINT `fk_srv_missing_profiles_id` FOREIGN KEY (`missing_pid`) REFERENCES `srv_missing_profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_missing_values`
--
ALTER TABLE `srv_missing_values`
  ADD CONSTRAINT `fk_srv_missing_values_sid` FOREIGN KEY (`sid`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_nice_links`
--
ALTER TABLE `srv_nice_links`
  ADD CONSTRAINT `srv_nice_links_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_panel_if`
--
ALTER TABLE `srv_panel_if`
  ADD CONSTRAINT `fk_srv_panel_if_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_panel_if_if_id` FOREIGN KEY (`if_id`) REFERENCES `srv_if` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_panel_settings`
--
ALTER TABLE `srv_panel_settings`
  ADD CONSTRAINT `fk_srv_panel_settings_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_parapodatki`
--
ALTER TABLE `srv_parapodatki`
  ADD CONSTRAINT `fk_srv_parapodatki_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_parapodatki_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_password`
--
ALTER TABLE `srv_password`
  ADD CONSTRAINT `srv_password_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_quiz_settings`
--
ALTER TABLE `srv_quiz_settings`
  ADD CONSTRAINT `fk_srv_quiz_settings_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_quiz_vrednost`
--
ALTER TABLE `srv_quiz_vrednost`
  ADD CONSTRAINT `fk_srv_quiz_vrednost_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_quiz_vrednost_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_respondents`
--
ALTER TABLE `srv_respondents`
  ADD CONSTRAINT `fk_srv_respondents_pid` FOREIGN KEY (`pid`) REFERENCES `srv_respondent_profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_slideshow_settings`
--
ALTER TABLE `srv_slideshow_settings`
  ADD CONSTRAINT `srv_slideshow_settings_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_specialdata_vrednost`
--
ALTER TABLE `srv_specialdata_vrednost`
  ADD CONSTRAINT `fk_srv_specialdata_vrednost_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_specialdata_vrednost_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_specialdata_vrednost_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_spremenljivka`
--
ALTER TABLE `srv_spremenljivka`
  ADD CONSTRAINT `fk_srv_spremenljivka_gru_id` FOREIGN KEY (`gru_id`) REFERENCES `srv_grupa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_spremenljivka_tracking`
--
ALTER TABLE `srv_spremenljivka_tracking`
  ADD CONSTRAINT `srv_spremenljivka_tracking_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_spremenljivka_tracking_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_spremenljivka_tracking_tracking_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_squalo_anketa`
--
ALTER TABLE `srv_squalo_anketa`
  ADD CONSTRAINT `fk_srv_squalo_anketa_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_survey_conditions`
--
ALTER TABLE `srv_survey_conditions`
  ADD CONSTRAINT `srv_survey_conditions_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_survey_conditions_if_id` FOREIGN KEY (`if_id`) REFERENCES `srv_if` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_survey_list`
--
ALTER TABLE `srv_survey_list`
  ADD CONSTRAINT `srv_survey_list_id` FOREIGN KEY (`id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_survey_misc`
--
ALTER TABLE `srv_survey_misc`
  ADD CONSTRAINT `fk_srv_survey_misc_sid` FOREIGN KEY (`sid`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_survey_unsubscribe`
--
ALTER TABLE `srv_survey_unsubscribe`
  ADD CONSTRAINT `srv_survey_unsubscribe_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_theme_editor`
--
ALTER TABLE `srv_theme_editor`
  ADD CONSTRAINT `fk_srv_theme_editor_profile_id` FOREIGN KEY (`profile_id`) REFERENCES `srv_theme_profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_theme_editor_mobile`
--
ALTER TABLE `srv_theme_editor_mobile`
  ADD CONSTRAINT `fk_srv_theme_editor_mobile_profile_id` FOREIGN KEY (`profile_id`) REFERENCES `srv_theme_profiles_mobile` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_user`
--
ALTER TABLE `srv_user`
  ADD CONSTRAINT `fk_srv_user_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_userbase`
--
ALTER TABLE `srv_userbase`
  ADD CONSTRAINT `fk_srv_userbase_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_userbase_respondents`
--
ALTER TABLE `srv_userbase_respondents`
  ADD CONSTRAINT `fk_srv_userbase_respondents_list_id` FOREIGN KEY (`list_id`) REFERENCES `srv_userbase_respondents_lists` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_userbase_setting`
--
ALTER TABLE `srv_userbase_setting`
  ADD CONSTRAINT `fk_srv_userbase_setting_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_userstatus`
--
ALTER TABLE `srv_userstatus`
  ADD CONSTRAINT `fk_srv_userstatus_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_user_grupa_active`
--
ALTER TABLE `srv_user_grupa_active`
  ADD CONSTRAINT `fk_srv_user_grupa_active_gru_id` FOREIGN KEY (`gru_id`) REFERENCES `srv_grupa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_user_grupa_active_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_user_grupa_archive1`
--
ALTER TABLE `srv_user_grupa_archive1`
  ADD CONSTRAINT `fk_srv_user_grupa_gru_id` FOREIGN KEY (`gru_id`) REFERENCES `srv_grupa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_user_grupa_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_user_setting_for_survey`
--
ALTER TABLE `srv_user_setting_for_survey`
  ADD CONSTRAINT `fk_srv_user_setting_for_survey_sid` FOREIGN KEY (`sid`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_validation`
--
ALTER TABLE `srv_validation`
  ADD CONSTRAINT `fk_srv_validation_if_id` FOREIGN KEY (`if_id`) REFERENCES `srv_if` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_validation_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_vrednost`
--
ALTER TABLE `srv_vrednost`
  ADD CONSTRAINT `fk_srv_vrednost_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_vrednost_map`
--
ALTER TABLE `srv_vrednost_map`
  ADD CONSTRAINT `fk_srv_vrednost_map_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_vrednost_map_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_zanka_profiles`
--
ALTER TABLE `srv_zanka_profiles`
  ADD CONSTRAINT `fk_srv_zanka_profiles_sid` FOREIGN KEY (`sid`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_access`
--
ALTER TABLE `user_access`
  ADD CONSTRAINT `fk_user_access_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_access_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `user_access_paket` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_access_anketa`
--
ALTER TABLE `user_access_anketa`
  ADD CONSTRAINT `fk_user_access_anketa_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_access_anketa_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_access_narocilo`
--
ALTER TABLE `user_access_narocilo`
  ADD CONSTRAINT `fk_user_access_narocilo_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_access_narocilo_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `user_access_paket` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_cronjob`
--
ALTER TABLE `user_cronjob`
  ADD CONSTRAINT `fk_user_cronjob_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_emails`
--
ALTER TABLE `user_emails`
  ADD CONSTRAINT `fk_user_emails_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_options`
--
ALTER TABLE `user_options`
  ADD CONSTRAINT `fk_user_options_users_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
