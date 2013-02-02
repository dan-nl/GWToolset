-- MySQL database schema for the GWToolset extension.
-- @author: dan entous
-- @datetime 2013-01-10 22:58 gmt +1
-- @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html


CREATE TABLE IF NOT EXISTS /*_*/gwtoolset_mediawiki_templates (

	`template_name`		varchar(255)	NOT NULL,
	`properties`			blob					NOT NULL,
	PRIMARY KEY (`template_name`)

) /*$wgDBTableOptions*/;

INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `template_name`, `properties` ) VALUES ( 'Artwork', '["artist","other_fields_1","title","object_type","description","other_fields_2","date","medium","dimensions","institution","location","accession_number","object_history","exhibition_history","credit_line","inscriptions","notes","other_fields_3","references","source","permission","other_versions","other_fields","url_to_the_media_file"]' );
INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `template_name`, `properties` ) VALUES ( 'Book', '["author","editor","translator","illustrator","title","subtitle","series_title","volume","edition","authority_control","publisher","printer","year_of_publication","place_of_publication","language","description","page_overview","source","permission"]' );
INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `template_name`, `properties` ) VALUES ( 'Photograph', '["title","original_caption","description","depicted_people","camera_location","date","medium","dimensions","photographer","inscriptions","institution","accession_number","credit_line","object_history","exhibition_history","notes","references","source","permission","other_versions"]' );