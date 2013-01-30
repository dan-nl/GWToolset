-- MySQL database schema for the GWToolset extension.
-- @author: dan entous
-- @datetime 2013-01-10 22:58 gmt +1
-- @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html


CREATE TABLE IF NOT EXISTS /*_*/gwtoolset_mediawiki_templates (

	`name`	varchar(255)	NOT NULL,
	PRIMARY KEY (`name`)

) /*$wgDBTableOptions*/;


INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `name` ) VALUES ( 'Artwork');
INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `name` ) VALUES ( 'Book' );
INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `name` ) VALUES ( 'Photograph' );
INSERT INTO /*_*/gwtoolset_mediawiki_templates ( `name` ) VALUES ( 'Musical Work' );