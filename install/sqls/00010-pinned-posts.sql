ALTER TABLE `posts` ADD `pinned` BOOLEAN NOT NULL DEFAULT FALSE AFTER `ad`, ADD INDEX (`pinned`);