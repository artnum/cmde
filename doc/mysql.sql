SET SQL_MODE=ANSI_QUOTES;
CREATE DATABASE "cmde";
USE "cmde";

CREATE TABLE IF NOT EXISTS "commande" (
    "commande_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "commande_reference" CHAR(12) NOT NULL DEFAULT '' COMMENT 'uid + check digit or any other checking solution',
    "commande_altreference" CHAR (40) NOT NULL DEFAULT '' COMMENT 'manually added reference by the creator',
    "commande_description" TEXT NOT NULL DEFAULT '',
    "commande_currency" CHAR(3) DEFAULT 'CHF' COMMENT 'currency ISO 4217 format',
    "commande_owner" CHAR(128) NOT NULL DEFAULT '' COMMENT 'Stored on another db/ldap'
) COMMENT 'Order description' CHARACTER SET 'utf8mb4';

CREATE TABLE IF NOT EXISTS "csupp" ( -- fit in cattrchange
    "csupp_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "csupp_commande" INTEGER UNSIGNED NOT NULL,
    "csupp_supplier" CHAR(128) NOT NULL DEFAULT '' COMMENT 'Stored on ldap',
    "csupp_reference" CHAR(160) NOT NULL DEFAULT '' COMMENT 'out reference, from the supplier',
    "csupp_price" INTEGER UNSIGNED NOT NULL COMMENT 'not float value, 10.25 -> 10.25 * 1000 = 10250',
    FOREIGN KEY("csupp_commande") REFERENCES "commande"("commande_uid") ON UPDATE CASCADE ON DELETE CASCADE
) COMMENT 'To whom it may concern';

CREATE TABLE IF NOT EXISTS "cdate" (
    "cdate_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "cdate_commande" INTEGER UNSIGNED NOT NULL,
    "cdate_csupp" INTEGER UNSIGNED DEFAULT NULL,
    "cdate_name" CHAR(20) NOT NULL,
    "cdate_value" CHAR(32) NOT NULL COMMENT 'date iso8601',
    FOREIGN KEY ("cdate_commande") REFERENCES "commande"("commande_uid") ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY ("cdate_csupp") REFERENCES "csupp"("csupp_uid") ON UPDATE CASCADE ON DELETE SET NULL

) COMMENT 'Date given during order (delivery, validity, warranty end, ...)' CHARACTER SET 'ascii';

CREATE TABLE IF NOT EXISTS "ptype" (
    "ptype_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "ptype_name" CHAR(60) NOT NULL DEFAULT '',
    "ptype_code" CHAR(40) NOT NULL DEFAULT '' COMMENT 'Code if any to identify in the real world',
    "ptype_color" CHAR(30) NOT NULL DEFAULT 'black' COMMENT 'Color associated with the type',
    "ptype_description" VARCHAR(260) NOT NULL DEFAULT '' COMMENT 'description of the type',
    "ptype_category" VARCHAR(20) NOT NULL DEFAULT '' COMMENT 'Category (progress type, material type, storage type, ...)',
    "ptype_deleted" INT UNSIGNED DEFAULT 0 COMMENT 'unix timestamp of deletion',
    "ptype_locked" TINYINT UNSIGNED DEFAULT 0 COMMENT 'set to 1 if cannot be deleted',
    UNIQUE INDEX ("ptype_code")
) COMMENT 'Type of any available' CHARACTER SET 'utf8mb4';

CREATE TABLE IF NOT EXISTS "succession" (
    "succession_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "succession_from" INTEGER UNSIGNED NOT NULL,
    "succession_to" INTEGER UNSIGNED NOT NULL,
    FOREIGN KEY ("succession_from") REFERENCES "ptype"("ptype_uid") ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY ("succession_to") REFERENCES "ptype"("ptype_uid") ON UPDATE CASCADE ON DELETE CASCADE
) COMMENT 'Succession of progress type (ptype)';

CREATE TABLE IF NOT EXISTS "cmate" ( -- name is set as long as cdate to fit into pattrchange
    "cmate_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "cmate_commande" INTEGER UNSIGNED NOT NULL,
    "cmate_ptype" INTEGER UNSIGNED NOT NULL,

    FOREIGN KEY ("cmate_commande") REFERENCES "commande"("commande_uid") ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY ("cmate_ptype") REFERENCES "ptype"("ptype_uid") ON UPDATE CASCADE ON DELETE CASCADE
) COMMENT 'Set material type of the order';

CREATE TABLE IF NOT EXISTS "cstre" ( -- name is set as long as cdate to fit into pattrchange
    "cstre_commande" INTEGER UNSIGNED NOT NULL,
    "cstre_ptype" INTEGER UNSIGNED NOT NULL,

    PRIMARY KEY ("cstre_commande", "cstre_ptype"),
    FOREIGN KEY ("cstre_commande") REFERENCES "commande"("commande_uid") ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY ("cstre_ptype") REFERENCES "ptype"("ptype_uid") ON UPDATE CASCADE ON DELETE CASCADE
) COMMENT 'Set storage places for the order';

