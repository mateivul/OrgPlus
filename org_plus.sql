-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 08, 2025 at 08:37 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `org_plus`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `org_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `available_roles` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `org_id`, `name`, `description`, `date`, `created_by`, `available_roles`) VALUES
(94, 7, 'Respirăm împreună: Plantăm pentru viitor', 'Acțiune de ecologizare și plantare de puieți pentru un oraș mai verde. \r\nLocație: Parcul Tineretului', '2025-05-15 12:00:00', 10, 'voluntar plantare,coordonator zonă,responsabil echipamente,fotograf'),
(95, 7, 'Târg de cariere pentru liceeni', 'Standuri cu prezentări de universități, companii și ONG-uri, pentru orientarea profesională a elevilor.\r\nLocație: Colegiul Tehnic „Mihai Băcescu”', '2025-05-17 10:00:00', 11, 'ghid stand,coordonator elevi,responsabil logistică,fotograf'),
(115, 7, 'Seară de film și dezbatere: \"Jurnalul Fericirii\"', 'Proiecția filmului urmată de o discuție despre credință și căutare spirituală. Locație: Amfiteatrul \"Eminescu\", USV.', '2025-03-10 18:00:00', 10, 'coordonator tehnic,moderator discuții,voluntar logistică,fotograf'),
(116, 7, 'Pelerinaj la Mănăstirile din Bucovina', 'Vizitarea mănăstirilor Voroneț, Moldovița și Sucevița. O zi de reculegere și descoperire a patrimoniului cultural.', '2025-04-05 08:00:00', 9, 'ghid spiritual,coordonator transport,responsabil grup,prim ajutor'),
(117, 7, 'Conferința \"Tinerii și Biserica în secolul XXI\"', 'Invitați speciali, preoți și teologi, vor discuta despre provocările și oportunitățile tinerilor creștini. Locație: Aula Magna USV.', '2025-04-22 17:00:00', 10, 'speaker,moderator panel,tehnician sunet,voluntar înregistrare'),
(118, 7, 'Atelier de creație: Icoane pe sticlă', 'Atelier practic pentru a învăța tehnica picturii de icoane pe sticlă. Toate materialele sunt incluse. Locație: Sediul ASCOR.', '2025-05-20 16:00:00', 11, 'instructor,asistent atelier,responsabil materiale,voluntar curățenie'),
(119, 22, 'Ziua de Curățenie Națională - Ediția Suceava', 'Acțiune de ecologizare în mai multe puncte cheie din județul Suceava. Mobilizare generală pentru un mediu mai curat.', '2025-04-26 09:00:00', 8, 'coordonator județean,lider echipă,responsabil saci și mănuși,fotograf eveniment'),
(120, 23, 'Strângere de fonduri \"MagicHOME\"', 'Campanie de strângere de fonduri în centrul comercial Iulius Mall Suceava pentru a susține familiile copiilor bolnavi.', '2025-03-29 12:00:00', 9, 'coordonator voluntari,voluntar stand,casier,animator pentru copii'),
(121, 24, 'Târg de prăjituri \"Dulce pentru o cauză\"', 'Vânzare de prăjituri de casă pentru a strânge fonduri pentru activitățile educaționale ale copiilor din satul SOS. Locație: Parcul Central Suceava.', '2025-06-01 11:00:00', 7, 'bucătar voluntar,vânzător,responsabil logistică,promovare eveniment');

-- --------------------------------------------------------

--
-- Table structure for table `event_roles`
--

CREATE TABLE `event_roles` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_roles`
--

INSERT INTO `event_roles` (`id`, `event_id`, `user_id`, `role`) VALUES
(155, 94, 11, 'coordonator zonă'),
(156, 94, 12, 'responsabil echipamente'),
(157, 94, 13, 'voluntar plantare'),
(158, 94, 8, 'voluntar plantare'),
(159, 94, 9, 'voluntar plantare'),
(160, 94, 15, 'fotograf'),
(167, 95, 12, 'ghid stand'),
(168, 95, 13, 'responsabil logistică'),
(169, 95, 8, 'coordonator elevi'),
(170, 95, 9, 'coordonator elevi'),
(171, 95, 15, 'fotograf'),
(177, 94, 10, 'coordonator zonă'),
(178, 95, 11, 'ghid stand'),
(179, 95, 10, 'responsabil logistică'),
(303, 115, 10, 'moderator discuții'),
(304, 115, 11, 'coordonator tehnic'),
(305, 115, 12, 'voluntar logistică'),
(306, 115, 15, 'fotograf'),
(307, 116, 9, 'ghid spiritual'),
(308, 116, 8, 'coordonator transport'),
(309, 116, 13, 'responsabil grup'),
(310, 116, 14, 'prim ajutor'),
(311, 119, 8, 'coordonator județean'),
(312, 119, 9, 'lider echipă'),
(313, 119, 12, 'lider echipă'),
(314, 119, 13, 'responsabil saci și mănuși'),
(315, 121, 7, 'responsabil logistică'),
(316, 121, 11, 'promovare eveniment'),
(317, 121, 15, 'vânzător'),
(318, 117, 10, 'moderator panel'),
(319, 117, 9, 'voluntar înregistrare'),
(320, 117, 12, 'tehnician sunet'),
(321, 117, 17, 'voluntar înregistrare'),
(322, 118, 11, 'instructor'),
(323, 118, 13, 'asistent atelier'),
(324, 118, 8, 'responsabil materiale'),
(325, 118, 14, 'asistent atelier'),
(326, 120, 9, 'coordonator voluntari'),
(327, 120, 10, 'voluntar stand'),
(328, 120, 14, 'casier'),
(329, 120, 8, 'voluntar stand');

