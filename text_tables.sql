CREATE TABLE `oracc_wordinfo` (
 `ow_id` int(11) NOT NULL AUTO_INCREMENT,
 `word_id` int(11) NOT NULL,
 `span_id` text,
 `title` text,
 `href` text,
 PRIMARY KEY (`ow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 

CREATE TABLE `orig_transl_cross` (
 `orig_transl_id` int(11) NOT NULL,
 `text_db_id` int(11) DEFAULT NULL,
 `orig_word_id` int(11) NOT NULL,
 `transl_word_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8

CREATE TABLE `orig_words` (
 `word_id` int(30) NOT NULL AUTO_INCREMENT,
 `text_db_id` int(10) NOT NULL,
 `line_nr` int(5) NOT NULL,
 `line_name` varchar(10) DEFAULT NULL,
 `word_nr` int(5) NOT NULL,
 `word` varchar(50) NOT NULL,
 `word_note` varchar(8) DEFAULT NULL,
 PRIMARY KEY (`word_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

CREATE TABLE `places` (
 `place_id` int(11) NOT NULL AUTO_INCREMENT,
 `place_name` varchar(20) NOT NULL,
 PRIMARY KEY (`place_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

CREATE TABLE `texts` (
 `text_db_id` int(10) NOT NULL AUTO_INCREMENT,
 `text_id` varchar(50) NOT NULL,
 PRIMARY KEY (`text_db_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

CREATE TABLE `text_refs` (
 `text_ref_id` int(11) NOT NULL AUTO_INCREMENT,
 `orig_word_id` int(11) NOT NULL,
 `word_type` varchar(11) NOT NULL,
 `word_type_ref` int(11) NOT NULL,
 PRIMARY KEY (`text_ref_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

CREATE TABLE `translat_sect` (
 `ts_id` int(20) NOT NULL AUTO_INCREMENT,
 `text_db_id` int(10) NOT NULL,
 `line_start` varchar(11) DEFAULT NULL,
 `word_start` varchar(11) DEFAULT NULL,
 `line_last` varchar(11) DEFAULT NULL,
 `word_last` varchar(11) DEFAULT NULL,
 `translation` text,
 PRIMARY KEY (`ts_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

CREATE TABLE `transl_words` (
 `word_id` int(11) NOT NULL AUTO_INCREMENT,
 `text_db_id` int(11) NOT NULL,
 `line_nr` int(11) NOT NULL,
 `line_name` varchar(20) DEFAULT NULL,
 `word_nr` int(11) NOT NULL,
 `word` varchar(100) NOT NULL,
 PRIMARY KEY (`word_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8

CREATE TABLE `word_types` (
 `word_type_id` int(11) NOT NULL AUTO_INCREMENT,
 `word_type` varchar(11) NOT NULL,
 `type_option_table` varchar(11) NOT NULL,
 `select_name` varchar(40) NOT NULL,
 PRIMARY KEY (`word_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8