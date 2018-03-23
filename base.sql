CREATE SCHEMA `sym4` ;


CREATE TABLE `zlink` (
  `id_zlink` INT(10) UNSIGNED NOT NULL,
  `crea_date` DATETIME NULL,
  `source` VARCHAR(200) NULL,
  `objet` VARCHAR(200) NULL,
  `categorie` VARCHAR(20) NULL,
  `title` VARCHAR(45) NULL,
  `description` text
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 INSERT INTO `zlink` 
  VALUES (0, '0001-01-01 00:00:01', 'source/file', 'object->name', 'categorie', 'titre', 'description');

ALTER TABLE `zlink`
  ADD PRIMARY KEY (`id_zlink`);

ALTER TABLE `zlink`
  MODIFY `id_zlink` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;

UPDATE `zlink` SET `id_zlink` = 0 WHERE `id_zlink` = 1;

ALTER TABLE `zlink`
  MODIFY `id_zlink` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=0;
