/*global jQuery, mw */
/*jslint browser: true, continue: true, white: true, devel: true, regexp: true, todo: true */
(function($) {


	'use strict';


	var digitTest = /^\d+$/,
		keyBreaker = /([^\[\]]+)|(\[\])/g,
		plus = /\+/g,
		paramTest = /([^?#]*)(#.*)?$/,
		gwtoolset ={};


	/**
	 * @add jQuery.String
	 * @see https://github.com/jupiterjs/jquerymx/blob/master/lang/string/deparam/deparam.js
	 */
	$.String = $.extend($.String || {}, {

		/**
		 * @function deparam
		 *
		 * Takes a string of name value pairs and returns a Object literal that represents those params.
		 *
		 * @param {String} params a string like <code>"foo=bar&person[age]=3"</code>
		 * @return {Object} A JavaScript Object that represents the params:
		 *
		 *     {
		 *       foo: "bar",
		 *       person: {
		 *         age: "3"
		 *       }
		 *     }
		 */
		deparam: function(params){

			if ( ! params || !paramTest.test( params ) ) {

				return {};

			}

			var current,
				data = {},
				i,
				j,
				key,
				lastPart,
				pair,
				pairs = params.split('&'),
				part,
				parts,
				value;

			for( i = 0; i < pairs.length; i += 1 ) {

				current = data;
				pair = pairs[i].split('=');

				// if we find foo=1+1=2
				if ( pair.length !== 2 ) {

					pair = [ pair[0], pair.slice(1).join("=") ];

				}

				key = decodeURIComponent( pair[0].replace( plus, " " ) );
				value = decodeURIComponent( pair[1].replace( plus, " " ) );
				parts = key.match( keyBreaker );

				for ( j = 0; j < parts.length - 1; j += 1 ) {

					part = parts[ j ];

					if ( !current[ part ] ) {

						// if what we are pointing to looks like an array
						current[ part ] = digitTest.test( parts[ j + 1 ] ) || parts[ j + 1 ] === "[]" ? [] : {};

					}

					current = current[ part ];

				}

				lastPart = parts[ parts.length - 1 ];

				if ( lastPart === "[]" ) {

					current.push( value );

				} else {

					current[lastPart] = value;

				}

			}

			return data;

		}

	});


	gwtoolset = {

		display_debug_output : true,
		empty_console : { log : function() {} },
		$form : $('#gwtoolset-form'),
		$ajax_loader : $( '<div/>', { 'id':'gwtoolset-loader' }),
		$template_table : $('#template-table > tbody'),
		$save_mapping_button : $('<tr><td colspan="3" style="text-align:right;"><span id="save-mapping" title="' + mw.message('gwtoolset-save-mapping').escaped() + '">' + mw.message('gwtoolset-save-mapping').escaped() + '</span></td></tr>'),
		$buttons : {
			$add : $('<img/>', { 'src' : '/extensions/GWToolset/resources/images/b_snewtbl.png', 'class' : 'gwtoolset-metadata-button' }),
			$subtract : $('<img/>', { 'src' : '/extensions/GWToolset/resources/images/b_drop.png', 'class' : 'gwtoolset-metadata-button' })
		},
		$metadata_buttons : $('.metadata-add, .metadata-subtract'),
		$category_buttons : $('.category-add, .category-subtract'),


		handleCategoryButtonAddClick : function( evt ) {

			var $target = $(this).parent().parent(),
					$td_input = $(this).parent().next().clone(),
					$td_select = $(this).parent().next().next().clone(),
					$button = gwtoolset.$buttons.$subtract.clone().on( 'click', gwtoolset.handleCategoryButtonSubtractClick ),
					$td_button = jQuery('<td/>',{'class':'category-subtract'}).html( $button ),
					$row = jQuery('<tr></tr>');

			evt.preventDefault();

			$td_input.find('input').val('');
			$td_select.find('option').prop("selected", false);
			$row.append( $td_button ).append( $td_input ).append( $td_select );
			$row.insertAfter( $target );

		},


		handleCategoryButtonSubtractClick : function( evt ) {

			evt.preventDefault();
			jQuery(this).parent().parent().remove();

		},


		addCategoryButtons : function() {

			var $elm;

			gwtoolset.$category_buttons.each(function() {

				var class_name;

				$elm =	jQuery(this);
				class_name = $elm.attr('class');

				if ( 'category-add' === class_name ) {

					$elm.html( gwtoolset.$buttons.$add.clone().on( 'click', gwtoolset.handleCategoryButtonAddClick ) );

				}

				if ( 'category-subtract' === class_name ) {

					$elm.html( gwtoolset.$buttons.$subtract.clone().on( 'click', gwtoolset.handleCategoryButtonSubtractClick ) );

				}

			});

		},


		handleMetadataButtonAddClick : function( evt ) {

			var $target = $(this).parent().parent(),
					$td_select = $(this).parent().next().clone(),
					$button = gwtoolset.$buttons.$subtract.clone().on( 'click', gwtoolset.handleMetadataButtonSubtractClick ),
					$td_button = jQuery('<td/>',{'class':'metadata-subtract'}).html( $button ),
					$row = jQuery('<tr><td>&nbsp;</td></tr>');

			evt.preventDefault();

			$td_select.find('input').val('');
			$td_select.find('option').prop("selected", false);
			$row.append( $td_button ).append( $td_select );
			$row.insertAfter( $target );

		},


		handleMetadataButtonSubtractClick : function( evt ) {

			evt.preventDefault();
			jQuery(this).parent().parent().remove();

		},


		addMetadataButtons : function() {

			var $elm;

			gwtoolset.$metadata_buttons.each(function() {

				var class_name;

				$elm =	jQuery(this);
				class_name = $elm.attr('class');

				if ( 'metadata-add' === class_name ) {

					$elm.html( gwtoolset.$buttons.$add.clone().on( 'click', gwtoolset.handleMetadataButtonAddClick ) );

				}

				if ( 'metadata-subtract' === class_name ) {

					$elm.html( gwtoolset.$buttons.$subtract.clone().on( 'click', gwtoolset.handleMetadataButtonSubtractClick ) );

				}

			});

		},


		/**
		 * of using an alert
		 */
		handleAjaxError : function() {

			alert( mw.message('gwtoolset-save-mapping-error').escaped() );
			console.log( arguments );

		},


		/**
		 * of using an alert
		 *
		 * @param {object} data
		 * @param {string} textStatus
		 * @param {object} jqXHR
		 */
		handleAjaxSuccess : function ( data, textStatus, jqXHR ) {

			if ( !data.status || data.status !== 'succeeded' || !textStatus || !jqXHR ) {

				alert( mw.message('gwtoolset-save-mapping-failed').escaped() );

			} else {

				alert( mw.message('gwtoolset-save-mapping-succeeded').escaped() );

			}

			console.log( arguments );

		},


		handleAjaxComplete : function () { console.log('ajax complete'); },


		getEdiToken : function( fields ) {

			var result = null, i, ii = fields.length;

			for ( i = 0; i < ii; i += 1 ) {

				if ( fields[i].name === 'wpEditToken' ) {

					result = fields[i].value;
					break;

				}

			}

			return result;

		},


		/**
		 * sends the appropriate data to the server for the mapping to be created/updated
		 *
		 * @param {string} mapping_name_to_use a name for the mapping to be saved as
		 * @param {string} mediawiki_template the name of the mediawiki template the mapping applies to
		 * @param {string} the edit token that allows the user to amke edits to stored data
		 * @param {array} metadata_mappings a json array of the selected mappings
		 * @todo handle server error, timeout, etc.
		 */
		saveMapping : function( mapping_name_to_use, mediawiki_template_name, wpEditToken, metadata_mappings ) {

			var self = this;

			$.ajax({
				type : 'POST',
				url : mw.config.get('wgArticlePath').replace("$1", "") + 'Special:GWToolset',
				data : {
					'gwtoolset-form' : 'metadata-mapping-save',
					'mapping-name-to-use' : mapping_name_to_use,
					'mediawiki-template-name' : mediawiki_template_name,
					'wpEditToken' : wpEditToken,
					'metadata-mappings' : metadata_mappings
				},
				error : function( jqXHR, textStatus, errorThrown ) { self.handleAjaxError( jqXHR, textStatus, errorThrown ); },
				success : function( data, textStatus, jqXHR ) { self.handleAjaxSuccess( data, textStatus, jqXHR ); },
				complete : function( jqXHR, textStatus ) { self.handleAjaxComplete( jqXHR, textStatus ); },
				timeout : 5000
			});

		},


		handleSaveMappingClick : function( evt ) {

			var self = evt.data.self,
				mapping_name_to_use = prompt( mw.message('gwtoolset-save-mapping-name').escaped(), $('#gwtoolset-metadata-mapping').val() ),
				mediawiki_template_name = $('#gwtoolset-mediawiki-template-name').val(),
				wpEditToken = $('#wpEditToken').val(),
				metadata_mappings = self.$form.find('select').serialize();

			evt.preventDefault();
			metadata_mappings = $.String.deparam( metadata_mappings );

			if ( mapping_name_to_use !== null && mapping_name_to_use.length > 3 ) {

				self.saveMapping( mapping_name_to_use, mediawiki_template_name, wpEditToken, metadata_mappings );

			}

		},


		addSaveMappingButton : function() {

			if ( this.$template_table.length === 1 ) {

				this.$template_table.append( this.$save_mapping_button );
				this.$save_mapping_button.on( 'click', { self : this }, this.handleSaveMappingClick );

			}

		},


		handleFormSubmit : function(e) {

			var self = e.data.self;
			self.$ajax_loader.fadeIn();

		},


		addAjaxLoader : function() {

			this.$ajax_loader
				.append('<p><img src="/skins/common/images/ajax-loader.gif"/><br/>' + mw.msg('gwtoolset-loading') + '</p>');
			this.$form.prepend( this.$ajax_loader );

		},


		addFormListener : function() {

			if ( this.$form.length < 1 ) { return; }
			this.addAjaxLoader();
			this.$form.on( 'submit', { self : this }, this.handleFormSubmit );

		},


		setConsole : function() {

			if ( window.console === undefined || !this.display_debug_output ) {

				window.console = this.empty_console;

			}

		},


		init : function() {

			gwtoolset.setConsole();
			gwtoolset.addFormListener();
			gwtoolset.addSaveMappingButton();
			gwtoolset.addMetadataButtons();
			gwtoolset.addCategoryButtons();

		}


	};


	gwtoolset.init();


}( jQuery ));