
-- HSGG Lernzentrum - Datenbank Setup


-- Datenbank erstellen
CREATE DATABASE IF NOT EXISTS hsgg_lernzentrum;
USE hsgg_lernzentrum;


-- Tabellen löschen

DROP TABLE IF EXISTS downloads;
DROP TABLE IF EXISTS faecher;
DROP TABLE IF EXISTS users;


-- Tabelle: users

CREATE TABLE users
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM ('schueler', 'lehrer') DEFAULT 'schueler',
    created_at TIMESTAMP                   DEFAULT CURRENT_TIMESTAMP
);

-- Tabelle: faecher

CREATE TABLE faecher
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    folder_path VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Tabelle: downloads

CREATE TABLE downloads
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    file_path   VARCHAR(500) NOT NULL,
    subject_id  INT,
    uploaded_by INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES faecher (id),
    FOREIGN KEY (uploaded_by) REFERENCES users (id)
);


-- Test-Daten: Users

INSERT INTO users (username, email, password, role)
VALUES ('TestSchueler', 'schueler@test.de', '12345', 'schueler'),
       ('TestLehrer', 'lehrer@test.de', '12345', 'lehrer'),
       ('Max Mustermann', 'max@test.de', '12345', 'schueler');


-- Test-Daten: Fächer

INSERT INTO faecher (name, description, folder_path)
VALUES ('Mathematik', 'Mathematik für die 5. Klasse', 'Faecher/mathe.php'),
       ('Deutsch', 'Deutsch für die 5. Klasse', 'Faecher/deutsch.php'),
       ('Englisch', 'Englisch für die 5. Klasse', 'Faecher/englisch.php'),
       ('Wissenschaft', 'Naturwissenschaften für die 5. Klasse', 'Faecher/wissenschaft.php'),
       ('Erdkunde', 'Geographie für die 5. Klasse', 'Faecher/erdkunde.php'),
       ('Geschichte', 'Geschichte für die 5. Klasse', 'Faecher/geschichte.php'),
       ('Kunst', 'Kunst für die 5. Klasse', 'Faecher/kunst.php'),
       ('Musik', 'Musik für die 5. Klasse', 'Faecher/musik.php');


-- Test-Daten: Downloads

INSERT INTO downloads (title, description, file_path, subject_id, uploaded_by)
VALUES ('Mathe Übung 1', 'Addition und Subtraktion großer Zahlen',
        'Nachhilfematerial_fuer_fuenfte_Klasse_first_five.pdf', 1, 2),
       ('Mathe Erklärung', 'Erklärung zu Addition', 'Faecher/Erklärungen/Addition.php', 1, 2);
CREATE TABLE IF NOT EXISTS suchbegriffe
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    stichwort    VARCHAR(100) NOT NULL,
    ziel_url     VARCHAR(255) NOT NULL,
    beschreibung TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stichwort (stichwort)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- Beispiel-Einträge einfügen
INSERT INTO suchbegriffe (stichwort, ziel_url, beschreibung)
VALUES
-- Fächer (Hauptseiten)
('Mathematik', 'Faecher/mathe.php', 'Mathematik Hauptseite'),
('Mathe', 'Faecher/mathe.php', 'Mathematik Hauptseite'),
('Wissenschaft', 'Faecher/wissenschaft.php', 'Wissenschaft Hauptseite'),
('Deutsch', 'Faecher/deutsch.php', 'Deutsch Hauptseite'),
('Englisch', 'Faecher/englisch.php', 'Englisch Hauptseite'),
('Erdkunde', 'Faecher/erdkunde.php', 'Erdkunde Hauptseite'),
('Geographie', 'Faecher/erdkunde.php', 'Erdkunde Hauptseite'),
('Kunst', 'Faecher/kunst.php', 'Kunst Hauptseite'),
('Musik', 'Faecher/musik.php', 'Musik Hauptseite'),
('Geschichte', 'Faecher/geschichte.php', 'Geschichte Hauptseite'),

-- Mathematik Themenhsgg_lernzentrum
('Rechnen mit großen Zahlen', 'Faecher/mathe.php?topic=grundrechenarten', 'Rechnen mit großen Zahlen'),
('große Zahlen', 'Faecher/mathe.php?topic=grundrechenarten', 'Rechnen mit großen Zahlen'),
('Addition', 'Faecher/Erklärungen/Addition.php', 'Addition Übungen'),
('Subtraktion', 'Faecher/mathe.php?topic=grundrechenarten', 'Addition und Subtraktion'),

-- Wissenschaft Themen
('Physik', 'Faecher/wissenschaft.php?topic=physik', 'Physik Thema'),
('Chemie', 'Faecher/wissenschaft.php?topic=chemie', 'Chemie Thema'),


-- Deutsch Themen
('Grammatik', 'Faecher/deutsch.php?topic=grammatik', 'Deutsche Grammatik'),


-- Englisch Themen
('Vocabulary', 'Faecher/englisch.php?topic=vocabulary', 'English Vocabulary'),


-- Erdkunde Themen
('Kontinente', 'Faecher/erdkunde.php?topic=kontinente', 'Kontinente und Länder'),