-- --------------------------------------------------------

--
-- Table structure for table `event_tasks`
--

CREATE TABLE `event_tasks` (
  `task_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_description` text NOT NULL,
  `assigned_by_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_tasks`
--

INSERT INTO `event_tasks` (`task_id`, `event_id`, `user_id`, `task_description`, `assigned_by_user_id`, `created_at`) VALUES
(18, 94, 11, 'Distrubuire voluntari plantare pe zonele din parc.', 10, '2025-05-15 17:39:21'),
(19, 94, 12, 'Aduce-ți echipamentele de la sediu la intrare în parc și distribuie cate un echipament pentru fiecare voluntar. Luați și puieții de la sediu.', 10, '2025-05-15 17:39:21'),
(20, 94, 13, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 10, '2025-05-15 17:39:21'),
(21, 94, 8, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 10, '2025-05-15 17:39:21'),
(22, 94, 9, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 10, '2025-05-15 17:39:21'),
(23, 94, 15, 'Luați camera de la sediu si face-ți cateva poze la fiecare zonă din parc și la plantarea copacilor.', 10, '2025-05-15 17:39:21'),
(24, 95, 8, 'Se asigură că elevii participă la toate activitățile conform programului, gestionează grupurile și răspunde întrebărilor legate de desfășurarea evenimentului.', 11, '2025-05-15 17:49:19'),
(25, 95, 9, 'Se asigură că elevii participă la toate activitățile conform programului, gestionează grupurile și răspunde întrebărilor legate de desfășurarea evenimentului.', 11, '2025-05-15 17:49:19'),
(26, 95, 12, 'Întâmpină vizitatorii, oferă informații despre organizația sau instituția prezentă la stand și direcționează elevii către resursele disponibile.', 11, '2025-05-15 17:49:19'),
(27, 95, 13, 'erifică și pregătește spațiile pentru standuri, se ocupă de distribuirea materialelor (mese, scaune, afișe, badge-uri) și rezolvă problemele tehnice apărute în timpul evenimentului.', 11, '2025-05-15 17:49:19'),
(28, 95, 15, 'Surprinde momente importante ale evenimentului (deschiderea oficială, interacțiuni la standuri, prezentări), organizează mini-sesiuni foto cu participanții și pregătește o selecție de poze pentru promovare.', 11, '2025-05-15 17:49:19'),
(29, 94, 8, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 11, '2025-05-15 18:25:20'),
(30, 94, 9, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 11, '2025-05-15 18:25:20'),
(31, 94, 10, 'Distrubuire voluntari plantare pe zonele din parc.', 11, '2025-05-15 18:25:20'),
(32, 94, 11, 'Distrubuire voluntari plantare pe zonele din parc.', 11, '2025-05-15 18:25:20'),
(33, 94, 12, 'Aduce-ți echipamentele de la sediu la intrare în parc și distribuie cate un echipament pentru fiecare voluntar. Luați și puieții de la sediu.', 11, '2025-05-15 18:25:20'),
(34, 94, 13, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 11, '2025-05-15 18:25:20'),
(35, 94, 15, 'Luați camera de la sediu si face-ți cateva poze la fiecare zonă din parc și la plantarea copacilor.', 11, '2025-05-15 18:25:20'),
(36, 95, 8, 'Se asigură că elevii participă la toate activitățile conform programului, gestionează grupurile și răspunde întrebărilor legate de desfășurarea evenimentului.', 11, '2025-05-15 18:26:12'),
(37, 95, 9, 'Se asigură că elevii participă la toate activitățile conform programului, gestionează grupurile și răspunde întrebărilor legate de desfășurarea evenimentului.', 11, '2025-05-15 18:26:12'),
(38, 95, 10, 'Menține ordinea și igiena în spațiul evenimentului.', 11, '2025-05-15 18:26:12'),
(39, 95, 11, 'Explică scopul și ofertele instituției/organizației reprezentate.', 11, '2025-05-15 18:26:12'),
(40, 95, 12, 'Întâmpină vizitatorii, oferă informații despre organizația sau instituția prezentă la stand și direcționează elevii către resursele disponibile.', 11, '2025-05-15 18:26:12'),
(41, 95, 13, 'erifică și pregătește spațiile pentru standuri, se ocupă de distribuirea materialelor (mese, scaune, afișe, badge-uri) și rezolvă problemele tehnice apărute în timpul evenimentului.', 11, '2025-05-15 18:26:12'),
(42, 95, 15, 'Surprinde momente importante ale evenimentului (deschiderea oficială, interacțiuni la standuri, prezentări), organizează mini-sesiuni foto cu participanții și pregătește o selecție de poze pentru promovare.', 11, '2025-05-15 18:26:12'),
(43, 94, 11, 'Distrubuire voluntari plantare pe zonele din park.', 10, '2025-05-19 16:30:46'),
(44, 94, 10, 'Distrubuire voluntari plantare pe zonele din parc.', 10, '2025-05-19 16:30:46'),
(45, 94, 12, 'Aduce-ți echipamentele de la sediu la intrare în parc și distribuie cate un echipament pentru fiecare voluntar. Luați și puieții de la sediu.', 10, '2025-05-19 16:30:46'),
(46, 94, 13, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 10, '2025-05-19 16:30:46'),
(47, 94, 8, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 10, '2025-05-19 16:30:46'),
(48, 94, 9, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 10, '2025-05-19 16:30:46'),
(49, 94, 15, 'Luați camera de la sediu si face-ți cateva poze la fiecare zonă din parc și la plantarea copacilor.', 10, '2025-05-19 16:30:46'),
(50, 94, 11, 'Distrubuire voluntari plantare pe zonele din parc.', 10, '2025-05-19 16:31:21'),
(51, 94, 10, 'Distrubuire voluntari plantare pe zonele din parc.', 10, '2025-05-19 16:31:21'),
(52, 94, 12, 'Aduce-ți echipamentele de la sediu la intrare în parc și distribuie cate un echipament pentru fiecare voluntar. Luați și puieții de la sediu.', 10, '2025-05-19 16:31:21'),
(53, 94, 13, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 10, '2025-05-19 16:31:21'),
(54, 94, 8, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 10, '2025-05-19 16:31:21'),
(55, 94, 9, 'Ajuta-ți la plantat copaci pe zona precizată de Georgescu Ana.', 10, '2025-05-19 16:31:21'),
(64, 115, 11, 'Pregătirea proiectorului și a sistemului de sunet. Asigurarea bunei funcționări pe parcursul evenimentului.', 10, '2025-07-08 05:55:33'),
(65, 115, 10, 'Pregătirea întrebărilor pentru dezbatere și moderarea discuției cu publicul.', 10, '2025-07-08 05:55:33'),
(66, 115, 12, 'Aranjarea scaunelor în sală și întâmpinarea participanților.', 10, '2025-07-08 05:55:33'),
(67, 116, 9, 'Prezentarea istoriei și semnificației fiecărei mănăstiri vizitate.', 9, '2025-07-08 05:55:33'),
(68, 116, 8, 'Contactarea firmei de transport, stabilirea traseului și a orelor de plecare/sosire.', 9, '2025-07-08 05:55:33'),
(69, 116, 13, 'Asigurarea că toți participanții respectă programul și nu se pierde nimeni de grup.', 9, '2025-07-08 05:55:33'),
(70, 119, 8, 'Centralizarea zonelor de acțiune și coordonarea liderilor de echipă din județ.', 8, '2025-07-08 05:55:34'),
(71, 119, 9, 'Organizarea voluntarilor în zona Parcul Șipote și raportarea rezultatelor.', 8, '2025-07-08 05:55:34'),
(72, 119, 12, 'Organizarea voluntarilor pe malul râului Suceava și raportarea rezultatelor.', 8, '2025-07-08 05:55:34'),
(73, 119, 13, 'Distribuirea materialelor necesare (saci, mănuși) către toate echipele.', 8, '2025-07-08 05:55:34'),
(74, 121, 7, 'Obținerea autorizațiilor, amenajarea standului în parc.', 7, '2025-07-08 05:55:34'),
(75, 121, 11, 'Crearea de afișe și promovarea evenimentului pe rețelele sociale.', 7, '2025-07-08 05:55:34'),
(76, 121, 15, 'Vânzarea prăjiturilor și oferirea de informații despre cauză.', 7, '2025-07-08 05:55:34'),
(77, 117, 10, 'Moderează panelul de discuții, asigură un dialog fluid între speakeri și public.', 10, '2025-07-08 06:06:17'),
(78, 117, 9, 'Înregistrează participanții la intrare și oferă materialele informative.', 10, '2025-07-08 06:06:17'),
(79, 117, 12, 'Setează microfoanele pentru speakeri și pentru public. Asigură calitatea sunetului pe durata conferinței.', 10, '2025-07-08 06:06:17'),
(80, 117, 17, 'Ajută la înregistrarea participanților și la distribuirea ecusoanelor.', 10, '2025-07-08 06:06:17'),
(81, 118, 11, 'Susține cursul practic de pictură pe sticlă și oferă îndrumare participanților.', 11, '2025-07-08 06:06:17'),
(82, 118, 13, 'Ajută participanții care au nevoie de sprijin individual în timpul atelierului.', 11, '2025-07-08 06:06:17'),
(83, 118, 8, 'Pregătește și distribuie toate materialele necesare fiecărui participant (sticlă, vopsele, pensule).', 11, '2025-07-08 06:06:17'),
(84, 120, 9, 'Organizează programul voluntarilor la stand și asigură buna desfășurare a campaniei.', 9, '2025-07-08 06:06:17'),
(85, 120, 10, 'Interacționează cu vizitatorii mall-ului, prezintă cauza și încurajează donațiile.', 9, '2025-07-08 06:06:17'),
(86, 120, 14, 'Gestionează urna de donații și oferă chitanțe la cerere.', 9, '2025-07-08 06:06:17'),
(87, 120, 8, 'Distribuie pliante și abțibilduri, menține standul atractiv și ordonat.', 9, '2025-07-08 06:06:17');

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','inactive','','') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `description`, `email`, `phone`, `address`, `website`, `owner_id`, `created_at`, `updated_at`, `status`) VALUES
(7, 'ASCOR Suceava', 'Asociația Studenților Creștin Ortodocși Români (ASCOR) Filiala Suceava. Organizează activități culturale, sociale și religioase pentru studenți.', 'suceava@ascor.ro', '0744123456', 'Str. Universității, Nr. 13, Suceava, Suceava', 'https://ascor-suceava.ro/', 10, '2025-01-15 15:41:50', '2025-05-21 14:18:57', 'active'),
(14, 'Asociația Dăruiește Viața', 'Asociație non-profit dedicată construirii de spitale și centre medicale, precum și îmbunătățirii condițiilor din sistemul medical românesc.', 'contact@daruiesteviata.ro', '0729999099', 'Str. Doctor Ion Bogdan, Nr. 15, Sector 1, București', 'https://www.daruiesteviata.ro', 11, '2025-02-15 18:02:23', '2025-05-21 10:42:21', 'active'),
(15, 'Fundația Mihai Eminescu Trust', 'Organizație dedicată conservării patrimoniului cultural și natural din Transilvania și Bucovina.', 'office@eminescutrust.ro', '0265266851', 'Str. Primăriei, Nr. 12, Sighișoara, Mureș', 'https://www.eminescutrust.ro', 12, '2025-05-14 17:21:30', '2025-05-21 10:42:21', 'active'),
(16, 'Societatea Națională de Cruce Roșie din România', 'Organizație umanitară auxiliară autorităților publice, cu o istorie de peste 140 de ani.', 'office@crucearosie.ro', '0213120205', 'Str. Biserica Amzei, Nr. 29, Sector 1, București', 'https://www.crucearosie.ro', 13, '2025-05-14 17:27:33', '2025-05-21 10:42:21', 'active'),
(17, 'Asociația Little People România', 'Oferă sprijin emoțional și social copiilor și tinerilor diagnosticați cu cancer, precum și familiilor acestora.', 'info@littlepeople.ro', '0722232425', 'Str. General Henri Mathias Berthelot, Nr. 82, Cluj-Napoca, Cluj', 'https://www.littlepeople.ro', 8, '2025-05-15 16:37:18', '2025-05-21 10:42:21', 'active'),
(18, 'Fundația Conservation Carpathia', 'Dedicată creării unui peisaj sălbatic protejat în Munții Carpați.', 'office@conservationcarpathia.org', '0268510800', 'Str. Piatra Craiului, Nr. 10, Zărnești, Brașov', 'https://www.conservationcarpathia.org', 9, '2025-05-15 16:40:30', '2025-05-21 10:42:21', 'active'),
(19, 'Asociația Casa Share', 'Construiește și renovează locuințe pentru familii defavorizate din mediul rural.', 'contact@casashare.ro', '0743300300', 'Sat Călugăreni, Comuna Poienari, Iași', 'https://www.casashare.ro', 7, '2025-05-15 16:44:50', '2025-05-21 10:42:21', 'active'),
(20, 'Salvați Copiii România', 'Promovează drepturile copilului și oferă asistență socială, educațională și medicală copiilor aflați în dificultate.', 'office@salvaticopiii.ro', '0213166176', 'Str. Mendeleev, Nr. 5, Sector 1, București', 'https://www.salvaticopiii.ro', 15, '2025-05-15 16:48:12', '2025-05-21 10:42:21', 'active'),
(22, 'Let\'s Do It, Romania!', 'Cea mai mare mișcare socială din România, care organizează acțiuni de curățenie națională și proiecte de educație ecologică.', 'contact@letsdoitromania.ro', '0314259443', 'Str. Av. Jean Monnet, nr. 50, et. 3, Sector 1, București', 'https://www.letsdoitromania.ro', 8, '2025-01-20 08:00:00', '2025-01-20 08:00:00', 'active'),
(23, 'MagiCAMP', 'Asociație care oferă suport copiilor cu afecțiuni oncologice și familiilor acestora, prin tabere de vară, sprijin material și consiliere psihologică.', 'office@magic.ro', '037493 волшебство', 'Str. Fabrica de Gheață, Nr. 2, Brănești, Ilfov', 'https://www.magic.ro', 9, '2025-01-22 09:30:00', '2025-01-22 09:30:00', 'active'),
(24, 'SOS Satele Copiilor România', 'Fundație care oferă o familie iubitoare copiilor care au rămas fără sprijinul parental și îi susține în dezvoltarea lor până la independență.', 'office@sos-satelecopiilor.ro', '0213172535', 'Str. Jiului, nr. 131 A, Sector 1, București', 'https://www.sos-satelecopiilor.ro', 7, '2025-01-25 07:00:00', '2025-01-25 07:00:00', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL,
  `request_type` enum('organization_join_request','event_invitation','organization_invite_manual','organization_join_request_response') NOT NULL,
  `sender_user_id` int(11) DEFAULT NULL,
  `receiver_user_id` int(11) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`request_id`, `request_type`, `sender_user_id`, `receiver_user_id`, `organization_id`, `event_id`, `status`, `created_at`) VALUES
(172, 'organization_join_request', 12, NULL, 14, NULL, 'accepted', '2025-05-14 17:18:27'),
(173, 'organization_join_request', 12, NULL, 7, NULL, 'accepted', '2025-05-14 17:18:30'),
(176, 'organization_join_request', 8, NULL, 15, NULL, 'accepted', '2025-05-14 17:22:35'),
(177, 'organization_join_request', 8, NULL, 14, NULL, 'accepted', '2025-05-14 17:22:39'),
(178, 'organization_join_request', 8, NULL, 7, NULL, 'accepted', '2025-05-14 17:22:41'),
(179, 'organization_join_request', 13, NULL, 15, NULL, 'accepted', '2025-05-14 17:22:55'),
(180, 'organization_join_request', 9, NULL, 15, NULL, 'accepted', '2025-05-14 17:23:07'),
(181, 'organization_join_request', 9, NULL, 14, NULL, 'accepted', '2025-05-14 17:23:11'),
(182, 'organization_join_request', 9, NULL, 7, NULL, 'accepted', '2025-05-14 17:23:13'),
(183, 'organization_join_request', 8, NULL, 16, NULL, 'accepted', '2025-05-14 17:28:05'),
(184, 'organization_join_request', 9, NULL, 16, NULL, 'accepted', '2025-05-14 17:28:20'),
(185, 'organization_join_request', 7, NULL, 16, NULL, 'accepted', '2025-05-14 17:28:31'),
(186, 'organization_join_request', 7, NULL, 15, NULL, 'accepted', '2025-05-14 17:28:32'),
(187, 'organization_join_request', 7, NULL, 14, NULL, 'rejected', '2025-05-14 17:28:33'),
(188, 'organization_join_request', 7, NULL, 7, NULL, 'rejected', '2025-05-14 17:28:35'),
(189, 'organization_join_request', 10, NULL, 14, NULL, 'accepted', '2025-05-14 17:28:57'),
(190, 'organization_join_request_response', 10, 12, 7, NULL, 'accepted', '2025-05-14 17:31:40'),
(192, 'organization_join_request_response', 10, 8, 7, NULL, 'accepted', '2025-05-14 17:31:50'),
(193, 'organization_join_request', 15, NULL, 16, NULL, 'rejected', '2025-05-14 17:32:13'),
(194, 'organization_join_request', 15, NULL, 15, NULL, 'rejected', '2025-05-14 17:32:15'),
(195, 'organization_join_request', 15, NULL, 14, NULL, 'pending', '2025-05-14 17:32:16'),
(196, 'organization_join_request', 15, NULL, 7, NULL, 'accepted', '2025-05-14 17:32:17'),
(197, 'organization_join_request_response', 10, 9, 7, NULL, 'accepted', '2025-05-14 17:32:39'),
(198, 'organization_join_request_response', 10, 7, 7, NULL, 'rejected', '2025-05-14 17:32:42'),
(199, 'organization_join_request', 11, NULL, 16, NULL, 'accepted', '2025-05-14 17:33:45'),
(200, 'organization_join_request', 11, NULL, 15, NULL, 'accepted', '2025-05-14 17:33:46'),
(201, 'organization_join_request', 11, NULL, 7, NULL, 'accepted', '2025-05-14 17:33:47'),
(202, 'organization_join_request_response', 10, 11, 7, NULL, 'accepted', '2025-03-15 18:34:23'),
(203, 'organization_join_request_response', 11, 10, 14, NULL, 'accepted', '2025-05-14 17:39:27'),
(204, 'organization_join_request_response', 11, 12, 14, NULL, 'accepted', '2025-05-14 17:39:32'),
(206, 'organization_join_request_response', 11, 8, 14, NULL, 'accepted', '2025-05-14 17:39:37'),
(207, 'organization_join_request_response', 11, 9, 14, NULL, 'accepted', '2025-05-14 17:39:39'),
(208, 'organization_join_request_response', 11, 7, 14, NULL, 'rejected', '2025-05-14 17:39:42'),
(209, 'organization_join_request_response', 13, 8, 16, NULL, 'accepted', '2025-05-15 16:33:57'),
(210, 'organization_join_request_response', 13, 9, 16, NULL, 'accepted', '2025-05-15 16:33:59'),
(211, 'organization_join_request_response', 13, 7, 16, NULL, 'accepted', '2025-05-15 16:34:01'),
(212, 'organization_join_request_response', 13, 15, 16, NULL, 'rejected', '2025-05-15 16:34:04'),
(213, 'organization_join_request_response', 13, 11, 16, NULL, 'accepted', '2025-05-15 16:34:06'),
(214, 'organization_join_request_response', 12, 8, 15, NULL, 'accepted', '2025-05-15 16:51:02'),
(215, 'organization_join_request_response', 12, 13, 15, NULL, 'accepted', '2025-05-15 16:51:03'),
(216, 'organization_join_request_response', 12, 9, 15, NULL, 'accepted', '2025-05-15 16:51:04'),
(217, 'organization_join_request_response', 12, 7, 15, NULL, 'accepted', '2025-05-15 16:51:05'),
(218, 'organization_join_request_response', 12, 15, 15, NULL, 'rejected', '2025-05-15 16:51:06'),
(219, 'organization_join_request_response', 12, 11, 15, NULL, 'accepted', '2025-05-15 16:51:07'),
(220, 'organization_invite_manual', 12, 10, 15, NULL, 'accepted', '2025-05-15 16:51:19'),
(226, 'organization_join_request_response', 10, 15, 7, NULL, 'accepted', '2025-05-15 16:52:13'),
(227, 'event_invitation', 10, 8, 7, 94, 'accepted', '2025-05-15 16:58:01'),
(228, 'event_invitation', 10, 9, 7, 94, 'accepted', '2025-05-15 16:58:01'),
(229, 'event_invitation', 10, 11, 7, 94, 'accepted', '2025-05-15 16:58:01'),
(230, 'event_invitation', 10, 12, 7, 94, 'accepted', '2025-05-15 16:58:01'),
(232, 'event_invitation', 10, 15, 7, 94, 'accepted', '2025-05-15 16:58:01'),
(233, 'event_invitation', 11, 8, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(234, 'event_invitation', 11, 9, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(235, 'event_invitation', 11, 10, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(236, 'event_invitation', 11, 12, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(238, 'event_invitation', 11, 15, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(275, 'organization_invite_manual', 10, 14, 7, NULL, 'accepted', '2025-05-24 10:54:10'),
(302, 'organization_invite_manual', 10, 17, 7, NULL, 'accepted', '2025-05-29 12:34:08'),
(303, 'organization_invite_manual', 11, 13, 14, NULL, 'accepted', '2025-06-26 09:56:18'),
(312, 'organization_invite_manual', 10, 13, 7, NULL, 'accepted', '2025-06-26 14:49:58'),
(345, 'organization_join_request', 12, NULL, 22, NULL, 'accepted', '2025-02-01 08:00:00'),
(346, 'organization_join_request', 13, NULL, 22, NULL, 'accepted', '2025-02-01 08:05:00'),
(347, 'organization_join_request', 11, NULL, 22, NULL, 'rejected', '2025-02-02 09:00:00'),
(348, 'organization_join_request', 10, NULL, 23, NULL, 'accepted', '2025-02-05 12:00:00'),
(349, 'organization_join_request', 14, NULL, 23, NULL, 'accepted', '2025-02-05 12:10:00'),
(350, 'organization_join_request', 15, NULL, 24, NULL, 'accepted', '2025-02-10 07:00:00'),
(351, 'organization_join_request', 17, NULL, 24, NULL, 'rejected', '2025-02-11 10:00:00'),
(352, 'organization_invite_manual', 7, 11, 24, NULL, 'accepted', '2025-02-15 13:00:00'),
(353, 'organization_invite_manual', 8, 9, 22, NULL, 'accepted', '2025-02-16 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `org_id` int(11) NOT NULL,
  `role` enum('owner','admin','member') NOT NULL DEFAULT 'member',
  `join_date` datetime DEFAULT current_timestamp(),
  `total_contribution_hours` decimal(8,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `last_activity_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `user_id`, `org_id`, `role`, `join_date`, `total_contribution_hours`, `is_active`, `last_activity_date`) VALUES
(7, 10, 7, 'owner', '2025-01-15 12:40:25', 22.00, 1, '2025-05-28'),
(75, 11, 14, 'owner', '2025-02-15 20:02:23', 0.00, 1, NULL),
(76, 12, 15, 'owner', '2025-05-14 20:21:30', 0.00, 1, NULL),
(77, 13, 16, 'owner', '2025-05-14 20:27:33', 0.00, 1, NULL),
(78, 12, 7, 'member', '2025-05-14 20:31:40', 6.50, 1, '2025-05-16'),
(80, 8, 7, 'member', '2025-05-14 20:31:50', 33.00, 1, '2025-05-24'),
(81, 9, 7, 'admin', '2025-05-14 20:32:39', 7.00, 1, '2025-05-16'),
(82, 11, 7, 'member', '2025-03-15 20:34:23', 10.00, 1, '2025-05-16'),
(83, 10, 14, 'admin', '2025-04-15 20:39:27', 0.00, 1, NULL),
(84, 12, 14, 'member', '2025-05-14 20:39:32', 0.00, 1, NULL),
(86, 8, 14, 'member', '2025-05-14 20:39:37', 0.00, 1, NULL),
(87, 9, 14, 'member', '2025-05-14 20:39:39', 0.00, 1, NULL),
(88, 8, 16, 'member', '2025-05-15 19:33:57', 0.00, 1, NULL),
(89, 9, 16, 'member', '2025-05-15 19:33:59', 0.00, 1, NULL),
(90, 7, 16, 'member', '2025-05-15 19:34:01', 0.00, 1, NULL),
(91, 11, 16, 'member', '2025-05-15 19:34:06', 0.00, 1, NULL),
(92, 8, 17, 'owner', '2025-05-15 19:37:18', 0.00, 1, NULL),
(93, 9, 18, 'owner', '2025-05-15 19:40:30', 0.00, 1, NULL),
(94, 7, 19, 'owner', '2025-05-15 19:44:50', 0.00, 1, NULL),
(95, 15, 20, 'owner', '2025-05-15 19:48:12', 0.00, 1, NULL),
(96, 8, 15, 'member', '2025-05-15 19:51:02', 0.00, 1, NULL),
(97, 13, 15, 'member', '2025-05-15 19:51:03', 0.00, 1, NULL),
(98, 9, 15, 'member', '2025-05-15 19:51:04', 0.00, 1, NULL),
(99, 7, 15, 'member', '2025-05-15 19:51:05', 0.00, 1, NULL),
(100, 11, 15, 'member', '2025-05-15 19:51:07', 0.00, 1, NULL),
(101, 10, 15, 'member', '2025-05-15 19:51:38', 0.00, 1, NULL),
(102, 15, 7, 'admin', '2025-05-15 19:52:13', 2.00, 0, '2025-05-16'),
(105, 14, 7, 'member', '2025-05-24 13:54:23', 0.00, 1, NULL),
(110, 17, 7, 'admin', '2025-05-29 15:34:18', 0.00, 1, NULL),
(111, 13, 14, 'member', '2025-06-26 12:56:28', 0.00, 1, NULL),
(112, 13, 7, 'member', '2025-06-26 18:50:08', 0.00, 1, NULL),
(113, 8, 22, 'owner', '2025-01-20 10:00:00', 0.00, 1, NULL),
(114, 9, 23, 'owner', '2025-01-22 11:30:00', 0.00, 1, NULL),
(115, 7, 24, 'owner', '2025-01-25 09:00:00', 0.00, 1, NULL),
(116, 12, 22, 'member', '2025-02-01 10:00:00', 0.00, 1, NULL),
(117, 13, 22, 'member', '2025-02-01 10:05:00', 0.00, 1, NULL),
(118, 10, 23, 'member', '2025-02-05 14:00:00', 0.00, 1, NULL),
(119, 14, 23, 'member', '2025-02-05 14:10:00', 0.00, 1, NULL),
(120, 15, 24, 'member', '2025-02-10 09:00:00', 0.00, 1, NULL),
(121, 11, 24, 'admin', '2025-02-15 15:00:00', 0.00, 1, NULL),
(122, 9, 22, 'admin', '2025-02-16 16:00:00', 0.00, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `hash_salt` varchar(64) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `prenume` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `hash_salt`, `name`, `prenume`, `created_at`, `updated_at`, `last_login`) VALUES
(7, 'test_user5@gmail.com', '68a9fde08d2e8809f5eb2ee34cd3f4fd0b9f9ecddb30e11dcddd4e3286009a1b', '40d47c90c8ef4db919acbd51f46811315c93c93019e6b49a8b0d39c18c29ec27', 'Popescu', 'Vasile', '2025-03-11 17:21:58', '2025-05-22 16:23:09', '2025-05-22 16:23:09'),
(8, 'test_user3@gmail.com', 'c035dec0d871502ba5ff13b14bb950e298f49922c88439bbf0d65b7fb19966ea', '08e1771fecf83d990a3115f4a9a2e7e4be465d6d261ea29cad51606f979ec737', 'Ionescu', 'Elena', '2025-03-11 17:21:58', '2025-05-24 06:17:26', '2025-05-24 06:17:26'),
(9, 'test_user4@gmail.com', 'a0214d04cb13bb08e6fff5d8c102223fb8f4a0d105c81546e611ac85be2a63da', '9b19737c99ab5a6ef875609e7946c966ec375f1f9ead6e7c4b6f3e6daf09c026', 'Dumitrescu', 'Andrei', '2025-03-21 17:47:24', '2025-05-21 17:46:41', '2025-05-15 17:44:12'),
(10, 'user1@gmail.com', '2140d9229f49c1f05d94f49bf4ff5ddf2481d3f51d7503c76b29776b0fafa2b1', 'c9c1224332089c00521e2f6f95d5ee968860eb5ea050e64605c5b23fc610e3df', 'Popescu', 'Ion', '2025-03-29 15:39:00', '2025-07-08 05:31:14', '2025-07-08 04:31:14'),
(11, 'user2@gmail.com', '2d9c6d5b83fe193840fe4adb86ef3f9fc4faf60c7a4c24e0d8f8e8d7fb5685cc', 'eeb159acd56a674712d597786aa105bc6ac60f0317bfa67a6d80cfe92a8f804d', 'Georgescu', 'Ana', '2025-04-05 16:11:50', '2025-05-19 05:54:33', '2025-05-19 05:54:33'),
(12, 'test_user1@gmail.com', '71b23db400bc5c8ee8f092f14e01a0119c1fad7e70f4627a0997e9424bfd85f5', '739e54f429ac9b5c335fb4a0a6297e2760f5f91832a7f1f3da31050d4269bedb', 'Radu', 'Maria', '2025-04-12 16:14:06', '2025-05-26 14:46:22', '2025-05-24 06:13:37'),
(13, 'test_user2@gmail.com', 'ee94fe961d86de73c979b5b1d5f7e7ed79a02a61cf64c139655cff91687ee298', '16dc2199f79f4a5a6a89ed829d21b3fca1cd6e060a9818a255df92d7af59edcb', 'Stoica', 'Cristian', '2025-04-29 08:37:31', '2025-07-02 16:41:50', '2025-07-02 15:41:50'),
(14, 'ionescu@gmail.com', 'c8ac2b0873afa4172f79fd64fbf3caa1e39ef361a1ef561e1eb69f67b212ea89', 'd3a2afa211ca6f5d3b36826eb697dce67b3d25a11bcfe6bac7c8df2a0d885eb8', 'Munteanu', 'Ioana', '2025-04-29 08:37:47', '2025-05-24 10:54:17', '2025-05-24 10:54:17'),
(15, 'test_user6@gmail.com', '82ec479e5868a52317f18a9907d7ed120f57393a1788a2721fe5aec9742ced7f', '1359ae4f29fa5843e1a71452e1dcb0a9d5d9c1172c0a7de7e318257a05743ec8', 'Constantinescu', 'Mihai', '2025-04-29 08:37:58', '2025-05-22 14:14:10', '2025-05-22 14:14:10'),
(17, 'vlad@gmail.com', '3bcd5ec38fab092e5b3c57fa4037274d8d0d21533b8331cd1c8c8ee7f8e4bcaf', '139b04296a9c9f50f26f83bbf25968007fce50a771383abe18b6036a54207c0c', 'Iustin', 'Vlad', '2025-05-24 10:50:04', '2025-07-08 05:59:55', '2025-05-24 10:51:43');

-- --------------------------------------------------------

--
-- Table structure for table `worked_hours`
--

CREATE TABLE `worked_hours` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `org_id` int(11) NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `work_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `worked_hours`
--

INSERT INTO `worked_hours` (`id`, `user_id`, `org_id`, `hours`, `work_date`, `description`, `recorded_at`, `recorded_by`) VALUES
(5, 10, 7, 3.00, '2025-05-14', 'Organizarea evenimentului de pe 15.05 2025.', '2025-05-16 14:23:55', 10),
(6, 12, 7, 1.50, '2025-05-15', 'A lucrat în cadrul evenimentului', '2025-05-16 14:27:03', 10),
(7, 15, 7, 1.00, '2025-05-15', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:27:36', 10),
(8, 13, 7, 4.00, '2025-05-15', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:27:56', 10),
(9, 8, 7, 4.00, '2025-05-15', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:28:11', 10),
(10, 9, 7, 4.00, '2025-05-15', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:28:37', 10),
(11, 11, 7, 5.00, '2025-05-15', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:29:47', 10),
(12, 10, 7, 5.00, '2025-05-15', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:29:59', 10),
(13, 10, 7, 0.50, '2025-05-16', 'Completarea evidenței orelor lucrate pentru fiecare voluntar implicat în eveniment.', '2025-05-16 14:33:47', 10),
(14, 11, 7, 2.00, '2025-05-16', 'Organizarea evenimentului de pe 17.05 2025.', '2025-05-16 14:35:12', 10),
(15, 15, 7, 1.00, '2025-05-17', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:35:36', 10),
(16, 12, 7, 5.00, '2025-05-17', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:36:14', 10),
(17, 8, 7, 5.00, '2025-05-17', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:37:09', 10),
(18, 13, 7, 3.00, '2025-05-17', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:37:28', 10),
(19, 10, 7, 3.50, '2025-05-17', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:37:39', 10),
(20, 9, 7, 3.00, '2025-05-17', 'A lucrat în cadrul evenimentului.', '2025-05-16 14:38:13', 10),
(22, 11, 7, 1.00, '2025-05-18', 'Completarea evidenței orelor lucrate pentru fiecare voluntar implicat în eveniment.', '2025-05-16 14:39:03', 10),
(48, 10, 7, 3.00, '2025-03-10', 'Moderare și organizare seară de film.', '2025-07-08 05:55:33', 10),
(49, 11, 7, 3.50, '2025-03-10', 'Asistență tehnică la seara de film.', '2025-07-08 05:55:33', 10),
(50, 12, 7, 2.50, '2025-03-10', 'Logistică și primire invitați la seara de film.', '2025-07-08 05:55:33', 10),
(51, 15, 7, 2.00, '2025-03-10', 'Fotograf la seara de film.', '2025-07-08 05:55:33', 10),
(52, 9, 7, 8.00, '2025-04-05', 'Coordonare și ghidaj pelerinaj.', '2025-07-08 05:55:33', 10),
(53, 8, 7, 8.00, '2025-04-05', 'Organizare transport și logistică pelerinaj.', '2025-07-08 05:55:33', 10),
(54, 13, 7, 7.00, '2025-04-05', 'Responsabil de grup în pelerinaj.', '2025-07-08 05:55:33', 10),
(55, 14, 7, 7.00, '2025-04-05', 'Asistență prim ajutor pe durata pelerinajului.', '2025-07-08 05:55:33', 10),
(56, 8, 22, 8.00, '2025-04-26', 'Coordonare județeană Ziua de Curățenie.', '2025-07-08 05:55:34', 8),
(57, 9, 22, 6.00, '2025-04-26', 'Lider de echipă la Ziua de Curățenie.', '2025-07-08 05:55:34', 8),
(58, 12, 22, 6.00, '2025-04-26', 'Lider de echipă la Ziua de Curățenie.', '2025-07-08 05:55:34', 8),
(59, 13, 22, 7.00, '2025-04-26', 'Logistică și distribuire materiale la Ziua de Curățenie.', '2025-07-08 05:55:34', 8),
(60, 7, 24, 6.00, '2025-06-01', 'Coordonare și logistică târg de prăjituri.', '2025-07-08 05:55:34', 7),
(61, 11, 24, 5.00, '2025-06-01', 'Promovare și vânzare la târgul de prăjituri.', '2025-07-08 05:55:34', 7),
(62, 15, 24, 5.00, '2025-06-01', 'Vânzător voluntar la târgul de prăjituri.', '2025-07-08 05:55:34', 7),
(63, 10, 7, 4.00, '2025-04-22', 'Moderare și organizare conferință.', '2025-07-08 06:06:17', 10),
(64, 9, 7, 3.00, '2025-04-22', 'Voluntariat la înregistrare participanți.', '2025-07-08 06:06:17', 10),
(65, 12, 7, 3.50, '2025-04-22', 'Asistență tehnică audio la conferință.', '2025-07-08 06:06:17', 10),
(66, 17, 7, 3.00, '2025-04-22', 'Voluntariat și suport la conferință.', '2025-07-08 06:06:17', 10),
(67, 11, 7, 3.00, '2025-05-20', 'Instructor la atelierul de pictat icoane.', '2025-07-08 06:06:17', 10),
(68, 13, 7, 2.50, '2025-05-20', 'Asistență în cadrul atelierului de pictură.', '2025-07-08 06:06:17', 10),
(69, 8, 7, 2.50, '2025-05-20', 'Logistică și materiale pentru atelier.', '2025-07-08 06:06:17', 10),
(70, 14, 7, 2.50, '2025-05-20', 'Asistență în cadrul atelierului de pictură.', '2025-07-08 06:06:17', 10),
(71, 9, 23, 5.00, '2025-03-29', 'Coordonare voluntari la strângerea de fonduri.', '2025-07-08 06:06:17', 9),
(72, 10, 23, 4.00, '2025-03-29', 'Voluntar la standul MagicHOME.', '2025-07-08 06:06:17', 9),
(73, 14, 23, 4.00, '2025-03-29', 'Casier la campania de strângere de fonduri.', '2025-07-08 06:06:17', 9),
(74, 8, 23, 4.00, '2025-03-29', 'Voluntar la standul MagicHOME.', '2025-07-08 06:06:17', 9);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `event_roles`
--
ALTER TABLE `event_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_tasks`
--
ALTER TABLE `event_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_by_user_id` (`assigned_by_user_id`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `sender_user_id` (`sender_user_id`),
  ADD KEY `receiver_user_id` (`receiver_user_id`),
  ADD KEY `organization_id` (`organization_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`org_id`),
  ADD KEY `org_id` (`org_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `worked_hours`
--
ALTER TABLE `worked_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `user_id` (`user_id`,`org_id`,`work_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `event_roles`
--
ALTER TABLE `event_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=330;

--
-- AUTO_INCREMENT for table `event_tasks`
--
ALTER TABLE `event_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=354;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `worked_hours`
--
ALTER TABLE `worked_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_roles`
--
ALTER TABLE `event_roles`
  ADD CONSTRAINT `event_roles_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_roles_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_tasks`
--
ALTER TABLE `event_tasks`
  ADD CONSTRAINT `event_tasks_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  ADD CONSTRAINT `event_tasks_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `event_tasks_ibfk_3` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`receiver_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `requests_ibfk_3` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  ADD CONSTRAINT `requests_ibfk_4` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `roles_ibfk_2` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`id`);

--
-- Constraints for table `worked_hours`
--
ALTER TABLE `worked_hours`
  ADD CONSTRAINT `worked_hours_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `worked_hours_ibfk_2` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`id`),
  ADD CONSTRAINT `worked_hours_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