CREATE TABLE IF NOT EXISTS "progression" (
    "progression_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "progression_commande" INTEGER UNSIGNED NOT NULL,
    "progression_csupp" INTEGER UNSIGNED DEFAULT NULL COMMENT 'progression linked to a specific supplier',
    "progression_timestamp" INTEGER UNSIGNED NOT NULL COMMENT 'unix timestamp of when it happened',
    "progression_type" INTEGER UNSIGNED NOT NULL COMMENT 'Any type like quote request, ...',
    "progression_message" VARCHAR(260) NOT NULL DEFAULT '' COMMENT 'why this progression happens',
    "progression_creator" CHAR(128) NOT NULL DEFAULT '' COMMENT 'Stored on another db/ldap',

    FOREIGN KEY ("progression_type") REFERENCES "ptype"("ptype_uid") ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY ("progression_commande") REFERENCES "commande"("commande_uid") ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY ("progression_csupp") REFERENCES "csupp"("csupp_uid") ON UPDATE CASCADE ON DELETE SET NULL
) COMMENT 'each step of an order, from quote to delivery' CHARACTER SET 'utf8mb4';

CREATE TABLE IF NOT EXISTS "pattrchange" (
    "pattrchange_uid" BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "pattrchange_progression" INTEGER UNSIGNED NOT NULL,
    "pattrchange_attribute" CHAR(50) NOT NULL COMMENT 'attribute name from commande table changed (max len: <cdate:[name of 20 chars]>)',
    "pattrchange_value" CHAR(160) NOT NULL DEFAULT '' COMMENT 'previous value or part of if too big',
    FOREIGN KEY ("pattrchange_progression") REFERENCES "progression"("progression_uid") ON UPDATE CASCADE ON DELETE CASCADE
) COMMENT 'describe attributes changed during a progression event' CHARACTER SET 'utf8mb4';

CREATE TABLE IF NOT EXISTS "cmdprj" (
    "cmdprj_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "cmdprj_project" INTEGER UNSIGNED NOT NULL COMMENT 'Project might be stored on another db',
    "cmdprj_commande" INTEGER UNSIGNED NOT NULL,
    FOREIGN KEY ("cmdprj_commande") REFERENCES "commande"("commande_uid") ON UPDATE CASCADE ON DELETE CASCADE
) COMMENT 'link project to command, multiple project on one commmande' CHARACTER SET 'ascii';

CREATE TABLE IF NOT EXISTS "cmdfile" (
    "cmdfile_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "cmdfile_file" CHAR (64) NOT NULL COMMENT 'sha256 of the file, file storage outside application',
    "cmdfile_commande" INTEGER UNSIGNED NOT NULL,
    "cmdfile_csupp" INTEGER UNSIGNED DEFAULT NULL COMMENT 'file linked to a specific supplier',
    "cmdfile_progression" INTEGER UNSIGNED NOT NULL DEFAULT 0 COMMENT 'if linked to a progression event, then set here',
    "cmdfile_type" CHAR (64) NOT NULL DEFAULT '' COMMENT 'mimetype of the file',
    "cmdfile_humanname" VARCHAR (255) NOT NULL DEFAULT '' COMMENT 'name given by the user, up to 255 as it was/is a limit on windows',
    FOREIGN KEY ("cmdfile_commande") REFERENCES "commande"("commande_uid") ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY ("cmdfile_csupp") REFERENCES "csupp"("csupp_uid") ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY ("cmdfile_progression") REFERENCES "progression"("progression_uid") ON UPDATE CASCADE ON DELETE SET DEFAULT
) COMMENT 'link files to order' CHARACTER SET "ascii";

CREATE TABLE IF NOT EXISTS "item" (
    "item_uid" INTEGER UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    "item_commande" INTEGER UNSIGNED NOT NULL,
    "item_cmate" INTEGER UNSIGNED DEFAULT NULL,
    "item_name" VARCHAR(60) NOT NULL DEFAULT '',
    "item_description" VARCHAR(200) NOT NULL DEFAULT '',
    "item_quantity" INTEGER DEFAULT 0,
    "item_unitvalue" INTEGER DEFAULT NULL,
    "item_unitname" CHAR(20) DEFAULT '',
    "item_price" INTEGER DEFAULT 0 COMMENT 'price, no float as 7.5 * 1000 = 7500, so up to 3 decimals',
    "item_internid" INTEGER UNSIGNED DEFAULT 0 COMMENT 'Internal id, an id from an internal database',
    "item_reference" VARCHAR(200) DEFAULT '' COMMENT 'supplier reference number/code',
    FOREIGN KEY ("item_commande") REFERENCES "commande"("commande_uid") ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY ("item_cmate") REFERENCES "cmate"("cmate_uid") ON UPDATE CASCADE ON DELETE SET NULL
) COMMENT 'Item line in a order ' CHARACTER SET 'utf8mb4';

INSERT INTO "ptype" ("ptype_uid", "ptype_name", "ptype_code", "ptype_color", "ptype_category") VALUES 
    (1, 'Création', 'create', 'black', 'progress'),
    (2, 'Fermeture', 'close', 'black', 'progress'),
    (3, 'Envoi', 'send-order', 'black', 'progress'), 
    (4, 'Suppression', 'delete', 'black', 'progress'),
    (5, 'Restauration', 'restore', 'black', 'progress'),
    (6, 'Réouverture', 'reopen', 'black', 'progress');

INSERT INTO "succession" ("succession_from", "succession_to") VALUES 
    (1,2), (1,3), (1,4), (2,6), (2,4), (2,4), (3,4), (5,2), (5,3), (5,4),(6,2),(6,3),(6,4);