-- Kunst Themen
('Malerei', 'Faecher/kunst.php?topic=malerei', 'Malerei'),


-- Musik Themen
('Musiktheorie', 'Faecher/musik.php?topic=musiktheorie', 'Musiktheorie'),


-- Geschichte Themen
('Antike', 'Faecher/geschichte.php?topic=antike', 'Antike');

-- Überprüfen was eingefügt wurde
use hsgg_lernzentrum;
SELECT *
FROM suchbegriffe
ORDER BY stichwort;



CREATE TABLE Kategorien
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    fach_id     INT          NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    FOREIGN KEY (fach_id) REFERENCES faecher (id) ON DELETE CASCADE
);


ALTER TABLE kategorien
    ADD parent_id INT NULL,
    ADD FOREIGN KEY (parent_id) REFERENCES kategorien (id) ON DELETE CASCADE;



INSERT INTO kategorien(fach_id, name, description, parent_id)
VALUES (1, 'Rechnen mit großen Zahlen', 'Alles zu rechnen mit großen Zahlen', NULL);

INSERT INTO kategorien (fach_id, name, description, parent_id)
VALUES (1,
        'Addition und Subtraktion großer Zahlen',
        'Addition großer Zahlen',
        1);

INSERT INTO kategorien (fach_id, name, description, parent_id)
VALUES (1,
        'Multiplikation und Division großer Zahlen',
        'Multiplikation und Division von großen Zahlen',
        1);


CREATE TABLE erklaerungen
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    kategorie_id INT  NOT NULL,
    content      TEXT NOT NULL,
    FOREIGN KEY (kategorie_id) REFERENCES kategorien (id) ON DELETE CASCADE
);



INSERT INTO erklaerungen(kategorie_id, content)
VALUES (2, ' Bei der Addition und Subtraktion grosser Zahlen hilft es, die Zahlen stellengerecht
untereinander zu schreiben.
Beispiel: 7421 + 5634 = 13.055 und 9876 - 1234 = 8642');


ALTER TABLE downloads
    ADD COLUMN kategorie_id INT NULL,
    ADD CONSTRAINT downloads_ibfk_kategorie
        FOREIGN KEY (kategorie_id)
            REFERENCES kategorien (id)
            ON DELETE CASCADE;



UPDATE downloads
set kategorie_id = 2,
    subject_id   = 1
WHERE id = 1;

INSERT INTO kategorien(fach_id, name, description, parent_id)
VALUES (1, 'Bruchrechnung', 'Grundlagen der Bruchrechnung', NULL);

INSERT INTO kategorien(fach_id, name, description, parent_id)
VALUES (1, 'Bruchrechnung: Einführung', 'Grundlagen der Bruchrechnung', 4);



UPDATE kategorien
SET name ='Einführung in die Bruchrechnung'
WHERE id = 5;


CREATE TABLE aufgaben (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          kategorie_id INT NOT NULL,
                          frage TEXT NOT NULL,
                          typ ENUM('variable', 'blank', 'multiple_choice') NOT NULL,
                          created_by INT NOT NULL,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                          FOREIGN KEY (kategorie_id) REFERENCES kategorien(id)
                              ON DELETE CASCADE,
                          FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE TABLE aufgaben_felder (
                                 id INT AUTO_INCREMENT PRIMARY KEY,
                                 aufgabe_id INT NOT NULL,
                                 name VARCHAR(50) NOT NULL,
                                 korrekter_wert VARCHAR(50) NOT NULL,

                                 FOREIGN KEY (aufgabe_id)
                                     REFERENCES aufgaben(id)
                                     ON DELETE CASCADE
);
CREATE TABLE aufgaben_optionen (
                                   id INT AUTO_INCREMENT PRIMARY KEY,
                                   aufgabe_id INT NOT NULL,
                                   text VARCHAR(255) NOT NULL,
                                   ist_korrekt BOOLEAN DEFAULT 0,

                                   FOREIGN KEY (aufgabe_id)
                                       REFERENCES aufgaben(id)
                                       ON DELETE CASCADE
);


ALTER TABLE `users`
    MODIFY COLUMN `role` ENUM('schueler', 'lehrer', 'admin') DEFAULT 'schueler';



INSERT INTO users(username,email,password,role)
VALUES (
           'Adminname','admin@test.de','12345','admin'
       );

ALTER TABLE downloads ADD COLUMN uebung_id INT NULL AFTER kategorie_id;
CREATE TABLE IF NOT EXISTS kontakt_nachrichten (
                                                   id INT AUTO_INCREMENT PRIMARY KEY,
                                                   name VARCHAR(255) NOT NULL,
                                                   email VARCHAR(255) NOT NULL,
                                                   betreff VARCHAR(255) NOT NULL,
                                                   nachricht TEXT NOT NULL,
                                                   datum DATETIME NOT NULL,
                                                   gelesen BOOLEAN DEFAULT FALSE
);


ALTER TABLE `downloads`
    ADD COLUMN `section` ENUM('uebung', 'erklaerung') DEFAULT 'uebung'
        AFTER `uebung_id`;


ALTER TABLE kontakt_nachrichten ADD COLUMN gelesen TINYINT(1) DEFAULT 0;
