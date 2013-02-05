-- MySQL database schema for the GWToolset extension.
-- @author: dan entous
-- @datetime 2013-01-10 22:58 gmt +1
-- @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html


CREATE TABLE IF NOT EXISTS /*_*/gwtoolset_mediawiki_templates (

	`template_name`						varchar(255)	NOT NULL,
	`template_parameters`			blob					NOT NULL,
	PRIMARY KEY (`template_name`)

) /*$wgDBTableOptions*/;

-- https://commons.wikimedia.org/wiki/Template:Artwork
INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `template_name`, `template_parameters` ) VALUES ( 'Artwork', '{"artist":"","title":"","description":"","date":"","medium":"","dimensions":"","institution":"","location":"","references":"","object history":"","exhibition history":"","credit line":"","inscriptions":"","notes":"","accession number":"","source":"","permission":"","other_versions":"","url_to_the_media_file":""}' );

-- https://commons.wikimedia.org/wiki/Template:Book
INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `template_name`, `template_parameters` ) VALUES ( 'Book', '{"Author":"","Translator":"","Editor":"","Illustrator":"","Title":"","Subtitle":"","Series title":"","Volume":"","Edition":"","Publisher":"","Printer":"","Date":"","City":"","Language":"","Description":"","Source":"","Permission":"","Image":"","Image page":"","Pageoverview":"","Wikisource":"","Homecat":"","Otherversions":"","ISBN":"","LCCN":"","OCLC":"","url_to_the_media_file":""}' );

-- https://commons.wikimedia.org/wiki/Template:Musical_work
INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `template_name`, `template_parameters` ) VALUES ( 'Musical work', '{"composer":"","lyrics_writer":"","performer":"","title":"","description":"","composition_date":"","performance_date":"","notes":"","references":"","source":"","permission":"","other_versions":"","url_to_the_media_file":""}' );

-- https://commons.wikimedia.org/wiki/Template:Photograph
INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `template_name`, `template_parameters` ) VALUES ( 'Photograph', '{"photographer":"","title":"","descriptions":"","depicted people":"","depicted place":"","date":"","medium":"","dimensions":"","institution":"","department":"","references":"","object history":"","exhibition history":"","credit line":"","inscriptions":"","notes":"","accession number":"","source":"","permission":"","other_versions":"","url_to_the_media_file":""}' );