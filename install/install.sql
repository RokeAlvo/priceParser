-- phpMyAdmin SQL Dump
-- version 3.3.7deb7
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Май 02 2017 г., 22:46
-- Версия сервера: 5.1.73
-- Версия PHP: 5.3.3-7+squeeze19

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `expocar_1cparser`
--
CREATE DATABASE `expocar_1cparser` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `expocar_1cparser`;

-- --------------------------------------------------------

--
-- Структура таблицы `article`
--

CREATE TABLE IF NOT EXISTS `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article` varchar(30) NOT NULL COMMENT 'артикул',
  `price1` float(9,2) NOT NULL COMMENT 'цена1',
  `price2` float(9,2) NOT NULL COMMENT 'цена2',
  `name` varchar(250) NOT NULL COMMENT 'наименование',
  `producer` varchar(100) NOT NULL COMMENT 'производитель',
  `md5` varchar(50) NOT NULL COMMENT 'md5 строка с параметрами запроса',
  `queryArticle` varchar(30) NOT NULL COMMENT 'Артикул для query:',
  `evalDate` datetime NOT NULL COMMENT 'дата проценки',
  `orderId` int(11) NOT NULL COMMENT 'id заказа',
  `keywords` varchar(100) NOT NULL COMMENT 'ключевые слова',
  `queryCount` int(11) NOT NULL COMMENT 'количество для query',
  `availability` int(11) NOT NULL COMMENT 'наличие',
  `portalId` int(11) NOT NULL,
  `needEval` int(11) NOT NULL COMMENT 'требует проценки',
  `supplyDate` int(11) NOT NULL COMMENT 'срок поставки',
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orderId` (`orderId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2145332 ;

-- --------------------------------------------------------

--
-- Структура таблицы `order`
--

CREATE TABLE IF NOT EXISTS `order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `timeFinished` int(11) NOT NULL,
  `timeSent` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=551 ;

-- --------------------------------------------------------

--
-- Структура таблицы `portal`
--

CREATE TABLE IF NOT EXISTS `portal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;
--
-- База данных: `expocar_parser`
--
CREATE DATABASE `expocar_parser` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `expocar_parser`;

-- --------------------------------------------------------

--
-- Структура таблицы `article`
--

CREATE TABLE IF NOT EXISTS `article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article` varchar(30) NOT NULL COMMENT 'артикул',
  `price1` float(9,2) NOT NULL COMMENT 'цена1',
  `price2` float(9,2) NOT NULL COMMENT 'цена2',
  `name` varchar(250) NOT NULL COMMENT 'наименование',
  `producer` varchar(100) NOT NULL COMMENT 'производитель',
  `md5` varchar(50) NOT NULL COMMENT 'md5 строка с параметрами запроса',
  `queryArticle` varchar(30) NOT NULL COMMENT 'Артикул для query:',
  `evalDate` datetime NOT NULL COMMENT 'дата проценки',
  `orderId` int(11) NOT NULL COMMENT 'id заказа',
  `keywords` varchar(100) NOT NULL COMMENT 'ключевые слова',
  `queryCount` int(11) NOT NULL COMMENT 'количество для query',
  `availability` int(11) NOT NULL COMMENT 'наличие',
  `portalId` int(11) NOT NULL,
  `needEval` int(11) NOT NULL COMMENT 'требует проценки',
  `supplyDate` int(11) NOT NULL COMMENT 'срок поставки',
  `updateDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orderId` (`orderId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1289880 ;

-- --------------------------------------------------------

--
-- Структура таблицы `order`
--

CREATE TABLE IF NOT EXISTS `order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=668 ;

-- --------------------------------------------------------

--
-- Структура таблицы `portal`
--

CREATE TABLE IF NOT EXISTS `portal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;
