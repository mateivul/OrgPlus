BEGIN TRANSACTION;

--
-- Table structure for table `events`
--

CREATE TABLE events (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  org_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  description TEXT DEFAULT NULL,
  date DATETIME NOT NULL,
  created_by INTEGER NOT NULL,
  available_roles TEXT DEFAULT NULL,
  FOREIGN KEY (org_id) REFERENCES organizations (id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
);

--
-- Dumping data for table `events`
--

INSERT INTO "events" ("id", "org_id", "name", "description", "date", "created_by", "available_roles") VALUES
(94, 7, 'Respirăm împreună: Plantăm pentru viitor', 'Acțiune de ecologizare și plantare de puieți pentru un oraș mai verde. 
Locație: Parcul Tineretului', '2025-05-15 12:00:00', 10, 'voluntar plantare,coordonator zonă,responsabil echipamente,fotograf'),
(95, 7, 'Târg de cariere pentru liceeni', 'Standuri cu prezentări de universități, companii și ONG-uri, pentru orientarea profesională a elevilor.
Locație: Colegiul Tehnic „Mihai Băcescu”', '2025-05-17 10:00:00', 11, 'ghid stand,coordonator elevi,responsabil logistică,fotograf');

--
-- Table structure for table `event_roles`
--

CREATE TABLE event_roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  role TEXT NOT NULL,
  FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  UNIQUE (event_id, user_id)
);

--
-- Dumping data for table `event_roles`
--

INSERT INTO "event_roles" ("id", "event_id", "user_id", "role") VALUES
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
(179, 95, 10, 'responsabil logistică');

--
-- Table structure for table `event_tasks`
--

CREATE TABLE event_tasks (
  task_id INTEGER PRIMARY KEY AUTOINCREMENT,
  event_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  task_description TEXT NOT NULL,
  assigned_by_user_id INTEGER NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES events (id),
  FOREIGN KEY (user_id) REFERENCES users (id),
  FOREIGN KEY (assigned_by_user_id) REFERENCES users (id)
);

--
-- Dumping data for table `event_tasks`
--

INSERT INTO "event_tasks" ("task_id", "event_id", "user_id", "task_description", "assigned_by_user_id", "created_at") VALUES
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
(56, 94, 15, 'Luați camera de la sediu si face-ți cateva poze la fiecare zonă din parc și la plantarea copacilor.', 10, '2025-05-19 16:31:21');

--
-- Table structure for table `organizations`
--

CREATE TABLE organizations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT NOT NULL,
  email TEXT NOT NULL,
  phone TEXT NOT NULL,
  address TEXT NOT NULL,
  website TEXT NOT NULL,
  owner_id INTEGER NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive'))
);

--
-- Dumping data for table `organizations`
--

INSERT INTO "organizations" ("id", "name", "description", "email", "phone", "address", "website", "owner_id", "created_at", "updated_at", "status") VALUES
(7, 'ASCOR Suceava', 'Asociația Studenților Creștin Ortodocși Români (ASCOR) Filiala Suceava. Organizează activități culturale, sociale și religioase pentru studenți.', 'suceava@ascor.ro', '0744123456', 'Str. Universității, Nr. 13, Suceava, Suceava', 'https://ascor-suceava.ro/', 10, '2025-01-15 15:41:50', '2025-05-21 14:18:57', 'active'),
(14, 'Asociația Dăruiește Viața', 'Asociație non-profit dedicată construirii de spitale și centre medicale, precum și îmbunătățirii condițiilor din sistemul medical românesc.', 'contact@daruiesteviata.ro', '0729999099', 'Str. Doctor Ion Bogdan, Nr. 15, Sector 1, București', 'https://www.daruiesteviata.ro', 11, '2025-02-15 18:02:23', '2025-05-21 10:42:21', 'active'),
(15, 'Fundația Mihai Eminescu Trust', 'Organizație dedicată conservării patrimoniului cultural și natural din Transilvania și Bucovina.', 'office@eminescutrust.ro', '0265266851', 'Str. Primăriei, Nr. 12, Sighișoara, Mureș', 'https://www.eminescutrust.ro', 12, '2025-05-14 17:21:30', '2025-05-21 10:42:21', 'active'),
(16, 'Societatea Națională de Cruce Roșie din România', 'Organizație umanitară auxiliară autorităților publice, cu o istorie de peste 140 de ani.', 'office@crucearosie.ro', '0213120205', 'Str. Biserica Amzei, Nr. 29, Sector 1, București', 'https://www.crucearosie.ro', 13, '2025-05-14 17:27:33', '2025-05-21 10:42:21', 'active'),
(17, 'Asociația Little People România', 'Oferă sprijin emoțional și social copiilor și tinerilor diagnosticați cu cancer, precum și familiilor acestora.', 'info@littlepeople.ro', '0722232425', 'Str. General Henri Mathias Berthelot, Nr. 82, Cluj-Napoca, Cluj', 'https://www.littlepeople.ro', 8, '2025-05-15 16:37:18', '2025-05-21 10:42:21', 'active'),
(18, 'Fundația Conservation Carpathia', 'Dedicată creării unui peisaj sălbatic protejat în Munții Carpați.', 'office@conservationcarpathia.org', '0268510800', 'Str. Piatra Craiului, Nr. 10, Zărnești, Brașov', 'https://www.conservationcarpathia.org', 9, '2025-05-15 16:40:30', '2025-05-21 10:42:21', 'active'),
(19, 'Asociația Casa Share', 'Construiește și renovează locuințe pentru familii defavorizate din mediul rural.', 'contact@casashare.ro', '0743300300', 'Sat Călugăreni, Comuna Poienari, Iași', 'https://www.casashare.ro', 7, '2025-05-15 16:44:50', '2025-05-21 10:42:21', 'active'),
(20, 'Salvați Copiii România', 'Promovează drepturile copilului și oferă asistență socială, educațională și medicală copiilor aflați în dificultate.', 'office@salvaticopiii.ro', '0213166176', 'Str. Mendeleev, Nr. 5, Sector 1, București', 'https://www.salvaticopiii.ro', 15, '2025-05-15 16:48:12', '2025-05-21 10:42:21', 'active');

--
-- Table structure for table `requests`
--

CREATE TABLE requests (
  request_id INTEGER PRIMARY KEY AUTOINCREMENT,
  request_type TEXT NOT NULL CHECK (request_type IN ('organization_join_request', 'event_invitation', 'organization_invite_manual', 'organization_join_request_response')),
  sender_user_id INTEGER DEFAULT NULL,
  receiver_user_id INTEGER DEFAULT NULL,
  organization_id INTEGER DEFAULT NULL,
  event_id INTEGER DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected')),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_user_id) REFERENCES users (id),
  FOREIGN KEY (receiver_user_id) REFERENCES users (id),
  FOREIGN KEY (organization_id) REFERENCES organizations (id),
  FOREIGN KEY (event_id) REFERENCES events (id)
);

--
-- Dumping data for table `requests`
--

INSERT INTO "requests" ("request_id", "request_type", "sender_user_id", "receiver_user_id", "organization_id", "event_id", "status", "created_at") VALUES
(172, 'organization_join_request', 12, NULL, 14, NULL, 'accepted', '2025-05-14 17:18:27'),
(173, 'organization_join_request', 12, NULL, 7, NULL, 'accepted', '2025-05-14 17:18:30'),
(174, 'organization_join_request', 13, NULL, 14, NULL, 'accepted', '2025-05-14 17:18:50'),
(175, 'organization_join_request', 13, NULL, 7, NULL, 'accepted', '2025-05-14 17:18:52'),
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
(191, 'organization_join_request_response', 10, 13, 7, NULL, 'accepted', '2025-05-14 17:31:46'),
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
(205, 'organization_join_request_response', 11, 13, 14, NULL, 'accepted', '2025-05-14 17:39:35'),
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
(231, 'event_invitation', 10, 13, 7, 94, 'accepted', '2025-05-15 16:58:01'),
(232, 'event_invitation', 10, 15, 7, 94, 'accepted', '2025-05-15 16:58:01'),
(233, 'event_invitation', 11, 8, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(234, 'event_invitation', 11, 9, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(235, 'event_invitation', 11, 10, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(236, 'event_invitation', 11, 12, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(237, 'event_invitation', 11, 13, 7, 95, 'accepted', '2025-05-15 17:42:28'),
(238, 'event_invitation', 11, 15, 7, 95, 'accepted', '2025-05-15 17:42:28');

--
-- Table structure for table `roles`
--

CREATE TABLE roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  org_id INTEGER NOT NULL,
  role TEXT NOT NULL DEFAULT 'member' CHECK (role IN ('owner', 'admin', 'member')),
  join_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  total_contribution_hours REAL DEFAULT 0.00,
  is_active INTEGER DEFAULT 1,
  last_activity_date DATE DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users (id),
  FOREIGN KEY (org_id) REFERENCES organizations (id),
  UNIQUE (user_id, org_id)
);

--
-- Dumping data for table `roles`
--

INSERT INTO "roles" ("id", "user_id", "org_id", "role", "join_date", "total_contribution_hours", "is_active", "last_activity_date") VALUES
(7, 10, 7, 'owner', '2025-01-15 12:40:25', 17.00, 1, '2025-05-23'),
(75, 11, 14, 'owner', '2025-02-15 20:02:23', 0.00, 1, NULL),
(76, 12, 15, 'owner', '2025-05-14 20:21:30', 0.00, 1, NULL),
(77, 13, 16, 'owner', '2025-05-14 20:27:33', 0.00, 1, NULL),
(78, 12, 7, 'member', '2025-05-14 20:31:40', 6.50, 1, '2025-05-16'),
(79, 13, 7, 'member', '2025-05-14 20:31:46', 7.00, 1, '2025-05-16'),
(80, 8, 7, 'member', '2025-05-14 20:31:50', 9.00, 1, '2025-05-16'),
(81, 9, 7, 'member', '2025-05-14 20:32:39', 7.00, 1, '2025-05-16'),
(82, 11, 7, 'admin', '2025-03-15 20:34:23', 10.00, 1, '2025-05-16'),
(83, 10, 14, 'admin', '2025-04-15 20:39:27', 0.00, 1, NULL),
(84, 12, 14, 'member', '2025-05-14 20:39:32', 0.00, 1, NULL),
(85, 13, 14, 'member', '2025-05-14 20:39:35', 0.00, 1, NULL),
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
(102, 15, 7, 'member', '2025-05-15 19:52:13', 2.00, 1, '2025-05-16');

--
-- Table structure for table `users`
--

CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL,
  password TEXT NOT NULL,
  hash_salt TEXT NOT NULL,
  name TEXT NOT NULL,
  prenume TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL DEFAULT NULL
);

--
-- Dumping data for table `users`
--

INSERT INTO "users" ("id", "email", "password", "hash_salt", "name", "prenume", "created_at", "updated_at", "last_login") VALUES
(7, 'test_user5@gmail.com', '68a9fde08d2e8809f5eb2ee34cd3f4fd0b9f9ecddb30e11dcddd4e3286009a1b', '40d47c90c8ef4db919acbd51f46811315c93c93019e6b49a8b0d39c18c29ec27', 'Popescu', 'Vasile', '2025-03-11 17:21:58', '2025-05-22 16:23:09', '2025-05-22 16:23:09'),
(8, 'test_user3@gmail.com', 'c035dec0d871502ba5ff13b14bb950e298f49922c88439bbf0d65b7fb19966ea', '08e1771fecf83d990a3115f4a9a2e7e4be465d6d261ea29cad51606f979ec737', 'Ionescu', 'Elena', '2025-03-11 17:21:58', '2025-05-22 16:05:45', '2025-05-22 16:05:45'),
(9, 'test_user4@gmail.com', 'a0214d04cb13bb08e6fff5d8c102223fb8f4a0d105c81546e611ac85be2a63da', '9b19737c99ab5a6ef875609e7946c966ec375f1f9ead6e7c4b6f3e6daf09c026', 'Dumitrescu', 'Andrei', '2025-03-21 17:47:24', '2025-05-21 17:46:41', '2025-05-15 17:44:12'),
(10, 'user1@gmail.com', '2140d9229f49c1f05d94f49bf4ff5ddf2481d3f51d7503c76b29776b0fafa2b1', 'c9c1224332089c00521e2f6f95d5ee968860eb5ea050e64605c5b23fc610e3df', 'Popescu', 'Ion', '2025-03-29 15:39:00', '2025-05-23 15:07:53', '2025-05-23 15:07:53'),
(11, 'user2@gmail.com', '2d9c6d5b83fe193840fe4adb86ef3f9fc4faf60c7a4c24e0d8f8e8d7fb5685cc', 'eeb159acd56a674712d597786aa105bc6ac60f0317bfa67a6d80cfe92a8f804d', 'Georgescu', 'Ana', '2025-04-05 16:11:50', '2025-05-19 05:54:33', '2025-05-19 05:54:33'),
(12, 'test_user1@gmail.com', '71b23db400bc5c8ee8f092f14e01a0119c1fad7e70f4627a0997e9424bfd85f5', '739e54f429ac9b5c335fb4a0a6297e2760f5f91832a7f1f3da31050d4269bedb', 'Radu', 'Maria', '2025-04-12 16:14:06', '2025-05-22 16:03:42', '2025-05-22 16:03:42'),
(13, 'test_user2@gmail.com', 'ee94fe961d86de73c979b5b1d5f7e7ed79a02a61cf64c139655cff91687ee298', '16dc2199f79f4a5a6a89ed829d21b3fca1cd6e060a9818a255df92d7af59edcb', 'Stoica', 'Cristian', '2025-04-29 08:37:31', '2025-05-23 15:04:34', '2025-05-23 15:04:34'),
(14, 'test_user7@gmail.com', 'c8ac2b0873afa4172f79fd64fbf3caa1e39ef361a1ef561e1eb69f67b212ea89', 'd3a2afa211ca6f5d3b36826eb697dce67b3d25a11bcfe6bac7c8df2a0d885eb8', 'Munteanu', 'Ioana', '2025-04-29 08:37:47', '2025-05-23 15:07:42', '2025-05-23 15:07:42'),
(15, 'test_user6@gmail.com', '82ec479e5868a52317f18a9907d7ed120f57393a1788a2721fe5aec9742ced7f', '1359ae4f29fa5843e1a71452e1dcb0a9d5d9c1172c0a7de7e318257a05743ec8', 'Constantinescu', 'Mihai', '2025-04-29 08:37:58', '2025-05-22 14:14:10', '2025-05-22 14:14:10');

--
-- Table structure for table `worked_hours`
--

CREATE TABLE worked_hours (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  org_id INTEGER NOT NULL,
  hours REAL NOT NULL,
  work_date DATE NOT NULL,
  description TEXT DEFAULT NULL,
  recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  recorded_by INTEGER NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users (id),
  FOREIGN KEY (org_id) REFERENCES organizations (id),
  FOREIGN KEY (recorded_by) REFERENCES users (id)
);

--
-- Dumping data for table `worked_hours`
--

INSERT INTO "worked_hours" ("id", "user_id", "org_id", "hours", "work_date", "description", "recorded_at", "recorded_by") VALUES
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
(23, 10, 7, 5.00, '2025-05-30', 'descire', '2025-05-23 15:12:09', 10);

--
-- Indexes for tables
--

CREATE INDEX idx_events_org_id ON events (org_id);
CREATE INDEX idx_events_created_by ON events (created_by);
CREATE INDEX idx_event_roles_user_id ON event_roles (user_id);
CREATE INDEX idx_event_tasks_event_id ON event_tasks (event_id);
CREATE INDEX idx_event_tasks_user_id ON event_tasks (user_id);
CREATE INDEX idx_event_tasks_assigned_by_user_id ON event_tasks (assigned_by_user_id);
CREATE INDEX idx_requests_sender_user_id ON requests (sender_user_id);
CREATE INDEX idx_requests_receiver_user_id ON requests (receiver_user_id);
CREATE INDEX idx_requests_organization_id ON requests (organization_id);
CREATE INDEX idx_requests_event_id ON requests (event_id);
CREATE INDEX idx_roles_org_id ON roles (org_id);
CREATE INDEX idx_worked_hours_org_id ON worked_hours (org_id);
CREATE INDEX idx_worked_hours_recorded_by ON worked_hours (recorded_by);
CREATE INDEX idx_worked_hours_user_org_date ON worked_hours (user_id, org_id, work_date);

--
-- Triggers
--

CREATE TRIGGER update_organizations_updated_at
AFTER UPDATE ON organizations
FOR EACH ROW
BEGIN
    UPDATE organizations SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

CREATE TRIGGER update_users_updated_at
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

COMMIT;
