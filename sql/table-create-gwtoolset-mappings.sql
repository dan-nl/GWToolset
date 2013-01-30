-- MySQL database schema for the GWToolset extension.
-- @author: dan entous
-- @datetime 2013-01-10 22:58 gmt +1
-- @license GNU General Public Licence 3.0 http://www.gnu.org/licenses/gpl.html


CREATE TABLE IF NOT EXISTS /*_*/gwtoolset_mappings (

	`user_name`				varchar(255)	NOT NULL,
	`mapping_name`			varchar(255)	NOT NULL,
	`mediawiki_template`	varchar(255)	NOT NULL,
	`mapping`				blob			NOT NULL,
	`created`				datetime		NOT NULL,
	PRIMARY KEY (`user_name`,`mapping_name`)

) /*$wgDBTableOptions*/;


INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mapping_name`, `mediawiki_template`, `mapping`, `created` ) VALUES ( 'GWToolset', 'dublin core : Artwork', 'Artwork', '{"artist":"dc:creator","title":"dc:title","description":"dc:description","date":"dc:date","medium":"dc:terms:medium","Dimensions":"dcterms:extent","Institution":"dcterms:provenance","Location":"dc:terms:spatial","References":"dc:terms:references","Objects history":"dc:terms:provenance","exhibition history":"dc:description","Credit line":"dc:rights","Inscriptions":"dc:description","notes":"dc:description","Accession number":"dc:identifier","permission":"dc:rights","other_versions":"dcterms:hasVersion","object type":"dc:type"}', now() );
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mapping_name`, `mediawiki_template`, `mapping`, `created` ) VALUES ( 'GWToolset', 'dublin core : Book', 'Book', '{"Author":"dc:creator","translator":"dcterms:contributor","Editor":"dcterms:contributor","Illustrator":"dcterms:contributor","Title":"dc:title","Subtitle":"dcterms:alternative","Series title":"dcterms:alternative","Volume":"dcterms:extent","Edition":"dcterms:extent","Publisher":"dc:publisher","Printer":"dc:publisher","Date":"dcterms:issued","City":"dc:publisher","Language":"dc:language","description":"dc:description","source":"dc:source","permissions":"dc:rights","image":"dc:description","Page overview":"dcterms:extent","Homecat":"dcterms:references","Other_versions":"dcterms:hasVersion","ISBN":"dc:identifier","LCCN":"dc:identifier","OCLC":"dc:identifier"}', now() );
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mapping_name`, `mediawiki_template`, `mapping`, `created` ) VALUES ( 'GWToolset', 'dublin core : Photograph', 'Photograph', '{"Photographer":"dc:creator","title":"dc:title","description":"dc:description","date":"dc:date","medium":"dcterms:medium","dimensions":"dcterms:extent","institution":"dcterms:provenance","department":"dcterms:spatial","references":"dcterms:extent","object history":"dcterms:provenance","exhibition history":"dcterms:provenance","credit line":"dc:rights","inscriptions":"dc:description","notes":"dc:description","accession number":"dc:identifier","source":"dc:source","permissions":"dc:rights","other_versions":"dcterms:references","other_fields":"dcterms:extent"}', now() );
INSERT INTO /*_*/gwtoolset_mappings ( `user_name`, `mapping_name`, `mediawiki_template`, `mapping`, `created` ) VALUES ( 'GWToolset', 'dublin core : Musical Work', 'Musical Work', '{"composer":"dc:creator","lyrics_writer":"dcterms:contributor","performer":"dcterms:contributor","title":"dc:title","description":"dc:title","composition_date":"dcterms:alternative","performance_date":"dcterms:alternative","notes":"dcterms:alternative","references":"dcterms:extent","source":"dc:publisher","permission":"dc:publisher","other_versions":"dcterms:issued","other_fields":"dc:publisher"}', now() );