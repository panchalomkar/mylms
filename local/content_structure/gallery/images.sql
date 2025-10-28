-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2018 at 05:17 PM
-- Server version: 10.1.25-MariaDB
-- PHP Version: 7.1.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pagination`
--

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE `images` (
  `id` int(2) NOT NULL,
  `file` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `images`
--

INSERT INTO `images` (`id`, `file`) VALUES
(1, 'img1.jpg'),
(2, 'img2.jpg'),
(3, 'img3.jpg'),
(4, 'img4.jpg'),
(5, 'img5.jpg'),
(6, 'img6.jpg'),
(7, 'img7.jpg'),
(8, 'img8.jpg'),
(9, 'img9.jpg'),
(10, 'img10.jpg'),
(11, 'img11.jpg'),
(12, 'img12.jpg'),
(13, 'img13.jpg'),
(14, 'img14.jpg'),
(15, 'img15.jpg'),
(16, 'img16.jpg'),
(17, 'img17.jpg'),
(18, 'img18.jpg'),
(19, 'img19.jpg'),
(20, 'img20.jpg'),
(21, 'img21.jpg'),
(22, 'img22.jpg'),
(23, 'img23.jpg'),
(24, 'img24.jpg'),
(25, 'img25.jpg'),
(26, 'img26.jpg'),
(27, 'img27.jpg'),
(28, 'img28.jpg'),
(29, 'img29.jpg'),
(30, 'img30.jpg'),
(31, 'img31.jpg'),
(32, 'img32.jpg'),
(33, 'img33.jpg'),
(34, 'img34.jpg'),
(35, 'img35.jpg'),
(36, 'img36.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
  MODIFY `id` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
