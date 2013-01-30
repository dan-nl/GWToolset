<?php
/**
 * This is an example configuration file for the GWToolset Extenstion. When used
 * it should contain configuration settings that are unique to each install.
 *
 * Copy this file to a file named includes/config-custom.php and modify the values
 * as appropriate.
 */
namespace GWToolset;


#
# The url to your wiki’s api ( required )
#
# A possible value for this variable might be 'http://yourwikisdomain.com/w/api.php',
# however, some server configurations make it so that the url must be
# http://127.0.0.1/w/api.php
#
Config::$api_internal_endpoint = null;


#
# Your wiki’s user name that has access rights to the api ( required )
#
# For GWToolset, the api user must have the following access rights; see the
# INSTALL.md for further information :
#
#  upload
#  upload_by_url
#  edit
#
Config::$api_internal_lgname = null;


#
# The password for your wiki’s api user ( required )
#
Config::$api_internal_lgpassword = null;


#
# The url to an external wiki’s api
#
# This is the api url GWToolset will use to send confirmed uploaded data. For
# example, if the goal is to upload your metadata and media to commons you would
# indicate the commons.wikimedia.org api url.
#
Config::$api_external_endpoint = null;


#
# The external wiki’s api user name
#
Config::$api_external_lgname = null;


#
# The password for the external wiki’s api user
#
Config::$api_external_lgpassword = null;


#
# A flag to indicate whether or not to display debug information when available.
#
# In order to see this output, the user must also be a member of a permission group
# that has the right gwtoolset-debug
#
Config::$display_debug_output = false;

