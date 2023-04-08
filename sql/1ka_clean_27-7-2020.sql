-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Generation Time: Jul 27, 2020 at 09:13 AM
-- Server version: 5.6.40
-- PHP Version: 7.2.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
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
  `menu` int(10) NOT NULL DEFAULT '0',
  `clan` int(10) NOT NULL DEFAULT '0'
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
  `uid` int(11) NOT NULL DEFAULT '0',
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
  `parent` int(11) NOT NULL DEFAULT '0',
  `ord` int(11) NOT NULL DEFAULT '0',
  `naslov` varchar(255) NOT NULL DEFAULT '',
  `opis` tinytext NOT NULL,
  `display` int(11) NOT NULL DEFAULT '1',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `click` tinyint(1) NOT NULL DEFAULT '1',
  `thread` tinyint(4) NOT NULL DEFAULT '1',
  `user` int(11) NOT NULL DEFAULT '0',
  `clan` int(11) NOT NULL DEFAULT '0',
  `admin` int(11) NOT NULL DEFAULT '0',
  `ocena` int(11) NOT NULL DEFAULT '0',
  `lockedauth` int(11) NOT NULL DEFAULT '0',
  `NiceLink` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `manager`
--

CREATE TABLE `manager` (
  `menu` int(10) NOT NULL DEFAULT '0',
  `manager` int(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `manager_clan`
--

CREATE TABLE `manager_clan` (
  `manager` int(10) NOT NULL DEFAULT '0',
  `clan` int(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `maza_app_users`
--

CREATE TABLE `maza_app_users` (
  `id` int(11) NOT NULL,
  `identifier` varchar(16) NOT NULL,
  `registration_id` varchar(255) DEFAULT NULL,
  `datetime_inserted` datetime DEFAULT CURRENT_TIMESTAMP,
  `datetime_last_active` datetime DEFAULT NULL,
  `deviceInfo` text,
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
  `activity_on` tinyint(1) DEFAULT '0',
  `notif_title` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `notif_message` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `notif_sound` tinyint(1) DEFAULT '0',
  `activity_type` varchar(30) DEFAULT 'path',
  `after_seconds` int(11) DEFAULT '300'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_alarms`
--

CREATE TABLE `maza_srv_alarms` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `alarm_on` tinyint(1) DEFAULT '0',
  `alarm_notif_title` varchar(100) DEFAULT '',
  `alarm_notif_message` varchar(100) DEFAULT '',
  `repeat_by` varchar(30) DEFAULT 'everyday',
  `alarm_notif_sound` tinyint(1) DEFAULT '0',
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
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `entry_on` tinyint(1) DEFAULT '0',
  `location_check` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_geofences`
--

CREATE TABLE `maza_srv_geofences` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `geofence_on` tinyint(1) DEFAULT '0',
  `lat` float(19,15) NOT NULL,
  `lng` float(19,15) NOT NULL,
  `radius` float(21,13) NOT NULL,
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `name` varchar(100) DEFAULT NULL,
  `notif_title` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `notif_message` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `notif_sound` tinyint(1) DEFAULT '0',
  `on_transition` varchar(30) DEFAULT 'dwell',
  `after_seconds` int(11) DEFAULT '300',
  `location_triggered` tinyint(1) DEFAULT '0',
  `trigger_survey` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_srv_repeaters`
--

CREATE TABLE `maza_srv_repeaters` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `repeater_on` tinyint(1) DEFAULT '0',
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
  `tracking_on` tinyint(1) DEFAULT '0',
  `activity_recognition` tinyint(1) DEFAULT '0',
  `tracking_accuracy` varchar(30) DEFAULT 'high',
  `interval_wanted` int(11) DEFAULT '30',
  `interval_fastes` int(11) DEFAULT '10',
  `displacement_min` int(11) DEFAULT '10',
  `ar_interval_wanted` int(11) DEFAULT '30'
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
  `srv_description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `maza_user_activity_recognition`
--

CREATE TABLE `maza_user_activity_recognition` (
  `id` int(11) NOT NULL,
  `maza_user_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `in_vehicle` int(3) DEFAULT '0',
  `on_bicycle` int(3) DEFAULT '0',
  `on_foot` int(3) DEFAULT '0',
  `still` int(3) DEFAULT '0',
  `unknown` int(3) DEFAULT '0',
  `tilting` int(3) DEFAULT '0',
  `running` int(3) DEFAULT '0',
  `walking` int(3) DEFAULT '0'
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
  `is_mock` tinyint(1) DEFAULT '0',
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
  `nextpin_tracking_permitted` tinyint(1) DEFAULT '0',
  `tracking_permitted` tinyint(1) DEFAULT NULL,
  `datetime_unsubscribed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `misc`
--

CREATE TABLE `misc` (
  `what` varchar(255) DEFAULT NULL,
  `value` mediumtext
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `misc`
--

INSERT INTO `misc` (`what`, `value`) VALUES
('FS', '15'),
('active', '1'),
('confirm', '0'),
('obisk', '0'),
('active', '1'),
('confirm', '0'),
('obisk', '0'),
('active', '1'),
('confirm', '0'),
('name', '1ka'),
('keywords', 'web survey software, internet survey, online survey, web questionaires'),
('RegSurname', '0'),
('description', '1KA je na eni strani spletno mesto, ki podaja pregled nad tehnologijo in metodologijo spletnih anket, hkrati pa je tudi on-line platforma, kjer je mogoÃ¨e izdelovati spletne ankete.'),
('m2w2all', '0'),
('UserDataReply', 'info@1ka.si'),
('AfterNews', '<p><font size=\"2\">Dear SFNAME,<br />\r\n<br />\r\nhere are the news from SFPAGENAME on SFINT: <br />\r\n<hr align=\"center\" width=\"100%\" />\r\n<br />\r\nSFNEWS<br />\r\n<br />\r\nSincerely,<br />\r\n<br />\r\nSFPAGENAME team<br />\r\n<br />\r\n</font><font size=\"2\" face=\"Arial\"> <hr width=\"100%\" />\r\nEmail has been sent to SFMAIL</font><br />\r\n<font size=\"2\" face=\"Arial\">To unsubscribe click SFOUT here SFEND.<br />\r\nTo change/edit your profile click SFCHANGE here SFEND.</font></p>'),
('BeforeNews', 'xx SFINT xx SFMAIL xx<hr /><br />aaaasadadsasfff'),
('underline', '0'),
('showsender', '1'),
('BeforeName', 'xHi,'),
('m2w2all', '0'),
('AlertFrom', 'info@1ka.si'),
('AlertSubject', 'News from SFPAGENAME'),
('m2w2all', '0'),
('UserDataFrom', 'info@1ka.si'),
('AlertReply', 'info@1ka.si'),
('AlertFrom', 'info@1ka.si'),
('AlertSubject', 'News from SFPAGENAME'),
('AlertReply', 'info@1ka.si'),
('UserDataReply', 'info@1ka.si'),
('NewUserSubject', ''),
('ChangedUserSubject', ''),
('M2WNotAllowedSubject', ''),
('LostPasswordSubject', ''),
('HelloNewUser', ''),
('NewUserMail', ''),
('ChangedUserMail', ''),
('M2WNotAllowed', ''),
('LostPasswordMail', ''),
('PageName', '1ka'),
('OKNewUser', ''),
('OKByeUser', '<p class=\"MsoNormal\" style=\"margin: 0cm 0cm 0pt;\"><font size=\"2\"><span lang=\"EN-US\" style=\"\">Dear colleague, <br />\r\n<br />\r\non SFPAGENAME site we have included some publicly available information on your bibliography related to Web survey methodology. <br />\r\n<br />\r\nRecently we received the below-enclosed inquiry from a visitor&nbsp; for more information about one of your specific bibliographic unit. <br />\r\n<br />\r\nWe are also highly interested to include more of your work on our site. <br />\r\n<br />\r\nSincerely, <br />\r\n<br />\r\n</span>SFPAGENAME team<br />\r\n<br />\r\n*****************************************************************************</font></p>'),
('ByeEmail', '<p>Spo&#353;tovani,</p><p>Uspe&#353;no ste se odjavili iz spletnega mesta www.1ka.si.</p><p>Veseli nas, da ste preizkusili orodje 1ka.</p><p>SFPAGENAME ekipa</p>'),
('ByeWarning', ''),
('OKEdited', ''),
('CommonError', ''),
('ByeEmailSubject', 'UspeÂ¹na odjava'),
('LoginError', ''),
('showsearch', '5'),
('showlogin', '1'),
('keywordssi', 'spletne ankete, spletno anketiranje, internetne ankete, slovenÂ¹Ã¨ina, slovenski jezik, software, softver, programska oprema, orodje za spletne ankete, internetno anketiranje, online vpraÂ¹alniki'),
('keywordsde', ''),
('author', 'CMI, FDV'),
('abstract', '1KA je orodje za spletne ankete'),
('SurveyDostop', '3'),
('FinCurrency', 'SIT'),
('publisher', ''),
('copyright', 'CMI, FDV'),
('audience', 'sploÂ¹na populacija'),
('pagetopic', 'spletne aplikacije'),
('revisit', '7'),
('a.hover', '11'),
('a.active', '3'),
('a.visited', '4'),
('a.memberhover', '11'),
('a.membervisited', '9'),
('a.memberactive', '8'),
('a.userhover', '11'),
('a.uservisited', '7'),
('a.useractive', '5'),
('a.adminhover', '11'),
('a.adminvisited', '10'),
('a.adminactive', '11'),
('a.adminbhover', '11'),
('a.adminbvisited', '10'),
('a.adminbactive', '11'),
('a.bold', '0'),
('a.underline', ''),
('a.italic', '0'),
('a.hoverbold', ''),
('a.hoverunderline', '1'),
('a.hoveritalic', ''),
('a.visitedbold', ''),
('a.visitedunderline', ''),
('a.visiteditalic', ''),
('a.activebold', ''),
('a.activeunderline', '1'),
('a.activeitalic', ''),
('a.userbold', ''),
('a.userunderline', ''),
('a.useritalic', ''),
('a.userhoverbold', ''),
('a.userhoverunderline', '1'),
('a.userhoveritalic', ''),
('a.uservisitedbold', ''),
('a.uservisitedunderline', ''),
('a.uservisiteditalic', ''),
('a.useractivebold', ''),
('a.useractiveunderline', '1'),
('a.useractiveitalic', ''),
('a.memberbold', ''),
('a.memberunderline', ''),
('a.memberitalic', ''),
('a.memberhoverbold', ''),
('a.memberhoverunderline', '1'),
('a.memberhoveritalic', ''),
('a.membervisitedbold', ''),
('a.membervisitedunderline', ''),
('a.membervisiteditalic', ''),
('a.memberactivebold', ''),
('a.memberactiveunderline', '1'),
('a.memberactiveitalic', ''),
('a.adminbold', ''),
('a.adminunderline', ''),
('a.adminitalic', ''),
('a.adminhoverbold', ''),
('a.adminhoverunderline', '1'),
('a.adminhoveritalic', ''),
('a.adminvisitedbold', ''),
('a.adminvisitedunderline', ''),
('a.adminvisiteditalic', ''),
('a.adminactivebold', ''),
('a.adminactiveunderline', '1'),
('a.adminactiveitalic', ''),
('a.adminbbold', ''),
('a.adminbunderline', ''),
('a.adminbitalic', ''),
('a.adminbhoverbold', ''),
('a.adminbhoverunderline', '1'),
('a.adminbhoveritalic', ''),
('a.adminbvisitedbold', ''),
('a.adminbvisitedunderline', ''),
('a.adminbvisiteditalic', ''),
('a.adminbactivebold', ''),
('a.adminbactiveunderline', '1'),
('a.adminbactiveitalic', ''),
('forum_alert', '2010-09-30 09:58:24'),
('thread_alert', '2010-09-30 09:58:24'),
('ShowRubrikeAdmin', '0'),
('ForumSubject', 'New forum post on SFPAGENAME'),
('ForumAlert', '<p>Dear SFNAME,</p>\r\n<p>on SFPAGENAME there is a new post in the thread you are subscribed to.</p>\r\n<p>SFNEWS</p>\r\n<p>Sincerely,</p>\r\n<p>SFPAGENAME</p>\r\n<hr width=\"100%\" />\r\n<p><font size=\"2\">Email has been sent to SFMAIL.<br />\r\n</font><font size=\"2\" face=\"Arial\">To unsubscribe click SFOUT here SFEND.<br />\r\nTo change/edit your profile click SFCHANGE here SFEND.</font></p>'),
('Skin', '1ka-new'),
('adminskin', 'Default'),
('SendToAuthorExplain', ''),
('NoviceChars', '100'),
('ShowSearchMenu', '0'),
('ShowTracking', '0'),
('UploadDummy', '1'),
('FullPageView', '0'),
('AuthorCC', '<p>Dear visitor,<br />\r\n<br />\r\nThank you for your interest; your message to the author has been sent.<br />\r\n<br />\r\nMessage you sent is attached at the bottom of email.</p>'),
('RegName', '1'),
('RegPass', '1'),
('RegAlert', '0'),
('RegAlertOptions', '0'),
('RegEmailOptions', '0'),
('ToBasicUser', '<p>Dear SFPAGENAME user,<br />\r\n<br />\r\nanother user from our website sent you notification. <br />\r\nEmail of that user and message follows:</p>'),
('AdminRememberIP', '193.2.85.52'),
('ForumMenus', '0'),
('RegEditorOn', '0'),
('RegEditorName', 'Details'),
('RegTextBoxOn', '0'),
('RegTextBoxName', 'Affiliation'),
('NoNUExplain', '<p>This web page is closed-type so you can not register without invitation</p>'),
('BadWords', 'fuck'),
('BadWords', 'kurac'),
('BadWords', 'pizda'),
('BadWords', 'pussy'),
('BadWordsReplacement', '***'),
('DefaultRootSearch', '0'),
('RegAddArticle', '-1'),
('SendToAuthorSpeech', '<p>By filling out this form, you will send e-mail to the requested author.</p>'),
('RegAvatarEnable', '1'),
('NewChars', '100'),
('SendToForumSpeech', '<p>By filling out this form, you will send e-mail to the requested author.</p>'),
('ArticleDateType', '0'),
('AdminNoBotherIP', '0'),
('RelatedDropdown', 'http://www.sisplet.org,Sisplet CMS'),
('RelatedDropdownActive', '0'),
('ForumAccessMode', '1'),
('MenuTriangles', '0'),
('BreadCrumbs', '0'),
('BlindEmail', 'info@1ka.si'),
('ShowOnline', '1'),
('ForumColumns', '0'),
('TimeLink', '0'),
('CreateForum', '0'),
('CreateNavigation', '-1'),
('NoNUExplain', '<p>This web page is closed-type so you can not register without invitation</p>'),
('BadWords', 'fuck'),
('BadWords', 'kurac'),
('BadWords', 'pizda'),
('BadWords', 'pussy'),
('BadWordsReplacement', '***'),
('DefaultRootSearch', '0'),
('ProfileMenus', '0'),
('NewsColumns', '6040'),
('CookieLife', '43200'),
('ShowCountMenu', '1'),
('ShowCountRub', '1'),
('ForumLongAuthor', '1'),
('ProfileLink', '0'),
('ForumTopTxt', '1'),
('forum_display_column', '1'),
('forum_display_settings', ''),
('AdminContent', '0'),
('RegPassDefault', '0'),
('gridopt', '1'),
('BreadCrumbsNoFirst', '0'),
('BreadCrumbsNoLast', '0'),
('BreadCrumbsNoClick', '0'),
('ForumHourDisplay', '1'),
('ForumMarkDisplay', '0'),
('ForumEditPost', '0'),
('MembersFastEdit', '0'),
('Financial', '0'),
('TimeTables', '0'),
('SurveyCookie', '-1'),
('CalendarSubject', 'New forum post on SFPAGENAME'),
('CalendarAlert', ''),
('ip_yes', ''),
('ip_no', ''),
('favicon', ''),
('preslikavaKAM', '2'),
('preslikavaOSTALI', '0'),
('zavzamemenu', '1'),
('AfterReg', 'http://www.1ka.si/admin/survey/index.php'),
('AfterReg', 'http://www.1ka.si/admin/survey/index.php'),
('srv_sistemske', '5'),
('SurveyMetaOnly', '0'),
('hour_insertproject', '1'),
('register_auto_t', '0'),
('hour_insertproject', '1'),
('register_auto_t', '0'),
('SurveyMetaOnly', '0'),
('ShowBookmarks', '1'),
('DefaultShowPrintWordPdf', '1'),
('DefaultShowDigg', '1'),
('DefaultShowPrintWordPdf', '1'),
('DefaultShowDigg', '1'),
('RegShowInterval', '1'),
('RegShowGroups', '1'),
('RegShowColumns', '1'),
('RegShowN', '1'),
('RegShowInterval', '1'),
('RegShowGroups', '1'),
('RegShowColumns', '1'),
('RegShowN', '1'),
('WhenSendCustomMail', 'never / nikoli'),
('SurveyExport', '3'),
('utrack_acc', '0'),
('RegEmailActivate', '1'),
('analitics', '&lt;script type=&quot;text/javascript&quot;&gt;\r\nvar gaJsHost = ((&quot;https:&quot; == document.location.protocol) ? &quot;https://ssl.&quot; : &quot;http://www.&quot;);\r\ndocument.write(unescape(&quot;%3Cscript src=\'&quot; + gaJsHost + &quot;google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E&quot;));\r\n&lt;/script&gt;\r\n&lt;script type=&quot;text/javascript&quot;&gt;\r\ntry {\r\nvar pageTracker = _gat._getTracker(&quot;UA-12079719-1&quot;);\r\npageTracker._trackPageview();\r\n} catch(err) {}&lt;/script&gt;'),
('user_see_hour_views', '0'),
('ShortPageName', ''),
('SurveyForum', '0'),
('user_see_hour_views_meta', '0'),
('user_see_forum_views', '0'),
('ForumNewInicialke', '0'),
('SurveyLang_admin', '1'),
('SurveyLang_resp', '1'),
('RegCustomGroupsText', ''),
('RegAgreement', ''),
('DefaultShowLike', '0'),
('srv_maxDashboardChacheFiles', '200'),
('DefaultShowLikeAbove', '0'),
('RegLang', '0'),
('invitationTrackingStarted', '2014-07-19 14:45:40'),
('UnregisterEmbed', ''),
('drupal version', '7.72'),
('mobileApp_version', '16.5.30'),
('drupal version', '7.72'),
('version', '20.07.17');

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
  `fid` int(11) NOT NULL DEFAULT '0',
  `tid` int(11) NOT NULL DEFAULT '0',
  `parent` int(11) NOT NULL DEFAULT '0',
  `naslov` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `naslovnica` tinyint(4) NOT NULL DEFAULT '1',
  `vsebina` text CHARACTER SET utf8 NOT NULL,
  `ogledov` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `user` varchar(40) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `time2` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `admin` int(11) NOT NULL DEFAULT '3',
  `dispauth` int(11) NOT NULL DEFAULT '0',
  `dispthread` int(11) NOT NULL DEFAULT '0',
  `ocena` int(11) NOT NULL DEFAULT '0',
  `IP` varchar(128) NOT NULL DEFAULT 'Neznan',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
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
  `pos_x_start` int(11) NOT NULL DEFAULT '0',
  `pos_y_start` int(11) NOT NULL DEFAULT '0',
  `pos_x_end` int(11) NOT NULL DEFAULT '0',
  `pos_y_end` int(11) NOT NULL DEFAULT '0',
  `distance` int(11) DEFAULT '0'
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
  `pos_x` int(11) NOT NULL DEFAULT '0',
  `pos_y` int(11) NOT NULL DEFAULT '0',
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
  `devicePixelRatio` decimal(4,2) NOT NULL DEFAULT '0.00',
  `width` int(11) NOT NULL DEFAULT '0',
  `height` int(11) NOT NULL DEFAULT '0',
  `availWidth` int(11) NOT NULL DEFAULT '0',
  `availHeight` int(11) NOT NULL DEFAULT '0',
  `jquery_windowW` int(11) NOT NULL DEFAULT '0',
  `jquery_windowH` int(11) NOT NULL DEFAULT '0',
  `jquery_documentW` int(11) NOT NULL DEFAULT '0',
  `jquery_documentH` int(11) NOT NULL DEFAULT '0'
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
  `finish_respondent` tinyint(1) NOT NULL DEFAULT '0',
  `finish_respondent_cms` tinyint(1) NOT NULL DEFAULT '0',
  `finish_author` tinyint(1) NOT NULL DEFAULT '0',
  `finish_other` tinyint(1) NOT NULL DEFAULT '0',
  `finish_other_emails` mediumtext NOT NULL,
  `finish_subject` varchar(250) CHARACTER SET utf8 NOT NULL,
  `finish_text` text CHARACTER SET utf8 NOT NULL,
  `expire_days` int(11) NOT NULL DEFAULT '3',
  `expire_author` tinyint(1) NOT NULL DEFAULT '0',
  `expire_other` tinyint(1) NOT NULL DEFAULT '0',
  `expire_other_emails` mediumtext NOT NULL,
  `expire_text` text CHARACTER SET utf8 NOT NULL,
  `expire_subject` varchar(250) CHARACTER SET utf8 NOT NULL,
  `delete_author` tinyint(1) NOT NULL DEFAULT '0',
  `delete_other` tinyint(1) NOT NULL DEFAULT '0',
  `delete_other_emails` mediumtext NOT NULL,
  `delete_text` text CHARACTER SET utf8 NOT NULL,
  `delete_subject` varchar(250) CHARACTER SET utf8 NOT NULL,
  `active_author` tinyint(1) NOT NULL DEFAULT '0',
  `active_other` tinyint(1) NOT NULL DEFAULT '0',
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

INSERT INTO `srv_alert` (`ank_id`, `finish_respondent`, `finish_respondent_cms`, `finish_author`, `finish_other`, `finish_other_emails`, `finish_subject`, `finish_text`, `expire_days`, `expire_author`, `expire_other`, `expire_other_emails`, `expire_text`, `expire_subject`, `delete_author`, `delete_other`, `delete_other_emails`, `delete_text`, `delete_subject`, `active_author`, `active_other`, `active_other_emails`, `active_text0`, `active_subject0`, `active_text1`, `active_subject1`, `finish_respondent_if`, `finish_respondent_cms_if`, `finish_other_if`, `reply_to`) VALUES
(0, 0, 0, 0, 0, '', '', '', 3, 1, 0, '', 'SpoÃ…Â¡tovani,<br/><br/>obveÃ…Â¡Ã„Âamo vas, da bo anketa &#34;[SURVEY]&#34; potekla &#269;ez [DAYS] dni.<br/><br/>Povezava: [URL]<br/><br/>Ã„Å’as aktivnosti lahko podaljÃ…Â¡ate v nastavitvah [DURATION]<br />\n<br />\n<br />\n1KA<br />\n--------<br />\nOrodje za spletne ankete: <a href=\"http://www.1ka.si\">http://www.1ka.si</a>', '1KA - obvestilo o izteku ankete', 0, 0, '', '', '', 0, 0, '', '', '', '', '', NULL, NULL, NULL, '');

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
  `sid` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL,
  `filename` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  `note` varchar(200) NOT NULL,
  `access` tinyint(4) NOT NULL DEFAULT '0',
  `access_password` varchar(30) DEFAULT NULL,
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `duration` date NOT NULL,
  `editid` int(11) NOT NULL DEFAULT '0',
  `settings` mediumtext
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_anketa`
--

CREATE TABLE `srv_anketa` (
  `id` int(11) NOT NULL,
  `folder` int(11) NOT NULL DEFAULT '1',
  `backup` int(11) NOT NULL,
  `naslov` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `akronim` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `locked` tinyint(4) NOT NULL DEFAULT '0',
  `db_table` tinyint(4) NOT NULL DEFAULT '0',
  `starts` date NOT NULL,
  `expire` date NOT NULL,
  `introduction` text CHARACTER SET utf8,
  `conclusion` text CHARACTER SET utf8,
  `statistics` text CHARACTER SET utf8,
  `intro_opomba` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `concl_opomba` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `show_intro` tinyint(4) NOT NULL DEFAULT '1',
  `intro_static` tinyint(4) NOT NULL DEFAULT '0',
  `show_concl` tinyint(4) NOT NULL DEFAULT '1',
  `concl_link` int(11) NOT NULL DEFAULT '0',
  `concl_back_button` tinyint(4) NOT NULL DEFAULT '1',
  `concl_end_button` tinyint(4) NOT NULL DEFAULT '1',
  `text` varchar(250) NOT NULL,
  `url` varchar(250) NOT NULL,
  `insert_uid` int(11) NOT NULL,
  `insert_time` datetime NOT NULL,
  `edit_uid` int(11) NOT NULL,
  `edit_time` datetime NOT NULL,
  `cookie` tinyint(4) NOT NULL DEFAULT '2',
  `cookie_return` tinyint(4) NOT NULL DEFAULT '0',
  `return_finished` tinyint(4) NOT NULL DEFAULT '0',
  `cookie_continue` tinyint(4) NOT NULL DEFAULT '1',
  `user_from_cms` int(11) NOT NULL DEFAULT '0',
  `user_from_cms_email` int(11) NOT NULL DEFAULT '0',
  `user_base` tinyint(4) NOT NULL DEFAULT '0',
  `usercode_skip` tinyint(4) NOT NULL DEFAULT '0',
  `usercode_required` tinyint(4) NOT NULL DEFAULT '0',
  `usercode_text` varchar(255) NOT NULL,
  `block_ip` int(11) NOT NULL DEFAULT '0',
  `dostop` int(11) NOT NULL DEFAULT '3',
  `dostop_admin` date NOT NULL,
  `odgovarja` tinyint(4) NOT NULL DEFAULT '4',
  `skin` varchar(100) NOT NULL DEFAULT '1kaBlue',
  `mobile_skin` varchar(100) NOT NULL DEFAULT 'MobileBlue',
  `skin_profile` int(11) NOT NULL,
  `skin_profile_mobile` int(11) NOT NULL,
  `skin_checkbox` tinyint(4) NOT NULL DEFAULT '0',
  `branching` smallint(6) NOT NULL DEFAULT '0',
  `alert_respondent` tinyint(4) NOT NULL DEFAULT '0',
  `alert_avtor` tinyint(4) NOT NULL DEFAULT '0',
  `alert_admin` tinyint(4) NOT NULL DEFAULT '0',
  `alert_more` tinyint(1) NOT NULL DEFAULT '0',
  `uporabnost_link` varchar(400) NOT NULL,
  `progressbar` tinyint(4) NOT NULL DEFAULT '0',
  `sidebar` tinyint(4) NOT NULL DEFAULT '1',
  `collapsed_content` tinyint(4) NOT NULL DEFAULT '1',
  `library` tinyint(4) NOT NULL DEFAULT '0',
  `countType` tinyint(1) NOT NULL DEFAULT '0',
  `survey_type` tinyint(4) NOT NULL DEFAULT '2',
  `forum` int(11) NOT NULL DEFAULT '0',
  `thread` int(11) NOT NULL,
  `thread_intro` int(11) NOT NULL DEFAULT '0',
  `thread_concl` int(11) NOT NULL DEFAULT '0',
  `intro_note` text NOT NULL,
  `concl_note` text NOT NULL,
  `vote_limit` tinyint(4) NOT NULL DEFAULT '0',
  `vote_count` int(11) NOT NULL DEFAULT '0',
  `lang_admin` tinyint(4) NOT NULL DEFAULT '1',
  `lang_resp` tinyint(4) NOT NULL DEFAULT '1',
  `multilang` tinyint(4) NOT NULL DEFAULT '0',
  `expanded` tinyint(4) NOT NULL DEFAULT '1',
  `flat` tinyint(4) NOT NULL DEFAULT '0',
  `toolbox` tinyint(4) NOT NULL DEFAULT '1',
  `popup` tinyint(4) NOT NULL DEFAULT '1',
  `missing_values_type` tinyint(4) NOT NULL DEFAULT '0',
  `mass_insert` enum('0','1') NOT NULL,
  `monitoring` enum('0','1') NOT NULL,
  `show_email` enum('0','1') NOT NULL DEFAULT '1',
  `old_email_style` enum('0','1') NOT NULL DEFAULT '0',
  `vprasanje_tracking` enum('0','1','2','3') CHARACTER SET utf8 NOT NULL DEFAULT '0',
  `parapodatki` enum('0','1') NOT NULL,
  `individual_invitation` enum('0','1') NOT NULL DEFAULT '1',
  `email_to_list` enum('0','1') NOT NULL DEFAULT '0',
  `invisible` enum('0','1') NOT NULL DEFAULT '0',
  `continue_later` enum('1','0') NOT NULL DEFAULT '0',
  `js_tracking` text NOT NULL,
  `concl_PDF_link` tinyint(4) NOT NULL DEFAULT '0',
  `concl_return_edit` enum('0','1') NOT NULL DEFAULT '0',
  `defValidProfile` tinyint(4) NOT NULL DEFAULT '2',
  `showItime` tinyint(4) NOT NULL DEFAULT '0',
  `showLineNumber` tinyint(4) NOT NULL DEFAULT '0',
  `mobile_created` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_anketa`
--

INSERT INTO `srv_anketa` (`id`, `folder`, `backup`, `naslov`, `akronim`, `active`, `locked`, `db_table`, `starts`, `expire`, `introduction`, `conclusion`, `statistics`, `intro_opomba`, `concl_opomba`, `show_intro`, `intro_static`, `show_concl`, `concl_link`, `concl_back_button`, `concl_end_button`, `text`, `url`, `insert_uid`, `insert_time`, `edit_uid`, `edit_time`, `cookie`, `cookie_return`, `return_finished`, `cookie_continue`, `user_from_cms`, `user_from_cms_email`, `user_base`, `usercode_skip`, `usercode_required`, `usercode_text`, `block_ip`, `dostop`, `dostop_admin`, `odgovarja`, `skin`, `mobile_skin`, `skin_profile`, `skin_profile_mobile`, `skin_checkbox`, `branching`, `alert_respondent`, `alert_avtor`, `alert_admin`, `alert_more`, `uporabnost_link`, `progressbar`, `sidebar`, `collapsed_content`, `library`, `countType`, `survey_type`, `forum`, `thread`, `thread_intro`, `thread_concl`, `intro_note`, `concl_note`, `vote_limit`, `vote_count`, `lang_admin`, `lang_resp`, `multilang`, `expanded`, `flat`, `toolbox`, `popup`, `missing_values_type`, `mass_insert`, `monitoring`, `show_email`, `old_email_style`, `vprasanje_tracking`, `parapodatki`, `individual_invitation`, `email_to_list`, `invisible`, `continue_later`, `js_tracking`, `concl_PDF_link`, `concl_return_edit`, `defValidProfile`, `showItime`, `showLineNumber`, `mobile_created`) VALUES
(-1, 1, 0, 'system', '', 0, 0, 0, '0000-00-00', '0000-00-00', '', '', '', '', '', 1, 0, 1, 0, 1, 1, '', '', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 2, 0, 1, 1, 0, 0, 0, 1, 0, '', 0, 3, '0000-00-00', 4, 'Modern', 'Mobile', 0, 0, 0, 1, 0, 0, 0, 0, '', 1, 1, 1, 0, 0, 2, 0, 0, 0, 0, '', '', 0, 0, 1, 1, 0, 1, 1, 1, 1, 0, '0', '0', '0', '1', '0', '0', '1', '0', '0', '1', '', 0, '0', 2, 0, 0, '0'),
(0, 1, 0, 'system', '', 0, 0, 0, '0000-00-00', '0000-00-00', '', '', '', '', '', 1, 0, 1, 0, 1, 1, '', '', 0, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 2, 0, 1, 1, 0, 0, 0, 1, 0, '', 0, 3, '0000-00-00', 4, 'Modern', 'Mobile', 0, 0, 0, 0, 0, 0, 0, 0, '', 1, 1, 1, 0, 0, 2, 0, 0, 0, 0, '', '', 0, 0, 1, 1, 0, 1, 1, 1, 1, 0, '0', '0', '0', '1', '0', '0', '1', '0', '0', '1', '', 0, '0', 2, 0, 0, '0');

-- --------------------------------------------------------

--
-- Table structure for table `srv_anketa_module`
--

CREATE TABLE `srv_anketa_module` (
  `ank_id` int(11) NOT NULL,
  `modul` varchar(100) NOT NULL DEFAULT '',
  `vrednost` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_anketa_template`
--

CREATE TABLE `srv_anketa_template` (
  `id` int(11) NOT NULL,
  `kategorija` tinyint(1) DEFAULT '0',
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
  `usr_id` int(11) NOT NULL DEFAULT '0',
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
  `pagebreak` tinyint(4) NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `number` int(11) NOT NULL DEFAULT '0',
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
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
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
  `status_z` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status_n` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `max_calls` int(10) UNSIGNED NOT NULL DEFAULT '0'
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
  `usr_id` int(11) NOT NULL DEFAULT '0',
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
-- Table structure for table `srv_comment_resp`
--

CREATE TABLE `srv_comment_resp` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0',
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
  `conjunction` smallint(6) NOT NULL DEFAULT '0',
  `negation` smallint(6) NOT NULL DEFAULT '0',
  `operator` smallint(6) NOT NULL DEFAULT '0',
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `modul` smallint(6) NOT NULL DEFAULT '2',
  `ostanek` smallint(6) NOT NULL DEFAULT '0',
  `left_bracket` smallint(6) NOT NULL DEFAULT '0',
  `right_bracket` smallint(6) NOT NULL DEFAULT '0',
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
  `condition_error` tinyint(1) NOT NULL DEFAULT '0',
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
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0',
  `spr1` varchar(255) NOT NULL DEFAULT '',
  `spr2` varchar(255) NOT NULL DEFAULT '',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `sub_type` tinyint(1) NOT NULL DEFAULT '0',
  `vrstni_red` tinyint(1) NOT NULL DEFAULT '0',
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `profile` int(11) NOT NULL DEFAULT '0',
  `time_edit` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_custom_report_profiles`
--

CREATE TABLE `srv_custom_report_profiles` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL,
  `time_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_custom_report_share`
--

CREATE TABLE `srv_custom_report_share` (
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `profile_id` int(11) NOT NULL DEFAULT '0',
  `author_usr_id` int(11) NOT NULL DEFAULT '0',
  `share_usr_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_datasetting_profile`
--

CREATE TABLE `srv_datasetting_profile` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL,
  `dsp_ndp` tinyint(4) NOT NULL DEFAULT '1',
  `dsp_nda` tinyint(4) NOT NULL DEFAULT '2',
  `dsp_ndd` tinyint(4) NOT NULL DEFAULT '2',
  `dsp_res` tinyint(4) NOT NULL DEFAULT '3',
  `dsp_sep` tinyint(4) NOT NULL DEFAULT '0',
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
  `chartFontSize` tinyint(4) NOT NULL DEFAULT '8',
  `chartPieZeros` enum('0','1') NOT NULL DEFAULT '1',
  `hideEmpty` enum('0','1') NOT NULL DEFAULT '0',
  `numOpenAnswers` int(11) NOT NULL DEFAULT '10',
  `dataPdfType` enum('0','1','2') NOT NULL DEFAULT '0',
  `exportDataNumbering` enum('0','1') NOT NULL DEFAULT '1',
  `exportDataShowIf` enum('0','1') NOT NULL DEFAULT '1',
  `exportDataFontSize` tinyint(4) NOT NULL DEFAULT '10',
  `exportDataShowRecnum` enum('0','1') NOT NULL DEFAULT '1',
  `exportDataPB` enum('0','1') NOT NULL DEFAULT '0',
  `exportDataSkipEmpty` enum('0','1') NOT NULL DEFAULT '0',
  `exportDataSkipEmptySub` enum('0','1') NOT NULL DEFAULT '0',
  `exportDataLandscape` enum('0','1') NOT NULL DEFAULT '0',
  `exportNumbering` enum('0','1') NOT NULL DEFAULT '1',
  `exportShowIf` enum('0','1') NOT NULL DEFAULT '1',
  `exportFontSize` tinyint(4) NOT NULL DEFAULT '10',
  `exportShowIntro` enum('0','1') NOT NULL DEFAULT '0',
  `enableInspect` enum('0','1') NOT NULL DEFAULT '0',
  `dataShowIcons` enum('0','1') NOT NULL DEFAULT '1',
  `analysisGoTo` enum('0','1') NOT NULL DEFAULT '1',
  `analiza_legenda` enum('0','1') NOT NULL DEFAULT '0',
  `hideAllSystem` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_checkgrid`
--

CREATE TABLE `srv_data_checkgrid` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
-- Table structure for table `srv_data_files`
--

CREATE TABLE `srv_data_files` (
  `sid` int(11) NOT NULL,
  `head_file_time` datetime NOT NULL,
  `data_file_time` datetime NOT NULL,
  `collect_all_status` tinyint(4) NOT NULL DEFAULT '1',
  `collect_full_meta` tinyint(4) NOT NULL DEFAULT '1',
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
-- Table structure for table `srv_data_grid`
--

CREATE TABLE `srv_data_grid` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `loop_id` int(11) DEFAULT NULL
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
  `text` text CHARACTER SET utf8 COLLATE utf8_bin,
  `vrstni_red` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_imena`
--

CREATE TABLE `srv_data_imena` (
  `id` int(10) UNSIGNED NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT '0',
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_id` int(11) NOT NULL DEFAULT '0',
  `antonucci` int(1) NOT NULL DEFAULT '0',
  `emotion` tinyint(1) NOT NULL DEFAULT '0',
  `social` tinyint(1) NOT NULL DEFAULT '0',
  `emotionINT` tinyint(1) NOT NULL DEFAULT '0',
  `socialINT` tinyint(1) NOT NULL DEFAULT '0',
  `countE` int(11) NOT NULL DEFAULT '0',
  `countS` int(11) NOT NULL DEFAULT '0'
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
  `text` text CHARACTER SET utf8 COLLATE utf8_bin,
  `vrstni_red` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_number`
--

CREATE TABLE `srv_data_number` (
  `spr_id` int(11) NOT NULL DEFAULT '0',
  `vre_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0',
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
-- Table structure for table `srv_data_text`
--

CREATE TABLE `srv_data_text` (
  `id` int(10) UNSIGNED NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT '0',
  `vre_id` int(11) NOT NULL DEFAULT '0',
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `text2` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_id` int(11) DEFAULT '0',
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_textgrid`
--

CREATE TABLE `srv_data_textgrid` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `grd_id` int(11) NOT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
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
-- Table structure for table `srv_data_text_active`
--

CREATE TABLE `srv_data_text_active` (
  `id` int(10) UNSIGNED NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT '0',
  `vre_id` int(11) NOT NULL DEFAULT '0',
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `text2` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `usr_id` int(11) DEFAULT '0',
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
-- Table structure for table `srv_data_vrednost`
--

CREATE TABLE `srv_data_vrednost` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0',
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_vrednost_active`
--

CREATE TABLE `srv_data_vrednost_active` (
  `spr_id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0',
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_data_vrednost_cond`
--

CREATE TABLE `srv_data_vrednost_cond` (
  `id` int(10) UNSIGNED NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT '0',
  `vre_id` int(11) NOT NULL DEFAULT '0',
  `text` text COLLATE utf8_bin NOT NULL,
  `usr_id` int(11) NOT NULL DEFAULT '0',
  `loop_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `srv_dostop`
--

CREATE TABLE `srv_dostop` (
  `ank_id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `aktiven` tinyint(4) NOT NULL DEFAULT '1',
  `alert_complete` tinyint(1) NOT NULL DEFAULT '0',
  `alert_expire` tinyint(1) NOT NULL DEFAULT '0',
  `alert_delete` tinyint(1) NOT NULL DEFAULT '0',
  `alert_active` tinyint(1) NOT NULL DEFAULT '0',
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
  `ank_id` int(11) NOT NULL DEFAULT '0',
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
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `skupina_id` int(11) NOT NULL DEFAULT '0',
  `email` varchar(100) DEFAULT '',
  `lang_id` int(11) NOT NULL DEFAULT '1',
  `url` varchar(255) DEFAULT '',
  `kvota_max` int(11) NOT NULL DEFAULT '0',
  `kvota_val` int(11) NOT NULL DEFAULT '0',
  `date_from` date NOT NULL DEFAULT '0000-00-00',
  `date_to` date NOT NULL DEFAULT '0000-00-00',
  `datum_posiljanja` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_evoli_teammeter_data_department`
--

CREATE TABLE `srv_evoli_teammeter_data_department` (
  `department_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0'
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
  `tm_id` int(11) NOT NULL DEFAULT '0',
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
  `lastnum` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_folder`
--

CREATE TABLE `srv_folder` (
  `id` int(11) NOT NULL,
  `naslov` varchar(50) NOT NULL,
  `parent` int(11) NOT NULL,
  `creator_uid` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_folder`
--

INSERT INTO `srv_folder` (`id`, `naslov`, `parent`, `creator_uid`) VALUES
(1, 'Moje 1KA ankete', 0, 0);

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
  `other_text` varchar(255) NOT NULL DEFAULT '',
  `about` text NOT NULL,
  `expire` enum('0','1') NOT NULL DEFAULT '0',
  `expire_text` varchar(255) NOT NULL DEFAULT '',
  `other_users` enum('0','1') NOT NULL DEFAULT '0',
  `other_users_text` varchar(255) NOT NULL DEFAULT '',
  `export` enum('0','1') NOT NULL DEFAULT '0',
  `export_country` varchar(255) NOT NULL DEFAULT '',
  `export_user` varchar(255) NOT NULL DEFAULT '',
  `export_legal` varchar(255) NOT NULL DEFAULT '',
  `authorized` varchar(255) NOT NULL DEFAULT '',
  `contact_email` varchar(255) NOT NULL DEFAULT '',
  `note` text NOT NULL
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
  `type` tinyint(1) NOT NULL DEFAULT '0',
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
  `show_results` tinyint(4) NOT NULL DEFAULT '0',
  `show_percent` tinyint(4) NOT NULL DEFAULT '1',
  `show_graph` tinyint(4) NOT NULL DEFAULT '1',
  `spol` tinyint(4) NOT NULL DEFAULT '0',
  `stat_count` tinyint(4) NOT NULL DEFAULT '1',
  `stat_time` tinyint(4) NOT NULL DEFAULT '1',
  `embed` tinyint(4) NOT NULL DEFAULT '0',
  `show_title` tinyint(4) NOT NULL DEFAULT '0',
  `stat_archive` tinyint(4) NOT NULL DEFAULT '0',
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
  `vrstni_red` int(11) NOT NULL DEFAULT '0',
  `variable` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `other` tinyint(4) NOT NULL DEFAULT '0',
  `part` tinyint(4) NOT NULL DEFAULT '1',
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
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `naslov` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `vrstni_red` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_grupa`
--

INSERT INTO `srv_grupa` (`id`, `ank_id`, `naslov`, `vrstni_red`, `timestamp`) VALUES
(-2, 0, 'system', 0, '2018-03-05 09:50:10'),
(-1, 0, 'system', 0, '2018-03-05 09:50:10'),
(0, 0, 'system', 0, '2018-03-05 09:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `srv_hash_url`
--

CREATE TABLE `srv_hash_url` (
  `hash` varchar(32) NOT NULL,
  `anketa` int(11) NOT NULL DEFAULT '0',
  `properties` text NOT NULL,
  `comment` varchar(256) NOT NULL,
  `access_password` varchar(30) DEFAULT NULL,
  `refresh` int(2) NOT NULL DEFAULT '0',
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
  `lang` tinyint(4) NOT NULL DEFAULT '1',
  `help` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_help`
--

INSERT INTO `srv_help` (`what`, `lang`, `help`) VALUES
('DataPiping', 1, 'Ã„Å’e respondent pri vpraÃ…Â¡anju npr. Q1 (npr. Katero je vaÃ…Â¡e najljubÃ…Â¡e sadje) odgovori npr. Ã‚Â»jabolkaÃ‚Â«, lahko to vkljuÃ„Âimo v vpraÃ…Â¡anje Q2, npr. Ã‚Â»Kako pogosto kupujete #Q1# na trÃ…Â¾nici?\n\nPri tem je treba upoÃ…Â¡tevati:\n\n    * VpraÃ…Â¡anje Q2, ki vkljuÃ„Âi odgovor, mora biti na naslednji strani,\n    * Ime spremenljivke, ki se prenaÃ…Â¡a (Q1) je treba spremeniti, ker je lahko predmet avtomatskega preÃ…Â¡tevilÃ„Âenja, npr. spremenimo Ã‚Â»Q1Ã‚Â« v Ã‚Â»SADJEÃ‚Â«'),
('displaychart_settings', 1, '&#268;e &#382;elite urediti manjkajo&#269;e vrednosti ali za grafe prilagoditi text vpra&#353;anja, potem to spremenite v naprednih nastavitvah.'),
('displaydata_checkboxes', 1, '<ul style=\"padding-left:15px\"> <li>Kadar je izbrana opcija \"<b>Podatki</b>\", se v tabeli prikazujejo podatki respondentov.</li> <li>\"<b>status</b>\"<dl><dt>&nbsp;&nbsp;6-kon&#269;al anketo,</dt><dt>&nbsp;&nbsp;5-delno izpolnjena,</dt><dt>&nbsp;&nbsp;4-klik na anketo,</dt><dt>&nbsp;&nbsp;3-klik na nagovor,</dt><dt>&nbsp;&nbsp;2-epo&#353;ta-napaka,</dt><dt>&nbsp;&nbsp;1-epo&#353;ta-neodgovor,</dt><dt>&nbsp;&nbsp;0-epo&#353;ta-ni poslana),</dt><dt>&nbsp;&nbsp;lurker - prazna anketa (1 = da, 0 = ne),</dt><dt>&nbsp;&nbsp;Zaporedna &#353;tevilka vnosa</dt></dl></li> <li>Kadar je izbrana opcija \"<b>Meta podatki</b>\" prikazujemo meta podatke uporabnika: datum vnosa, datum popravljanja, &#269;ase po straneh, IP, JavaScript, podatke brskalnika</li> <li>Kadar je izbrana opcija \"<b>Sistemski podatki</b>\" prikazujemo sistemske podatke respondenta: ime, priimek, email....</li> </ul>'),
('displaydata_data', 1, 'Kadar je opcija izbrana, se v tabeli prika&#382;ejo podatki respondentov'),
('displaydata_meta', 1, 'Priak&#382;e meta podatke uporabnika: datum vnosa, datum popravljanja, &#269;ase po straneh, IP, JavaScript, podatke brskalnika'),
('displaydata_pdftype', 1, 'Dolg izpis pomeni izpis oblike kakr&#353;ne je vpra&#353;alnik, kraj&#353;i izpis pa izpi&#353;e vpra&#353;alnik z rezultati v skraj&#353;ani obliki.'),
('displaydata_status', 1, 'status (6-kon&#269;al anketo, 5-delno izpolnjena, 4-klik na anketo, 3-klik na nagovor, 2-epo&#353;ta-napaka, 1-epo&#353;ta-neodgovor, 0-epo&#353;ta-ni poslana),<br>lurker - prazna anketa (1 = da, 0 = ne),<br>Zaporedna &#353;tevilka vnosa'),
('displaydata_system', 1, 'Prika&#382;e sistemske podatke respondenta: ime, priimek, email....'),
('dodaj_searchbox', 1, 'V zemljevid vklju&#269;i tudi iskalno okno, preko katerega lahko respondent tudi opisno poi&#353;&#269;e lokacijo na zemljevidu'),
('edit_date_range', 1, 'Datum lahko navzdol omejimo z letnico, naprimer: 1951 ali kot obdobje -70, kar pomeni zadnjih 70 let. Podobno lahko omejimo datum tudi navzgor. Naprimer: 2013 ali kot obdobje +10, kar pomeni naslednjih 10 let'),
('edit_variable', 1, 'Urejanje variable'),
('exportSettings', 1, 'Kadar izberete \"Izvozi samo identifikatorje\" se bodo izvozili samo identifikatorji (sistemski podatki repondenta), brez katerikoli drugih podatkov.<br>Kadar pa ne izvaÃ…Â¾ate identifikatorjev pa lahko izvozite posamezne para podatke respondenta.'),
('fieldwork_devices', 1, 'blablabla'),
('fieldwork_devices', 2, 'blablabla'),
('individual_invitation', 1, 'Individualiziran URL'),
('inv_recipiens_from_system', 1, 'Prejemniki bodo dodani iz obstojeÃ„Âih podatkov v bazi, pri Ã„Âemer mora vpraÃ…Â¡alnik vsebovati sistemsko spremenljivko email'),
('marker_podvprasanje', 1, 'V obla&#269;ek markerja dodaj podvpra&#353;anje'),
('naslov_podvprasanja_map', 1, 'Besedilo podvpra&#353;anja v obla&#269;ku markerja'),
('spremenljivka_reminder', 1, 'V primeru, da respondent ni odgovoril na predvideno vpraÃ…Â¡anje, imamo tri moÃ…Â¾nosti:\n<UL>\n<LI><b>Brez opozorila </b> pomeni, da respondenti lahko, tudi Ã„Âe ne odgovorijo na doloÃ„Âeno vpraÃ…Â¡anje, brez opozorila nadaljujejo z anketo. \n\n<LI><b>Trdo opozorilo </b> pomeni, da respondenti, Ã„Âe ne odgovorijo na vpraÃ…Â¡anje s trdim opozorilom, dobijo obvestilo, da ne morejo nadaljevati z reÃ…Â¡evanjem ankete.\n\n<LI><b>Mehko opozorilo </b> pomeni, da respondenti, Ã„Âe ne odgovorijo, dobijo opozorilo, vendar lahko kljub temu nadaljujejo z reÃ…Â¡evanjem.\n</UL>'),
('spremenljivka_sistem', 1, 'S klikanjem na nastavitve v lahko zbiramo med dvema vrstama integracije vpraÃ…Â¡anja v anketo:\n<UL>\n<LI><b>Navadno</b> vpraÃ…Â¡anje,\n\n<LI><b>Sistemsko</b> vpraÃ…Â¡anje, ki omogoÃ„Âa uporabo vpraÃ…Â¡anja tudi izven samega vpraÃ…Â¡alnika. Gre za dva vidika:\n(1) sistemsko vpraÃ…Â¡anje (npr. ime) lahko oznaÃ„Âite in uporabite, tako da nastopa v elektronskem obvestilu respondentu, kjer spremenljivko z njegovim imenom uporabimo v elektronskem sporoÃ„Âilu, da se mu zahvalimo ali ga obvestimo o drugem valu anketiranja,\n(2) sistemsko vpraÃ…Â¡anje lahko neposredno uvozimo v bazo VNOSI mimo anketnega vpraÃ…Â¡alnika. Tako npr. lahko vnesemo ali naloÃ…Â¾imo datoteko s telefonski Ã…Â¡tevilkami ali emaili respondentov (v takem primeru bomo spremenljivko oznaÃ„Âili tudi kot skrito, saj respondentu ni treba vnaÃ…Â¡ati emaila).\n</UL>\nV primeru uporabe email vabil preko 1KA email sistema, mora biti spremenljivka \"email\" oznaÃ„Âena kot sistemska, ne glede, Ã„Âe je email vnesel respondent sam ali pa ga je pred anketiranjem vnesel administrator.'),
('spremenljivka_visible', 1, 'S klikanjem na nastavitve vidnosti lahko zbiramo med dvema vrstama integrcije vpraÃ…Â¡anja v anketo:\n<UL>\n<LI><b>vidno</b> vpraÃ…Â¡anje, ki bo vidno respondentom v konÃ„Ânem vpraÃ…Â¡alniku,\n<LI><b>skrito</b> vpraÃ…Â¡anje, ki bo vidno le avtorju v urejanju vpraÃ…Â¡alnika. MoÃ…Â¾nost uporabimo bodisi za skriti nagovor bodisi za sistemsko spremenljivko e-mail, ki je respondentom ni potrebno izpolniti.\n'),
('srv_aapor_link', 1, 'AAPOR kalkulacije'),
('srv_activity_quotas', 1, 'Ve? o trajanju ankete in kvotah si lahko preberete <a href=\"https://www.1ka.si/db/24/493/Priro?niki/Trajanje_ankete_glede_na_datum_ali_stevilo_odgovorov_kvote/\" target=\"_blank\">tukaj</a>.'),
('srv_activity_quotas_valid', 1, 'Ve? o statusih enot si lahko preberete <a href=\"https://www.1ka.si/db/24/328/Prirocniki/Statusi_enot_ustreznost_veljavnost_in_manjkajoce_vrednosti/\" target=\"_blank\">tukaj</a>.'),
('srv_alert_show_97', 1, 'Funkcija prikaz \"Neustrezno\" ob opozorilu, da se respondentu prikaÅ¾e odgovor \"Neustrezno\" Å¡ele po tem, ko ta ni odgovoril na vpraÅ¡anje. VpraÅ¡anje mora biti obvezno ali imeti opozorilo.'),
('srv_alert_show_98', 1, 'Funkcija prikaz \"Zavrnil\" ob opozorilu, da se respondentu prikaÅ¾e odgovor \"Zavrnil\" Å¡ele po tem, ko ta ni odgovoril na vpraÅ¡anje. VpraÅ¡anje mora biti obvezno ali imeti opozorilo.'),
('srv_alert_show_99', 1, 'Funkcija prikaz \"Ne vem\" ob opozorilu, da se respondentu prikaÅ¾e odgovor \"Ne vem\" Å¡ele po tem, ko ta ni odgovoril na vpraÅ¡anje. VpraÅ¡anje mora biti obvezno ali imeti opozorilo. \n<a href=\"https://www.1ka.si/a/57119\">Primer ankete >></a>'),
('srv_block_random', 1, 'Randomizacija vsebine bloka'),
('srv_calculation_missing', 1, 'Missing kot 0'),
('srv_choose_skin', 1, 'Izbira predloge'),
('srv_collect_all_status_0', 1, 'Statusi ustrezni so:\n[6] KonÃ¢Ë†Å¡Ãƒâ€˜?al anketo\n[5] Delno izpolnjena'),
('srv_collect_all_status_1', 1, '[6] KonÃ†â€™ÃƒÂ§al anketo\n[5] Delno izpolnjena\n[4] Klik na anketo\n[3] Klik na nagovor\n[2] Napaka pri poA!iljanju e-poA!te\n[1] E-poA!ta poslana (neodgovor)\n[0] E-poA!ta A!e ni bila poslana\n[-1] Neznan status'),
('srv_collect_data_setting', 1, 'Generiranje tabele s podatki:\n\nS poljem \"le ustrezni\" izbiramo med statusi enot, ki se bodo generirali kot potencialni za analize, izvoz podatkov in prikaz vnosov.\nKadar je polje \"le ustrezni\" izbrano se upo&#353;tevajo samo enote z statusom: 6 - Kon&#269;al anketo in 5 - Delno izpolnjena.\n\nS poljem \"meta podatki\" izbiramo ali se generirajo tudi meta podatki kot so: lastnosti ra&#269;unalika, podrobni podatki o e-po&#353;tnih vabilih in telefonskih klicih.'),
('srv_comments_only_unresolved', 1, 'Prikazovanje samo se neresenih komentarjev.'),
('srv_concl_deactivation_text', 1, 'Obvestilo pri deaktivaciji'),
('srv_concl_PDF_link', 1, 'Na koncu ankete prikaÃ…Â¾e ikono s povezavo do PDF dokumenta z odgovori respondenta.'),
('srv_continue_later_setting', 1, 'Opcija za nadaljevanje kasenje'),
('srv_cookie', 1, 'PiÃ…Â¡kotek (cookie) je koda, ki se instalira v raÃ„Âunalnik anketiranca, s Ã„Âimer 1KA lahko anketiranca identificira tudi pri morebitnem ponovljenem poskusu izpolnjevanja anketa. \n \n-        Ã‚Â»do konca izpolnjevanja anketeÃ‚Â« pomeni, da se piÃ…Â¡kotek hrani le v Ã„Âasu trajanja izpolnjevanja vpraÃ…Â¡alnika, zgolj za tehniÃ„Âne potrebe poteka ankete, kjer je treba vsako izpolnjeno stran identificirati in jo pripisati doloÃ„Âenemu respondentu. Ko je izpolnjevanje vpraÃ…Â¡alnika zakljuÃ„Âeno, se piÃ…Â¡kotek izbriÃ…Â¡e. Uporabnik lahko zato iz istega raÃ„Âunalnika neovirano izpolnjuje anketo Ã…Â¡e enkrat.\n\n-        Ã‚Â»do konca sejeÃ‚Â« piÃ…Â¡kotek se hrani za Ã„Âasa trajanja seje brskalnika, torej tudi po zakljuÃ„Âku izpolnjevanja ankete. IzbriÃ…Â¡e se Ã…Â¡ele, ko zapremo brskalnik. V Ã„Âasu trajanja seje brskalnika bo zato tak uporabnik ob ponovnem vraÃ„Âanju na anketo prepoznan  in obravnavan v skladu z nadaljnjimi nastavitvami: bodisi bo dobili obvestilo, da je anketa zanj nedostopna, ker jo je Ã…Â¾e izpolnil, bodisi jo bo lahko popravljal. Ko pa uporabnik zapre brskalnik, je ob ponovnem zagonu brskalnika obravnavan kot nov respondent.\n\n-        Ã‚Â»1 uroÃ‚Â« ali Ã‚Â»1 mesecÃ‚Â« PiÃ…Â¡kotek se hrani Ã…Â¡e eno uro (1 mesec) po zakljuÃ„Âku ankete. V tem Ã„Âasu bo uporabnik bo ob ponovnem vraÃ„Âanju na anketo prepoznan in obravnava v skladu z nadaljnjimi nastavitvami: bodisi bo dobili obvestilo, da je anketa zanj nedostopna, ker jo je Ã…Â¾e izpolnil, bodisi jo bo lahko popravljal.\n'),
('srv_cookie_continue', 1, '?e je vklopljena omejitev, da uporabnik ne more nadaljevati z izpolnjevanjem ankete brez sprejetja piÅ¡kotka, mora biti v anketi obvezno prikazan uvod!'),
('srv_create_form', 1, 'Ustvari formo na eni strani'),
('srv_create_poll', 1, 'Ustvari glasovanje z enim vpraÃ…Â¡anjem'),
('srv_create_survey', 1, 'Ustvari navadno anketo'),
('srv_create_survey_from_text', 1, 'Uvoz nove ankete iz besedila'),
('srv_creport', 1, 'V prilagojenem poro&#269;ilu lahko:<ul style=\"padding-left:15px\"><li>naredite poljuben izbor spremenljivk</li><li>jih urejate v poljubnem vrstnem redu</li><li>kombinirate grafe, frekvence, povpre&#269;ja...</li><li>dodajate komentarje</li></ul><a href=\"http://www.1ka.si/db/19/427/Pogosta%20vpraÃ…Â¡anja/Porocila_po_meri/?&cat=286&p1=226&p2=735&p3=789&p4=794&p5=865&id=865\" target=\"_blank\">Podrobnosti</a>'),
('srv_crosstab_inspect', 1, 'Mo&#382;nost \"Kdo je to\" omogo&#269;a da s klikom na &#382;eleno frekvenco, ogledamo katere enote se v njej nahajajo.<a href=\"http://www.1ka.si/c/836/Kdojeto/?preid=789\" target=\"_blank\">ve&#269;</a>'),
('srv_crosstab_residual', 1, 'Obarvane celice - glede na prilagojene vrednosti rezidualov (Z) - ka&#382;ejo, ali in koliko je v celici vec ali manj enot v primerjavi z razmerami, ko celici nista povezani. Bolj temna barva (rdeca ali modra) torej pomeni, da se v celici nekaj dogaja. Natancne vrednosti residualov dobimo, ce tako izberemo  v NASTAVITVAH. Nadaljnje podrobnosti o izracunavanja in interpetaciji rezidualov so <a href=\"https://www.1ka.si/db/24/308/Priro%C4%8Dniki/Kaj_pomenijo_residuali/\" target=\"_blank\">tukaj</a>'),
('srv_crosstab_residual2', 1, 'Reziduali'),
('srv_data_filter', 1, 'Filtriranje'),
('srv_data_onlyMySurvey', 1, 'Kadar anketo resujete kot uporabnik Sispleta in imate vklopljeno opcijo da anketa prepozna responmdenta iz CMS, Lahko z enostavnim klikom pregledate le vase ankete.'),
('srv_data_only_valid', 1, 'Ustrezne enote so tiste ankete, kjer je respondent odgovoril vsaj na eno vpra&#353;anje. V vseh analizah so privzeto (default) vklju&#269;ene le ustrezne enote. Ostale - za vsebinske analize neustrezne enote Ã¢â‚¬â€œ namre&#269; vklju&#269;ujejo prazne ankete (npr. anketirance, ki so zgolj kliknili na nagovor) in so zanimive predvsem za analizo procesa odgovarjanja Ã¢â‚¬â€œ njihov sumarni pregled pa je v zavihku STATUS.<br><a href=\"http://www.1ka.si/c/837/Statusi_in_manjkajoce_vrednosti/\" target=\"_blank\">Podrobnosti</a>'),
('srv_data_print_preview', 1, 'V hitrem seznamu je izpisanih prvih 5 spremenljivk. Primeren je za hiter izpis prijavnic in form. Za podrobne izpise uporabite obstojeÃ„Âe izvoze. Dodaten izbor spremenljivk lahko naredite v opciji \"Spremenljivke\".<br><br><a href=\"http://www.1ka.si/db/19/381/Pogosta%20vpraÃ…Â¡anja/Hitra_izdelava_seznama_odgovorov/?&cat=292&p1=226&p2=735&p3=789&p4=801&p5=856&id=856\" target=\"_blank\">Podrobnosti</a>'),
('srv_diag_complexity', 1, 'Kompleksnost:\nbrez pogojev ali blokov => zelo enostavna anketa\n1 pogoj ali blok => enostavna anketa\n1-10 pogojev ali blokov => zahtevna anketa\n10-50 pogojev ali blokov => kompleksna anketa \nveÃ„Â kot 50 pogojev ali blokov => zelo kompleksna anketa'),
('srv_diag_time', 1, 'Predviden Ã„Âas izpolnjevanja:\ndo 2 min => zelo kratka anketa\n2-5 min => kratka anketa\n5-10 min => srednje dolga anketa\n10-15 min => dolga anketa\n15-30 min => zelo dolga anketa\n30-45 min => obseÃ…Â¾na anketa\nveÃ„Â kot 45 min => zelo obseÃ…Â¾na anketa'),
('srv_disabled_question', 1, 'Vpra&#154;anje je onemogo&#269;eno za respondente'),
('srv_dostop_password', 1, 'Vsi respondenti morajo vnesti isto geslo.'),
('srv_dostop_password', 2, 'All respondents are asked to insert the same password.'),
('srv_dropdown_quickedit', 1, 'Hitro urejanje'),
('srv_email_server_settings', 1, 'Nastvaitve poÃ…Â¡tnega streÃ…Â¾nika'),
('srv_email_to_list', 1, 'V anketo mora biti dodano sistemsko vpra&#353;anje \"email\". Prav tako mora biti tudi vidno.'),
('srv_email_with_data', 1, 'Povezava emailov s podatki'),
('srv_gdpr_user_options', 1, 'Enkrat leto boste prejeli obvestilo o DSA dogodku.'),
('srv_google_2fa_options', 1, '<a href=\"https://www.1ka.si/d/sl/pomoc/prirocniki/dvo-nivojsko-preverjanje-pristnosti-pri-prijavi-1ka\" target=\"_blank\">https://www.1ka.si/d/sl/pomoc/prirocniki/dvo-nivojsko-preverjanje-pristnosti-pri-prijavi-1ka</a>'),
('srv_grid_var', 1, 'Vrednosti odgovorov so privzeto razvr&#353;&#269;ene nara&#353;&#269;ajo&#269;e in se pri&#269;nejo z 1. Vrednosti se lahko razvrstijo tudi padajo&#269;e (s klikom na checkbox Razvrsti vrednosti padajo&#269;e).<br /><br />Vrednosti odgovorov se lahko spremenijo. Pri tem velja upo&#353;tevati naslednja pravila:<ul><li>Vrednosti se ne smejo ponavljati (razen v primeru vklopljenega modula Kviz).</li><li>Uporabljajo se lahko samo cela &#353;tevila (brez decimalnih &#353;tevil).</li><li>Vrednosti -1, -2, -3, -4, -5, -6, -96, -97, -98, -99 so rezervirane za ozna&#269;evanje manjkajo&#269;ih vrednosti in se jih ne sme uporabljati za vrednotenje drugih odgovorov.</li></ul>'),
('srv_grupe', 1, 'VpraÃ…Â¡alnik je razdeljen na posamezne strani. Vsaka stran naj vsebuje primerno Ã…Â¡tevilo vpraÃ…Â¡anj.\n\nTukaj vidite izpisane vse strani vpraÃ…Â¡alnika, vkljuÃ„Âno z uvodom in zakljuÃ„Âkom.    '),
('srv_grupe_branching', 1, '<LI><b>ANKETA Z VEJITVAMI IN POGOJI:</b> anketni vpraÃ…Â¡alnik potrebuje  preskoke, bloke, gnezdenje pogojev ipd.'),
('srv_hierarchy_admin_help', 1, 'Tukaj lahko odstranjujete celotni nivo ali pa s klikom na checkbox izberete, &#269;e so &#353;ifranti znotraj polja unikatni.'),
('srv_hierarchy_edit_elements', 1, 'Za vsak izbran nivo se lahko dodaja nove elemente. Z izbiro mo&#382;nosti brisanja se izbri&#353;e celoten nivo z vsemi &#353;ifranti. Lahko pa se omenejene elemente ureja in odstrani zgolj poljuben element nivoja.'),
('srv_hierarhy_last_level_missing', 1, 'Na zadnjem nivoju manjka izbran element in elektronski naslov osebe, ki bo preko elektronske po&#353;te dobila kodo za re&#353;evanje ankete.'),
('srv_hotspot_region_color', 1, 'Omogo&#269;a urejanje barve obmo&#269;ja, ko bo to izbrano.'),
('srv_hotspot_tooltip', 1, 'Izberite mo&#382;nosti prikazovanja namigov z imeni obmo&#269;ij.\n\nPrika&#382;i ob mouseover: namig je viden, ko je kurzor mi&#353;ke nad obmo&#269;jem;\nSkrij: namig ni viden;\n'),
('srv_hotspot_tooltip_grid', 1, 'Izberite mo&#382;nosti prikazovanja namigov s kategorijami odgovorov.\n\nPrika&#382;i ob mouseover: kategorije odgovorov so vidne, ko je kurzor mi&#353;ke nad obmo&#269;jem;\nPrika&#382;i ob kliku  mi&#353;ke na obmo&#269;je: kategorije odgovorov so vidne, ko se klikne na obmo&#269;je;\n'),
('srv_hotspot_visibility', 1, 'Izberite tip osvetlitve oz. kako, so obmo&#269;ja vidna ali nevidna respondentom.\n\nSkrij: obmo&#269;je ni vidno;\nPrika&#382;i: obmo&#269;je je vidno;\nPrika&#382;i ob mouseover: obmo&#269;je je vidno, ko je kurzor mi&#353;ke nad obmo&#269;jem;\n'),
('srv_hotspot_visibility_color', 1, 'Omogo&#269;a urejanje barve osvetlitve obmo&#269;ja.'),
('srv_invitation_rename_profile', 1, 'Vsak vneÃ…Â¡en email se privzeto shrani v zaÃ„Âasen seznam, katerega pa lahko preimenujete tudi drugaÃ„Âe. Nove emaile pa lahko dodate tudi v obstojeÃ„Âe sezname.'),
('srv_inv_activate_1', 1, 'Z izbiro \"email vabil z individualiziranim URL\" se avtomati&#269;no vklopi opcija \"Da\" za individualizirana vabila, za vnos kode pa \"Avtomatsko v URL\". Respondentom bo sistem 1KA lahko poslal email, v katerem bo individualiziran URL naslovom ankete. &#268;im bo respondent na URL kliknil, bo sistem 1KA sledil respondenta.'),
('srv_inv_activate_2', 1, 'Z izbiro \"ro&#269;ni vnos individualizirane kode\" se avtomati&#269;no vklopi opcija \"Da\" za individulaizirana vabila ter opcija \"Ro&#269;ni vnos\" za vnos kode. Respondenti bodo prejeli enak URL, na za&#269;etku pa bodo morali ro&#269;no vnesti svojo individualno kodo. Vabilo s kodo se lahko respondentu po&#353;lje z emailom preko sistemom 1KA. Lahko pa se po&#353;lje  tudi eksterno (izven sistema 1KA): z dopisom preko po&#353;te, s SMS sporo&#269;ilom kako druga&#269;e; v takem primeru sistem 1KA zgolj zabele&#382;i kdo, kdaj in kako je poslal vabilo.'),
('srv_inv_activate_3', 1, 'Z izbiro \"uporabe splo&#353;nih vabil brez individulaizirane kode\" opcija \"email vabila z individualiziranim URL\" ostaja izklopljena (\"Ne\"). Sistem 1KA bo respondenom lahko poslal emaile, ki pa ne bo imeli individulaizranega URL oziroma individualizirane kode.'),
('srv_inv_activate_4', 1, 'Z izbiro \"Email vabila z ro&#269;nim vnosom kode\" se vklopi opcija \"Da\" za individualizirana vabila, za vnos kode pa \"Ro&#269;ni vnos\". Respondentom bo sistem 1KA lahko poslal email, v katerem bo individualizirana koda, URL naslov ankete pa bo enoten. Ko bo respondent kliknil na URL kliknil, se bo prikazal zahtevek za vnos kode, ki jo je prejel po emailu.'),
('srv_inv_archive_sent', 1, 'Klik na &#353;tevilo poslanih vabil prika&#382;e podroben pregled poslanih vabil'),
('srv_inv_cnt_by_sending', 1, 'Tabela pove, koliko enotam email &#353;e ni bil poslan (0), koliko enot je dobilo email enkrat (1), koliko dvakrat (2), koliko trikrat (3) ...'),
('srv_inv_general_settings', 1, 'Splosne nastavitve vabil'),
('srv_inv_message_title', 1, 'Sporo&#269;ilo, ki bo poslano po emailu'),
('srv_inv_message_title_noEmail', 1, 'Sporo&#269;ilo, ki bo poslano po navadni po&#353;ti ali SMS-u in bo v 1ki dokumentirano'),
('srv_inv_no_code', 1, 'Poslji brez kode'),
('srv_inv_recipiens_add_invalid_note', 1, '&#268;e &#382;elite dodati enote, ki nimajo emailov, naredite lo&#269;en seznam.'),
('srv_inv_sending_comment', 1, 'Zabele&#382;imo lahko kako posebnost ali zna&#269;ilnost (npr. preliminarno obvestilo, prvo vabilo, opomnik ipd). V primeru ro&#269;nega po&#353;iljanja je priporo&#269;ljivo navesti dejanski dan odpo&#353;iljanja (npr. preko po&#353;te), saj se lahko razlikuje od datuma priprave seznama.'),
('srv_inv_sending_double', 1, 'Odstranjujejo se podvojeni zapisi glede na email'),
('srv_inv_sending_type', 1, 'Nacin posiljanja'),
('srv_item_nonresponse', 1, 'Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).<br> Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) )'),
('srv_item_nonresponse', 2, 'Neodgovor spremenljivke pomeni koliko ustreznih odgovorov smo dobili glede na skupno &#353;tevilo enot, ki so dobile dolo&#269;eno vpra&#353;anje. Ali druga&#269;e: od vseh ustreznih enot, ki so dobile to vpra&#353;anje, so izlo&#269;eni statusi (-1).<br> Izra&#269;unan je po formuli: (-1) * 100 / ( (veljavni) + (-1) + (-97) + (-98) + (-99) )'),
('srv_mail_mode', 1, 'TODO: PodrobnejÃ…Â¡i opis za posamezne nastavitve... + link na FAQ.'),
('srv_missing_values', 1, 'Manjkajoce vrednosti'),
('srv_moje_ankete_setting', 1, 'Moje ankete'),
('srv_namig_setting', 1, 'Namig'),
('srv_nice_url', 1, 'Lepi link'),
('srv_novagrupa', 1, 'S klikom na <b>Nova stran</b> dodate v vpraÃ…Â¡alnik novo stran, ki se postavi pred zakljuÃ„Âek in jo nato lahko poljubno uredite in premikate. '),
('srv_para_graph_link', 1, 'Parapodatki'),
('srv_para_neodgovori_link', 1, 'Neodgovor spremenljivke'),
('srv_podatki_urejanje_inline', 1, 'Vklju&#269;ili ste tudi neposredno urejanje v pregledovalniku. <br/>V kolikor &#382;elite vrednosti vpra&#353;anja izbirati iz rolete lahko to nastavite v urejanju kot napredno nastavitev vpra&#353;anja.'),
('srv_podatki_urejanje_inline', 2, 'Vklju&#269;ili ste tudi neposredno urejanje v pregledovalniku. <br/>V kolikor &#382;elite vrednosti vpra&#353;anja izbirati iz rolete lahko to nastavite v urejanju kot napredno nastavitev vpra&#353;anja.'),
('srv_privacy_setting', 1, 'Politika zasebnosti'),
('srv_recode_advanced_edit', 1, 'Napredno urejanje, kot je dodajanje, preimenovanje in brisanje kategorij je na voljo v urejanju vpra&#353;alnika.'),
('srv_recode_chart_advanced', 1, 'Osnovno rekodiranje je primerno, da se starost, katera je veÃ„Âja od 100 rekodira v -97 katero je neustrezno. Oziroma da se odgovori 9 - ne vem rekodirajo v neustrezno.'),
('srv_recode_h_actions', 1, 'Funkcije rekodiranja so: <ul><li>Dodaj - odpre okno za dodajanje rekodiranje za posamezno variablo</li><li>Uredi - prikaÃ…Â¾e okno za urejane rekodiranja posamezne variable</li><li>Odstrani - odstrano oziroma v celoti izbri&#353;e rekodiranje posamezne variable</li><li>Omogo&#269;eno - trenutno omogo&#269;i oziroma onemogo&#269;i rekodiranje posamezne variable</li><li>Vidna - nastavi variablo vidno oziroma nevidno v vpra&#353;alniku</li></ul>'),
('srv_reminder_tracking_quality', 1, 'Kakovostni indeks = 1 - ( &sum;(&#353;tevilo spro&#382;enih opozoril/&#353;tevilo mo&#382;nih opozoril po vrsti opozorila) / &#353;tevilo respondentov ) </br> Haraldsen, G. (2005). Using Client Side Paradata as Process Quality Indicators in Web Surveys. Predstavljeno na delavnici ESF Workshop on Internet survey methodology, Dubrovnik, 26-28 September 2005.'),
('srv_reminder_tracking_quality', 2, 'Quality index = 1 - ( &sum;(Activated errors/Possible errors) / Number of respondents ) </br> Haraldsen, G. (2005). Using Client Side Paradata as Process Quality Indicators in Web Surveys. Presented at the ESF Workshop on Internet survey methodology, Dubrovnik, 26-28 September 2005.'),
('srv_show_progressbar', 1, 'Prika&#382;e indikator napredka na vrhu ankete. Vklop je mo&#382;en samo, &#269;e ima anketa ve&#269; strani.'),
('srv_sistemska_edit', 1, 'Sistemska spremenljivka'),
('srv_skala_edit', 1, '<b>Ordinalna skala:</b> Kategorije odgovorov je mogoce primerjati; racunamo lahko tudi povprecje. Npr. lestvice na skalah (strinjanje, zadovoljstvo,Ã¢â‚¬Â¦)</br><b>Nominalna skala:</b> Kategorij odgovorov ni mogoce primerjati niti ni mogoce racunati povprecij. Npr. spol, barva, regija, drÃ…Â¾ava.'),
('srv_skala_text_nom', 1, 'Nominalna skala'),
('srv_skala_text_ord', 1, 'Ordinalna skala'),
('srv_skins_Embed', 1, 'Za ankete, ki so vklju?ene v drugo spletno stran.'),
('srv_skins_Embed2', 1, 'Za ankete, ki so vklju?ene v drugo spletno stran (oÅ¾ja razli?ica).'),
('srv_skins_Fdv', 1, 'Samo za uporabnike, ki imajo dovoljenje s strani FDV.'),
('srv_skins_Slideshow', 1, 'Za prezentacijo.'),
('srv_skins_Uni', 1, 'Samo za uporabnike, ki imajo dovoljenje s strani FDV.'),
('srv_skupine', 1, 'Skupine'),
('srv_spremenljivka_lock', 1, 'Zaklenjeno vpraÃ…Â¡anje lahko ureja samo avtor ankete.'),
('srv_statistika', 1, 'Opcija ki se sicer redko uporablja, prikaze rezultate odgovora na naslednji strani.'),
('srv_survey_type', 1, '1KA upoÃ…Â¡teva, da enostavne ankete zahtevajo drugaÃ„Âen vmesnik kot kompleksne. \n\n1KA zato omogoÃ„Âa, da lahko vedno izberete optimalni vmesnik, paÃ„Â glede na zahtevnost ankete, ki jo potrebujete: \n\n<LI><b>GLASOVANJE:</b> anketa z enim samim vpraÃ…Â¡anjem, volitve, ipd., vendar z moÃ…Â¾nostjo sprotnega prikaza rezultatov.\n\n<LI><b>FORMA</b> kratek enostranski vpraÃ…Â¡alnik, forma, registracija, obrazec, email lista, prijava na dogodek ipd.\n\n<LI><b>ANKETA NA VEÃ„Å’ STRANEH:</b> daljÃ…Â¡a anketa s poljubnim Ã…Â¡tevilo strani, vendar brez preskokov in pogojev.\n\n<LI><b>ANKETA S POGOJI IN BLOKI:</b> anketni vpraÃ…Â¡alnik potrebuje  preskoke, bloke, gnezdenje pogojev ipd.\n\nMed vmesniki lahko preklapljate tudi kasneje, razen seveda v primeru, ko bi prehod pomenil izbris doloÃ„Âenih podatkov. Tako v primeru, ko imate pogoje, ni veÃ„Â mogoÃ„Âe prehod na enostavnejÃ…Â¡e vmesnike. Podobno iz veÃ„Âstranske ankete ni mogoÃ„Â prehod v formo, je pa mogoÃ„Â seveda prehod v anketo s pogoji.'),
('srv_telephone_help', 1, 'Ve&#269; o telefonski anketi si lahko preberete v priro&#269;niku. <a href=\"http://www.1ka.si/c/834/Telefonska_anketa/?preid=824&from1ka=1\" target=\"_blank\">Ve&#269; >></a>'),
('srv_toolbox_add_advanced', 1, 'Dodaj tip vpraÃ…Â¡anja'),
('srv_user_base_individual_invitaition_note', 1, 'Individualizirana vabila omogo&#269;ajo sledenje respondentom.'),
('srv_user_base_individual_invitaition_note2', 1, 'Z izbiro \"Ne\" je modul individualiziranih vabil izklopljen. Anketira se lahko vsak, ki vidi ali pozna URL naslov. Respondentov v takem primeru ne moremo slediti; ne vemo kdo je odgovoril in kdo ne.<br /><br />Sistem 1KA lahko kljub temu po&#353;lje (email) oziroma dokumentira (po&#353;ta, SMS, drugo) po&#353;iljanje splo&#353;nega ne-individualiziranega vabila, kjer vsi respondenti prejmejo enotni URL. To pomeni, da se zabele&#382;ilo, komu, kdaj in kako je bilo vabilo poslano, ne bo pa ozna&#269;eno, kdo je odgovoril in kdo ne.'),
('srv_vprasanje_max_marker_map', 1, '&#352;tevilo najve&#269; mo&#382;nih oddanih odgovorov/markerjev na zemljevidu'),
('srv_vprasanje_tracking_setting', 1, 'Arhiviranje vpraÃ…Â¡anj'),
('srv_window_help', 1, 'Urejanje oken s pomoÃ„Âjo'),
('toolbox_advanced', 1, 'Pri anketi z ve&#269;jim &#353;tevilom vpra&#353;anj vam priporo&#269;amo, da anketo razdelite na vsebinsko smiselne bloke. \n\nBloke lahko po potrebi zapirate in razpirate ter si s tem omogo&#269;ite bolj&#353;i pregled nad anketo.'),
('usercode_required', 1, '<ul><li><b>\"Avtomatsko\"</b> - geslo se samodejno prenese iz povezave email vabila</li><li><b>\"Ro&#269;no\"</b> uporabnik mora ro&#269;no vnesti geslo</li><ul/>'),
('usercode_skip', 1, 'Dostop brez gesla:<br/><ul><li><b>Ne</b> - Za izpolnjevanje ankete je potrebno bodisi pridobiti email vabilo kjer kliknemo na povezavo s katero se koda avtomatsko prenese v anketo, bodisi pa mora respondent poznati kodo in jo ro&#269;no vnesti v anketo.</li><li><b>Da</b> - Anketo lahko izpolnjujejo tudi uporabniki kateri niso prejeli email vabila - kode.</li><li><b>Samo avtor</b> - Polek uporabnikov, ki imajo kodo lahko anketo brez vabila (brez vnosa kode) izpolnjujejo tudi avtorji ankete (predvsem v testne namene).</li></ul>'),
('user_location_map', 1, 'Brskalnik bo poskusil ugotoviti trenutno lokacijo respondenta. Respondenta bo brskalnik najprej vpra&#353;al za dovoljenje deljenja njegove lokacije.');

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_dostop`
--

CREATE TABLE `srv_hierarhija_dostop` (
  `user_id` int(11) NOT NULL,
  `dostop` tinyint(4) DEFAULT '0',
  `ustanova` varchar(255) DEFAULT NULL,
  `aai_email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
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
  `option_value` longtext
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
  `unikaten` int(11) DEFAULT '0'
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
  `hierarhija` longtext,
  `struktura` longtext,
  `st_uciteljev` int(11) DEFAULT NULL,
  `st_vseh_uporabnikov` int(11) DEFAULT NULL,
  `komentar` text,
  `logo` varchar(255) DEFAULT NULL,
  `uporabniki_list` text
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
  `datetime` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hierarhija_users`
--

CREATE TABLE `srv_hierarhija_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `anketa_id` int(11) NOT NULL,
  `type` tinyint(4) DEFAULT '10'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_hotspot_regions`
--

CREATE TABLE `srv_hotspot_regions` (
  `id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL DEFAULT '0',
  `spr_id` int(11) NOT NULL DEFAULT '0',
  `region_name` text NOT NULL,
  `region_coords` text NOT NULL,
  `region_index` int(11) NOT NULL,
  `variable` varchar(15) NOT NULL DEFAULT '',
  `vrstni_red` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_if`
--

CREATE TABLE `srv_if` (
  `id` int(11) NOT NULL,
  `number` int(11) NOT NULL DEFAULT '0',
  `tip` tinyint(4) NOT NULL DEFAULT '0',
  `label` varchar(200) CHARACTER SET utf8 DEFAULT NULL,
  `collapsed` tinyint(4) NOT NULL DEFAULT '0',
  `folder` int(11) NOT NULL DEFAULT '0',
  `enabled` enum('0','1','2') NOT NULL,
  `tab` enum('0','1') NOT NULL,
  `horizontal` enum('0','1','2') NOT NULL DEFAULT '0',
  `random` int(11) NOT NULL DEFAULT '-1',
  `thread` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_if`
--

INSERT INTO `srv_if` (`id`, `number`, `tip`, `label`, `collapsed`, `folder`, `enabled`, `tab`, `horizontal`, `random`, `thread`) VALUES
(0, 0, 0, 'system', 0, 0, '0', '0', '0', -1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `srv_invitations_archive`
--

CREATE TABLE `srv_invitations_archive` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `date_send` datetime DEFAULT NULL,
  `subject_text` varchar(100) NOT NULL,
  `body_text` mediumtext,
  `tip` tinyint(4) NOT NULL DEFAULT '-1',
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
  `spr_id` int(11) NOT NULL DEFAULT '0',
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
  `body_text` mediumtext,
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
  `last_status` tinyint(4) NOT NULL DEFAULT '0',
  `inserted_uid` int(11) NOT NULL DEFAULT '0',
  `list_id` int(11) NOT NULL DEFAULT '0'
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
  `language` varchar(255) NOT NULL
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
  `naslov` text NOT NULL,
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
  `label` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_language_spremenljivka`
--

CREATE TABLE `srv_language_spremenljivka` (
  `ank_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `naslov` text NOT NULL,
  `info` text NOT NULL,
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
  `naslov` text NOT NULL,
  `naslov2` text NOT NULL
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
  `uid` int(11) NOT NULL DEFAULT '0',
  `tip` tinyint(4) NOT NULL DEFAULT '0',
  `naslov` varchar(50) NOT NULL,
  `parent` int(11) NOT NULL,
  `lang` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_library_folder`
--

INSERT INTO `srv_library_folder` (`id`, `uid`, `tip`, `naslov`, `parent`, `lang`) VALUES
(1, 0, 0, 'Sistemske', 0, 1),
(2, 0, 1, 'Public surveys', 0, 2),
(3, 0, 0, 'Public questions', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `srv_lock`
--

CREATE TABLE `srv_lock` (
  `lock_key` varchar(32) NOT NULL,
  `locked` enum('0','1') NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0',
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
  `table_id` int(11) NOT NULL DEFAULT '0',
  `spr` varchar(255) NOT NULL DEFAULT '',
  `parent` varchar(255) NOT NULL DEFAULT '',
  `vrstni_red` int(11) NOT NULL DEFAULT '0',
  `position` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_mc_table`
--

CREATE TABLE `srv_mc_table` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0',
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
  `value` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `srv_misc`
--

INSERT INTO `srv_misc` (`what`, `value`) VALUES
('export_data_font_size', '10'),
('export_data_numbering', '0'),
('export_data_PB', '1'),
('export_data_show_if', '0'),
('export_data_show_recnum', '0'),
('export_data_skip_empty', '1'),
('export_data_skip_empty_sub', '1'),
('export_font_size', '10'),
('export_numbering', '1'),
('export_show_if', '1'),
('export_show_intro', '1'),
('mobile_friendly', '1'),
('mobile_tables', '1'),
('question_comment_text', 'Va&#154; komentar k vpra&#154;anju'),
('timing_kategorija_1', '5'),
('timing_kategorija_16', '10'),
('timing_kategorija_17', '5'),
('timing_kategorija_18', '5'),
('timing_kategorija_19', '20'),
('timing_kategorija_2', '5'),
('timing_kategorija_20', '20'),
('timing_kategorija_3', '5'),
('timing_kategorija_6', '10'),
('timing_kategorija_max_3', '20'),
('timing_stran', '5'),
('timing_vprasanje_1', '10'),
('timing_vprasanje_16', '10'),
('timing_vprasanje_17', '10'),
('timing_vprasanje_18', '10'),
('timing_vprasanje_19', '10'),
('timing_vprasanje_2', '10'),
('timing_vprasanje_20', '10'),
('timing_vprasanje_21', '20'),
('timing_vprasanje_3', '10'),
('timing_vprasanje_4', '20'),
('timing_vprasanje_5', '10'),
('timing_vprasanje_6', '10'),
('timing_vprasanje_7', '10'),
('timing_vprasanje_8', '10');

-- --------------------------------------------------------

--
-- Table structure for table `srv_missing_profiles`
--

CREATE TABLE `srv_missing_profiles` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT '0',
  `display_mv_type` tinyint(4) NOT NULL DEFAULT '0',
  `merge_missing` tinyint(1) NOT NULL DEFAULT '0',
  `show_zerro` tinyint(1) NOT NULL DEFAULT '0'
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
  `type` tinyint(1) NOT NULL DEFAULT '3',
  `value` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '-99',
  `text` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'Ne vem',
  `active` tinyint(1) NOT NULL DEFAULT '1',
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

INSERT INTO `srv_module` (`id`, `module_name`, `active`) VALUES
(1, 'hierarhija', '0'),
(2, 'evalvacija', '0'),
(3, '360', '0'),
(4, 'evoli', '0'),
(5, 'gfksurvey', '0'),
(6, 'mfdps', '0'),
(7, '360_1ka', '0'),
(8, 'mju', '0'),
(9, 'evoli_teammeter', '0'),
(10, 'maza', '0'),
(11, 'excell_matrix', '0'),
(12, 'gorenje', '0'),
(13, 'borza', '0'),
(14, 'advanced_paradata', '0'),
(15, 'wpn', '0'),
(16, 'evoli_employmeter', '0');

-- --------------------------------------------------------

--
-- Table structure for table `srv_mysurvey_anketa`
--

CREATE TABLE `srv_mysurvey_anketa` (
  `ank_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `folder` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_mysurvey_folder`
--

CREATE TABLE `srv_mysurvey_folder` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL DEFAULT '0',
  `parent` int(11) NOT NULL DEFAULT '0',
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
  `recipient` int(11) NOT NULL DEFAULT '0',
  `viewed` enum('0','1') NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_notifications_messages`
--

CREATE TABLE `srv_notifications_messages` (
  `id` int(11) NOT NULL,
  `author` int(11) NOT NULL DEFAULT '0',
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
  `ank_id` int(11) NOT NULL DEFAULT '0',
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
  `spr_id` int(11) NOT NULL DEFAULT '0',
  `vre_id` int(11) NOT NULL DEFAULT '0'
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
  `value` int(11) NOT NULL DEFAULT '0',
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
  `recode_type` tinyint(1) NOT NULL DEFAULT '0',
  `to_spr_id` int(11) NOT NULL DEFAULT '0',
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
  `fixed_interval` tinyint(1) NOT NULL DEFAULT '0',
  `timer` mediumint(9) NOT NULL DEFAULT '5',
  `save_entries` tinyint(4) NOT NULL DEFAULT '0',
  `autostart` tinyint(4) NOT NULL DEFAULT '0',
  `next_btn` tinyint(4) NOT NULL DEFAULT '1',
  `back_btn` tinyint(4) NOT NULL DEFAULT '1',
  `pause_btn` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_specialdata_vrednost`
--

CREATE TABLE `srv_specialdata_vrednost` (
  `spr_id` int(11) NOT NULL DEFAULT '0',
  `vre_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_spremenljivka`
--

CREATE TABLE `srv_spremenljivka` (
  `id` int(11) NOT NULL,
  `gru_id` int(11) NOT NULL DEFAULT '0',
  `naslov` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `info` text CHARACTER SET utf8,
  `variable` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `variable_custom` tinyint(1) NOT NULL DEFAULT '0',
  `label` varchar(200) CHARACTER SET utf8 DEFAULT NULL,
  `tip` tinyint(4) NOT NULL DEFAULT '0',
  `vrstni_red` int(11) NOT NULL DEFAULT '0',
  `random` tinyint(4) NOT NULL DEFAULT '0',
  `size` tinyint(4) NOT NULL DEFAULT '5',
  `undecided` tinyint(4) NOT NULL DEFAULT '0',
  `rejected` tinyint(4) NOT NULL DEFAULT '0',
  `inappropriate` tinyint(4) NOT NULL DEFAULT '0',
  `stat` int(11) NOT NULL DEFAULT '0',
  `orientation` tinyint(1) NOT NULL DEFAULT '1',
  `checkboxhide` tinyint(1) NOT NULL DEFAULT '0',
  `reminder` tinyint(4) NOT NULL DEFAULT '0',
  `alert_show_99` enum('0','1') NOT NULL DEFAULT '0',
  `alert_show_98` enum('0','1') NOT NULL DEFAULT '0',
  `alert_show_97` enum('0','1') NOT NULL DEFAULT '0',
  `visible` tinyint(4) NOT NULL DEFAULT '1',
  `locked` enum('0','1') NOT NULL DEFAULT '0',
  `textfield` tinyint(4) NOT NULL DEFAULT '0',
  `textfield_label` varchar(250) NOT NULL,
  `cela` tinyint(4) NOT NULL DEFAULT '4',
  `decimalna` tinyint(4) NOT NULL DEFAULT '0',
  `enota` tinyint(1) NOT NULL DEFAULT '0',
  `timer` mediumint(9) NOT NULL DEFAULT '0',
  `sistem` tinyint(4) NOT NULL DEFAULT '0',
  `folder` int(11) NOT NULL DEFAULT '1',
  `params` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `antonucci` tinyint(1) NOT NULL DEFAULT '0',
  `design` tinyint(1) NOT NULL DEFAULT '0',
  `podpora` tinyint(1) NOT NULL DEFAULT '0',
  `grids` tinyint(4) NOT NULL DEFAULT '5',
  `grids_edit` tinyint(4) NOT NULL DEFAULT '0',
  `grid_subtitle1` text CHARACTER SET utf8,
  `grid_subtitle2` text CHARACTER SET utf8,
  `ranking_k` tinyint(4) NOT NULL DEFAULT '0',
  `vsota` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `vsota_limit` int(11) NOT NULL DEFAULT '0',
  `vsota_min` int(11) NOT NULL DEFAULT '0',
  `skala` tinyint(4) NOT NULL DEFAULT '-1',
  `vsota_reminder` tinyint(4) NOT NULL DEFAULT '0',
  `vsota_limittype` tinyint(4) NOT NULL DEFAULT '0',
  `vsota_show` tinyint(4) NOT NULL DEFAULT '1',
  `num_useMax` enum('0','1') NOT NULL DEFAULT '0',
  `num_useMin` enum('0','1') NOT NULL DEFAULT '0',
  `num_min2` int(11) NOT NULL DEFAULT '0',
  `num_max2` int(11) NOT NULL DEFAULT '0',
  `num_useMax2` enum('0','1') NOT NULL DEFAULT '0',
  `num_useMin2` enum('0','1') NOT NULL DEFAULT '0',
  `thread` int(11) NOT NULL DEFAULT '0',
  `text_kosov` tinyint(4) NOT NULL DEFAULT '1',
  `text_orientation` tinyint(4) NOT NULL DEFAULT '0',
  `note` text CHARACTER SET utf8,
  `upload` tinyint(4) NOT NULL DEFAULT '0',
  `signature` tinyint(4) NOT NULL DEFAULT '0',
  `dostop` tinyint(4) NOT NULL DEFAULT '4',
  `inline_edit` tinyint(4) NOT NULL DEFAULT '0',
  `onchange_submit` tinyint(4) NOT NULL DEFAULT '0',
  `hidden_default` tinyint(4) NOT NULL DEFAULT '0',
  `naslov_graf` varchar(255) NOT NULL DEFAULT '',
  `edit_graf` tinyint(4) NOT NULL DEFAULT '0',
  `wide_graf` tinyint(4) NOT NULL DEFAULT '1',
  `coding` int(11) NOT NULL DEFAULT '0',
  `dynamic_mg` enum('0','1','2','3','4','5','6') NOT NULL DEFAULT '0',
  `showOnAllPages` enum('0','1') NOT NULL DEFAULT '0',
  `skupine` enum('0','1','2','3') NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_spremenljivka`
--

INSERT INTO `srv_spremenljivka` (`id`, `gru_id`, `naslov`, `info`, `variable`, `variable_custom`, `label`, `tip`, `vrstni_red`, `random`, `size`, `undecided`, `rejected`, `inappropriate`, `stat`, `orientation`, `checkboxhide`, `reminder`, `alert_show_99`, `alert_show_98`, `alert_show_97`, `visible`, `locked`, `textfield`, `textfield_label`, `cela`, `decimalna`, `enota`, `timer`, `sistem`, `folder`, `params`, `antonucci`, `design`, `podpora`, `grids`, `grids_edit`, `grid_subtitle1`, `grid_subtitle2`, `ranking_k`, `vsota`, `vsota_limit`, `vsota_min`, `skala`, `vsota_reminder`, `vsota_limittype`, `vsota_show`, `num_useMax`, `num_useMin`, `num_min2`, `num_max2`, `num_useMax2`, `num_useMin2`, `thread`, `text_kosov`, `text_orientation`, `note`, `upload`, `signature`, `dostop`, `inline_edit`, `onchange_submit`, `hidden_default`, `naslov_graf`, `edit_graf`, `wide_graf`, `coding`, `dynamic_mg`, `showOnAllPages`, `skupine`, `timestamp`) VALUES
(-4, 0, 'system', '', '', 0, '', 0, 0, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, '', 0, 1, 0, '0', '0', '0', '2018-03-05 09:50:11'),
(-3, 0, 'system', '', '', 0, '', 0, 0, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, '', 0, 1, 0, '0', '0', '0', '2018-03-05 09:50:11'),
(-2, 0, 'system', '', '', 0, '', 0, 1, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, 'system', 0, 0, 0, '0', '0', '0', '2018-03-05 09:50:11'),
(-1, 0, 'system', '', '', 0, '', 0, 2, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, 'system', 0, 0, 0, '0', '0', '0', '2018-03-05 09:50:11'),
(0, 0, 'system', '', '', 0, '', 0, 3, 0, 5, 0, 0, 0, 0, 1, 0, 0, '0', '0', '0', 1, '0', 0, '', 4, 0, 0, 0, 0, 1, '', 0, 0, 0, 5, 0, '', '', 0, '', 0, 0, -1, 0, 0, 1, '0', '0', 0, 0, '0', '0', 0, 1, 0, '', 0, 0, 4, 0, 0, 0, 'system', 0, 0, 0, '0', '0', '0', '2018-03-05 09:50:11');

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
-- Table structure for table `srv_statistic_profile`
--

CREATE TABLE `srv_statistic_profile` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
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
  `uid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT '0',
  `statusnull` tinyint(1) NOT NULL DEFAULT '0',
  `status0` tinyint(1) NOT NULL DEFAULT '0',
  `status1` tinyint(1) NOT NULL DEFAULT '0',
  `status2` tinyint(1) NOT NULL DEFAULT '0',
  `status3` tinyint(1) NOT NULL DEFAULT '0',
  `status4` tinyint(1) NOT NULL DEFAULT '0',
  `status5` tinyint(1) NOT NULL DEFAULT '0',
  `status6` tinyint(1) NOT NULL DEFAULT '0',
  `statuslurker` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `srv_status_casi`
--

INSERT INTO `srv_status_casi` (`id`, `uid`, `name`, `system`, `statusnull`, `status0`, `status1`, `status2`, `status3`, `status4`, `status5`, `status6`, `statuslurker`) VALUES
(1, 0, 'Koncal anketo', 1, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(2, 0, 'Vsi statusi', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `srv_status_profile`
--

CREATE TABLE `srv_status_profile` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '0',
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT '0',
  `statusnull` tinyint(1) NOT NULL DEFAULT '0',
  `status0` tinyint(1) NOT NULL DEFAULT '0',
  `status1` tinyint(1) NOT NULL DEFAULT '0',
  `status2` tinyint(1) NOT NULL DEFAULT '0',
  `status3` tinyint(1) NOT NULL DEFAULT '0',
  `status4` tinyint(1) NOT NULL DEFAULT '0',
  `status5` tinyint(1) NOT NULL DEFAULT '0',
  `status6` tinyint(1) NOT NULL DEFAULT '0',
  `statuslurker` tinyint(1) NOT NULL DEFAULT '0',
  `statustestni` tinyint(1) NOT NULL DEFAULT '2',
  `statusnonusable` tinyint(1) NOT NULL DEFAULT '1',
  `statuspartusable` tinyint(1) NOT NULL DEFAULT '1',
  `statususable` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `srv_status_profile`
--

INSERT INTO `srv_status_profile` (`id`, `uid`, `ank_id`, `name`, `system`, `statusnull`, `status0`, `status1`, `status2`, `status3`, `status4`, `status5`, `status6`, `statuslurker`, `statustestni`, `statusnonusable`, `statuspartusable`, `statususable`) VALUES
(1, 0, 0, 'Vsi statusi', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2, 1, 1, 1),
(2, 0, 0, 'Ustrezni', 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 2, 1, 1, 1),
(3, 0, 0, 'Koncani', 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 2, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `srv_survey_conditions`
--

CREATE TABLE `srv_survey_conditions` (
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `if_id` int(11) NOT NULL DEFAULT '0',
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
  `answers` int(11) NOT NULL DEFAULT '0',
  `variables` int(11) NOT NULL DEFAULT '0',
  `approp` int(11) NOT NULL DEFAULT '0',
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
  `value` longtext
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
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `filter` varchar(100) COLLATE utf8_bin NOT NULL,
  `text` varchar(200) COLLATE utf8_bin NOT NULL,
  `uid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `srv_sys_filters`
--

INSERT INTO `srv_sys_filters` (`id`, `fid`, `type`, `filter`, `text`, `uid`) VALUES
(1, '-1', 1, '-1', 'Ni odgovoril', 0),
(2, '-2', 1, '-2', 'Preskok (if)', 0),
(3, '-3', 1, '-3', 'Prekinjeno', 0),
(4, '-4', 1, '-4', 'Naknadno vprasanje', 0),
(5, '99', 2, '-99', 'Ne vem', 0),
(6, '98', 2, '-98', 'Zavrnil', 0),
(7, '97', 2, '-97', 'Neustrezno', 0),
(8, '-5', 1, '-5', 'Prazna enota', 0);

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
  `survey_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
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
  `survey_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status_z` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status_n` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status_d` int(10) NOT NULL DEFAULT '0',
  `max_calls` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `call_order` int(11) NOT NULL DEFAULT '0'
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
  `uid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `starts` datetime DEFAULT NULL,
  `ends` datetime DEFAULT NULL,
  `interval_txt` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_tracking`
--

CREATE TABLE `srv_tracking` (
  `ank_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `ip` varchar(16) NOT NULL,
  `user` int(11) NOT NULL,
  `get` text NOT NULL,
  `post` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `time_seconds` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `time_seconds` float NOT NULL DEFAULT '0'
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
  `kategorija` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user`
--

CREATE TABLE `srv_user` (
  `id` int(11) NOT NULL,
  `ank_id` int(11) NOT NULL,
  `preview` tinyint(4) NOT NULL DEFAULT '0',
  `testdata` tinyint(4) NOT NULL DEFAULT '0',
  `email` varchar(100) NOT NULL,
  `cookie` char(32) NOT NULL,
  `pass` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(20) NOT NULL,
  `time_insert` datetime NOT NULL,
  `time_edit` datetime NOT NULL,
  `recnum` int(11) NOT NULL DEFAULT '0',
  `javascript` tinyint(4) NOT NULL,
  `useragent` varchar(250) NOT NULL,
  `device` enum('0','1','2','3') NOT NULL DEFAULT '0',
  `browser` varchar(250) NOT NULL,
  `os` varchar(250) NOT NULL,
  `referer` varchar(250) NOT NULL,
  `last_status` tinyint(4) NOT NULL DEFAULT '-1',
  `lurker` tinyint(4) NOT NULL DEFAULT '1',
  `unsubscribed` tinyint(4) NOT NULL DEFAULT '0',
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

INSERT INTO `srv_userbase_invitations` (`id`, `name`, `subject`, `text`) VALUES
(1, 'Privzet text', 'Spletna anketa', '<p>Prosimo, &#269;e si vzamete nekaj minut in izpolnite spodnjo anketo.</p><p>Hvala.</p><p>#URL#</p>');

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
  `tip` tinyint(4) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `datetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_grupa`
--

CREATE TABLE `srv_user_grupa` (
  `gru_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `time_edit` datetime NOT NULL,
  `preskocena` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_grupa_active`
--

CREATE TABLE `srv_user_grupa_active` (
  `gru_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `time_edit` datetime NOT NULL,
  `preskocena` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_user_session`
--

CREATE TABLE `srv_user_session` (
  `ank_id` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) NOT NULL DEFAULT '0',
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
  `survey_list_rows_per_page` int(11) NOT NULL DEFAULT '25',
  `survey_list_visible` varchar(255) NOT NULL DEFAULT '',
  `survey_list_widths` varchar(255) NOT NULL DEFAULT '',
  `icons_always_on` tinyint(1) NOT NULL DEFAULT '0',
  `full_screen_edit` tinyint(1) NOT NULL DEFAULT '0',
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
  `value` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_validation`
--

CREATE TABLE `srv_validation` (
  `spr_id` int(11) NOT NULL,
  `if_id` int(11) NOT NULL,
  `reminder` enum('0','1','2') NOT NULL,
  `reminder_text` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `srv_variable_profiles`
--

CREATE TABLE `srv_variable_profiles` (
  `id` int(11) NOT NULL,
  `sid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL,
  `variables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_vrednost`
--

CREATE TABLE `srv_vrednost` (
  `id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL DEFAULT '0',
  `naslov` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `naslov2` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `variable` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `variable_custom` tinyint(1) NOT NULL DEFAULT '0',
  `vrstni_red` int(11) NOT NULL DEFAULT '0',
  `random` tinyint(1) NOT NULL DEFAULT '0',
  `other` int(11) NOT NULL DEFAULT '0',
  `if_id` int(11) NOT NULL DEFAULT '0',
  `size` int(11) NOT NULL DEFAULT '0',
  `naslov_graf` varchar(255) NOT NULL DEFAULT '',
  `hidden` enum('0','1','2') NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `srv_vrednost`
--

INSERT INTO `srv_vrednost` (`id`, `spr_id`, `naslov`, `naslov2`, `variable`, `variable_custom`, `vrstni_red`, `random`, `other`, `if_id`, `size`, `naslov_graf`, `hidden`, `timestamp`) VALUES
(-4, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12'),
(-3, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12'),
(-2, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12'),
(-1, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12'),
(0, 0, 'system', '', '', 0, 0, 0, 0, 0, 0, 'system', '0', '2018-03-05 09:50:12');

-- --------------------------------------------------------

--
-- Table structure for table `srv_vrednost_map`
--

CREATE TABLE `srv_vrednost_map` (
  `id` int(11) NOT NULL,
  `vre_id` int(11) NOT NULL,
  `spr_id` int(11) NOT NULL,
  `overlay_id` int(11) DEFAULT '0',
  `overlay_type` varchar(25) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `lat` float(19,15) NOT NULL,
  `lng` float(19,15) NOT NULL,
  `address` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `vrstni_red` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `srv_zanka_profiles`
--

CREATE TABLE `srv_zanka_profiles` (
  `id` int(11) NOT NULL,
  `sid` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(200) NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT '0',
  `variables` text NOT NULL,
  `mnozenje` int(11) NOT NULL DEFAULT '0'
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
  `type` tinyint(1) NOT NULL DEFAULT '3',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `approved` tinyint(1) NOT NULL DEFAULT '1',
  `gdpr_agree` tinyint(1) NOT NULL DEFAULT '-1',
  `email` varchar(255) NOT NULL,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'Nepodpisani',
  `surname` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `pass` varchar(255) DEFAULT NULL,
  `came_from` tinyint(1) NOT NULL DEFAULT '0',
  `when_reg` date NOT NULL DEFAULT '2003-01-01',
  `show_email` tinyint(1) NOT NULL DEFAULT '1',
  `lost_password` varchar(255) NOT NULL DEFAULT '',
  `lost_password_code` varchar(255) NOT NULL DEFAULT '',
  `lang` int(11) NOT NULL DEFAULT '1',
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:01',
  `LastLP` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `manuallyApproved` enum('Y','N') NOT NULL DEFAULT 'N',
  `eduroam` enum('1','0') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `type`, `status`, `approved`, `gdpr_agree`, `email`, `name`, `surname`, `pass`, `came_from`, `when_reg`, `show_email`, `lost_password`, `lost_password_code`, `lang`, `last_login`, `LastLP`, `manuallyApproved`, `eduroam`) VALUES
(1045, 0, 1, 1, -1, 'admin', 'admin', 'admin', '', 1, '2010-10-28', 1, '', '', 1, '2018-05-29 09:53:39', 0, 'N', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users_to_be`
--

CREATE TABLE `users_to_be` (
  `id` int(11) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '3',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `approved` tinyint(1) NOT NULL DEFAULT '1',
  `gdpr_agree` tinyint(1) NOT NULL DEFAULT '-1',
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'Nepodpisani',
  `surname` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `pass` varchar(255) DEFAULT NULL,
  `came_from` tinyint(1) NOT NULL DEFAULT '0',
  `when_reg` date NOT NULL DEFAULT '2003-01-01',
  `show_email` tinyint(1) NOT NULL DEFAULT '1',
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
  `status` int(3) NOT NULL DEFAULT '0',
  `time` datetime(3) NOT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'sl',
  `package_id` int(11) NOT NULL,
  `trajanje` int(11) NOT NULL DEFAULT '1',
  `payment_method` int(3) NOT NULL DEFAULT '0',
  `discount` decimal(7,2) NOT NULL DEFAULT '0.00',
  `cebelica_id_predracun` int(11) NOT NULL DEFAULT '0',
  `cebelica_id_racun` int(11) NOT NULL DEFAULT '0',
  `phone` varchar(30) NOT NULL DEFAULT '',
  `podjetje_ime` varchar(255) NOT NULL DEFAULT '',
  `podjetje_naslov` varchar(255) NOT NULL DEFAULT '',
  `podjetje_postna` varchar(20) NOT NULL DEFAULT '',
  `podjetje_posta` varchar(100) NOT NULL DEFAULT '',
  `podjetje_drzava` varchar(255) NOT NULL DEFAULT 'Slovenija',
  `podjetje_davcna` varchar(20) NOT NULL DEFAULT '',
  `podjetje_zavezanec` enum('0','1') NOT NULL DEFAULT '0',
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
  `price` decimal(7,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_access_paket`
--

INSERT INTO `user_access_paket` (`id`, `name`, `description`, `price`) VALUES
(1, '1ka', '', '0.00'),
(2, '2ka', '', '11.90'),
(3, '3ka', '', '21.90');

-- --------------------------------------------------------

--
-- Table structure for table `user_access_placilo`
--

CREATE TABLE `user_access_placilo` (
  `id` int(11) NOT NULL,
  `narocilo_id` int(11) NOT NULL DEFAULT '0',
  `note` varchar(255) NOT NULL DEFAULT '',
  `time` datetime(3) NOT NULL,
  `price` decimal(7,2) NOT NULL DEFAULT '0.00',
  `payment_method` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_access_stripe_charge`
--

CREATE TABLE `user_access_stripe_charge` (
  `id` int(11) NOT NULL,
  `narocilo_id` int(11) NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL DEFAULT '',
  `price` decimal(7,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(7,2) NOT NULL DEFAULT '0.00',
  `status` varchar(100) NOT NULL DEFAULT '',
  `balance_transaction` varchar(255) NOT NULL DEFAULT '',
  `time` datetime(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_emails`
--

CREATE TABLE `user_emails` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

-- --------------------------------------------------------

--
-- Table structure for table `user_options`
--

CREATE TABLE `user_options` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `option_name` varchar(255) NOT NULL,
  `option_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_tracker`
--

CREATE TABLE `user_tracker` (
  `uid` varchar(10) DEFAULT NULL,
  `timestamp` mediumtext,
  `what` varchar(254) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_tracking`
--

CREATE TABLE `user_tracking` (
  `datetime` datetime NOT NULL,
  `ip` varchar(16) NOT NULL DEFAULT '',
  `user` int(11) NOT NULL DEFAULT '0',
  `get` text NOT NULL,
  `post` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `time_seconds` float NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `views`
--

CREATE TABLE `views` (
  `pid` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

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
-- Indexes for table `srv_data_checkgrid`
--
ALTER TABLE `srv_data_checkgrid`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`grd_id`,`loop_id`),
  ADD KEY `fk_srv_data_checkgrid_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_checkgrid_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_checkgrid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_checkgrid_active`
--
ALTER TABLE `srv_data_checkgrid_active`
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
-- Indexes for table `srv_data_grid`
--
ALTER TABLE `srv_data_grid`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `vre_id` (`vre_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_grid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_grid_active`
--
ALTER TABLE `srv_data_grid_active`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `vre_id` (`vre_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_grid_active_loop_id` (`loop_id`);

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
-- Indexes for table `srv_data_text`
--
ALTER TABLE `srv_data_text`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_text_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_text_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_textgrid`
--
ALTER TABLE `srv_data_textgrid`
  ADD UNIQUE KEY `spr_id` (`spr_id`,`vre_id`,`usr_id`,`grd_id`,`loop_id`),
  ADD KEY `fk_srv_data_textgrid_usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_textgrid_vre_id` (`vre_id`),
  ADD KEY `fk_srv_data_textgrid_loop_id` (`loop_id`);

--
-- Indexes for table `srv_data_textgrid_active`
--
ALTER TABLE `srv_data_textgrid_active`
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
-- Indexes for table `srv_data_upload`
--
ALTER TABLE `srv_data_upload`
  ADD KEY `srv_data_upload_ank_id` (`ank_id`),
  ADD KEY `srv_data_upload_usr_id` (`usr_id`);

--
-- Indexes for table `srv_data_vrednost`
--
ALTER TABLE `srv_data_vrednost`
  ADD UNIQUE KEY `spr_id_2` (`spr_id`,`vre_id`,`usr_id`,`loop_id`),
  ADD KEY `spr_id` (`spr_id`,`usr_id`),
  ADD KEY `vre_usr` (`vre_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`),
  ADD KEY `fk_srv_data_vrednost_loop_id` (`loop_id`);

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
-- Indexes for table `srv_tracking`
--
ALTER TABLE `srv_tracking`
  ADD KEY `ank_id` (`ank_id`,`datetime`,`user`);

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
-- Indexes for table `srv_user_grupa`
--
ALTER TABLE `srv_user_grupa`
  ADD PRIMARY KEY (`gru_id`,`usr_id`),
  ADD KEY `usr_id` (`usr_id`);

--
-- Indexes for table `srv_user_grupa_active`
--
ALTER TABLE `srv_user_grupa_active`
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
-- AUTO_INCREMENT for table `srv_data_text`
--
ALTER TABLE `srv_data_text`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `srv_data_text_active`
--
ALTER TABLE `srv_data_text_active`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
-- AUTO_INCREMENT for table `user_emails`
--
ALTER TABLE `user_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_login_tracker`
--
ALTER TABLE `user_login_tracker`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `srv_data_checkgrid`
--
ALTER TABLE `srv_data_checkgrid`
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
-- Constraints for table `srv_data_grid`
--
ALTER TABLE `srv_data_grid`
  ADD CONSTRAINT `fk_srv_data_grid_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_grid_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_grid_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_grid_active`
--
ALTER TABLE `srv_data_grid_active`
  ADD CONSTRAINT `fk_srv_data_grid_active_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_grid_active_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_grid_active_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `srv_data_text`
--
ALTER TABLE `srv_data_text`
  ADD CONSTRAINT `fk_srv_data_text_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_text_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_text_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_textgrid`
--
ALTER TABLE `srv_data_textgrid`
  ADD CONSTRAINT `fk_srv_data_textgrid_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_textgrid_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_textgrid_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_textgrid_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_upload`
--
ALTER TABLE `srv_data_upload`
  ADD CONSTRAINT `srv_data_upload_ank_id` FOREIGN KEY (`ank_id`) REFERENCES `srv_anketa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `srv_data_upload_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_vrednost`
--
ALTER TABLE `srv_data_vrednost`
  ADD CONSTRAINT `fk_srv_data_vrednost_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_data_vrednost_active`
--
ALTER TABLE `srv_data_vrednost_active`
  ADD CONSTRAINT `fk_srv_data_vrednost_active_loop_id` FOREIGN KEY (`loop_id`) REFERENCES `srv_loop_data` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_active_spr_id` FOREIGN KEY (`spr_id`) REFERENCES `srv_spremenljivka` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_active_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_data_vrednost_active_vre_id` FOREIGN KEY (`vre_id`) REFERENCES `srv_vrednost` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `srv_user_grupa`
--
ALTER TABLE `srv_user_grupa`
  ADD CONSTRAINT `fk_srv_user_grupa_gru_id` FOREIGN KEY (`gru_id`) REFERENCES `srv_grupa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_user_grupa_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `srv_user_grupa_active`
--
ALTER TABLE `srv_user_grupa_active`
  ADD CONSTRAINT `fk_srv_user_grupa_active_gru_id` FOREIGN KEY (`gru_id`) REFERENCES `srv_grupa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_srv_user_grupa_active_usr_id` FOREIGN KEY (`usr_id`) REFERENCES `srv_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
