-- ============================================================
--  في السكة — قاعدة البيانات (MySQL / MariaDB)
--  بتتنفّذ لوحدها من install.php — مش محتاج تشغّلها بإيدك.
-- ============================================================

CREATE TABLE IF NOT EXISTS zones (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name_ar     VARCHAR(80) NOT NULL UNIQUE,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS images (
  id          CHAR(32) PRIMARY KEY,
  mime        VARCHAR(40) NOT NULL,
  bytes       MEDIUMBLOB NOT NULL,
  byte_size   INT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS providers (
  id            CHAR(32) PRIMARY KEY,
  display_name  VARCHAR(80) NOT NULL,
  phone         VARCHAR(20) NOT NULL,
  whatsapp      VARCHAR(20) NULL,
  zone_id       INT NOT NULL,
  services      VARCHAR(200) NOT NULL DEFAULT '',   -- مفصولة بفاصلة
  vehicle_note  VARCHAR(60) NULL,
  note          VARCHAR(160) NULL,
  photo_id      CHAR(32) NULL,
  is_verified   TINYINT(1) NOT NULL DEFAULT 0,
  verified_on   DATE NULL,
  is_available  TINYINT(1) NOT NULL DEFAULT 0,
  available_at  DATETIME NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  token         CHAR(32) NOT NULL UNIQUE,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (zone_id), KEY (is_active),
  CONSTRAINT fk_prov_zone FOREIGN KEY (zone_id) REFERENCES zones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS places (
  id            CHAR(32) PRIMARY KEY,
  name_ar       VARCHAR(80) NOT NULL,
  category      VARCHAR(20) NOT NULL,
  zone_id       INT NOT NULL,
  phone         VARCHAR(20) NULL,
  whatsapp      VARCHAR(20) NULL,
  address_note  VARCHAR(140) NULL,
  hours_note    VARCHAR(60) NULL,
  note          VARCHAR(160) NULL,
  photo_id      CHAR(32) NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  sort_order    INT NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (category), KEY (zone_id), KEY (is_active),
  CONSTRAINT fk_place_zone FOREIGN KEY (zone_id) REFERENCES zones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS applications (
  id            CHAR(32) PRIMARY KEY,
  kind          ENUM('driver','place') NOT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  name          VARCHAR(80) NOT NULL,
  phone         VARCHAR(20) NOT NULL,
  whatsapp      VARCHAR(20) NULL,
  zone_id       INT NOT NULL,
  note          VARCHAR(320) NULL,
  photo_id      CHAR(32) NULL,
  services      VARCHAR(200) NULL,
  vehicle_note  VARCHAR(60) NULL,
  category      VARCHAR(20) NULL,
  address_note  VARCHAR(140) NULL,
  hours_note    VARCHAR(60) NULL,
  reject_reason VARCHAR(200) NULL,
  reviewed_at   DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS requests (
  id            CHAR(32) PRIMARY KEY,
  zone_id       INT NULL,
  kind          VARCHAR(20) NULL,
  body          VARCHAR(500) NOT NULL,
  contact_phone VARCHAR(20) NOT NULL,
  hide_phone    TINYINT(1) NOT NULL DEFAULT 1,
  is_hidden     TINYINT(1) NOT NULL DEFAULT 0,
  owner_token   CHAR(32) NOT NULL UNIQUE,
  expires_at    DATETIME NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (expires_at, is_hidden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  request_id  CHAR(32) NOT NULL,
  from_owner  TINYINT(1) NOT NULL,
  provider_id CHAR(32) NULL,
  body        VARCHAR(1000) NOT NULL,
  is_hidden   TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (request_id, created_at),
  CONSTRAINT fk_msg_req FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ratings (
  id          CHAR(32) PRIMARY KEY,
  provider_id CHAR(32) NULL,
  place_id    CHAR(32) NULL,
  stars       TINYINT NOT NULL,
  comment     VARCHAR(320) NULL,
  author_name VARCHAR(40) NULL,
  is_hidden   TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY (provider_id), KEY (place_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
  id             CHAR(32) PRIMARY KEY,
  provider_id    CHAR(32) NULL,
  place_id       CHAR(32) NULL,
  reason         VARCHAR(20) NOT NULL,
  details        VARCHAR(500) NULL,
  reporter_phone VARCHAR(20) NULL,
  handled_at     DATETIME NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicle_rates (
  kind        VARCHAR(20) PRIMARY KEY,
  starts_from DECIMAL(10,2) NOT NULL,
  note_ar     VARCHAR(120) NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS price_guide (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  from_zone_id INT NULL,
  to_zone_id   INT NULL,
  kind         VARCHAR(20) NULL,
  typical_min  DECIMAL(10,2) NULL,
  typical_max  DECIMAL(10,2) NULL,
  note_ar      VARCHAR(140) NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
