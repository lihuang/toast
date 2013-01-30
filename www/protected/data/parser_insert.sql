-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2011 年 11 月 24 日 10:43
-- 服务器版本: 5.5.8
-- PHP 版本: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `toast_new`
--

--
-- 转存表中的数据 `parser`
--

INSERT INTO `parser` (`name`, `parser_class`, `desc_info`) VALUES
('GTest-txt', 'GTestTxtParser', NULL),
('GTest-xml', 'GTestXMLParser', NULL),
('Perl', 'PerlParser', NULL),
('PHPUnit-xml', 'PHPUnitXMLParser', NULL),
('CPPUnit', 'CPPUnitParser', NULL),
('JUnit-ant', 'JUnitAntParser', NULL),
('JUnit-orig', 'JUnitOrigParser', NULL),
('JUnit-mvn', 'JUnitMvnParser', NULL),
('Mocha', 'MochaParser', NULL),
('Grails', 'GrailsParser', NULL),
('PyUnit', 'PyUnitParser', NULL),
('Perl Test::Class', 'PerlTestParser', NULL),
('NUnit', 'NUnitParser', NULL),
('ApsaraUnit', 'ApsaraUnitParser', NULL),
('ShellUnit', 'ShellUnitParser', NULL),
('LightFace', 'LightFaceParser', NULL),
('Toast', 'ToastParser', NULL),
('NoseTests', 'NoseTestsParser', NULL),
('Lua', 'LuaParser', NULL);
