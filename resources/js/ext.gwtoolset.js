(function($) {

	'use strict';


	var gwtoolset = {

		display_debug_output : true,
		empty_console : { log : function() {} },
		$form : $('#gwtoolset-form'),
		$ajax_loader : $( '<div/>', { 'class':'gwtoolset-loader' }),
		$template_table : $('#template-table > tbody'),
		$save_mapping_button : $('<tr><td colspan="3" style="text-align:right;"><button id="save-mapping">' + mw.message('gwtoolset-save-mapping').escaped() + '</button></td></tr>'),


		/**
		 * @todo use the translate version of the message
		 * @todo message should be placed in dom in an ajax message area instead
		 * of using an alert
		 */
		handleAjaxError : function() {
			
			alert( mw.message('gwtoolset-save-mapping-error').escaped() );
			console.log( arguments );
			
		},


		/**
		 * @todo use the translate version of the message
		 * @todo message should be placed in dom in an ajax message area instead
		 * of using an alert
		 *
		 * @param {object} data
		 * @param {string} textStatus
		 * @param {object} jqXHR
		 */
		handleAjaxSuccess : function ( data, textStatus, jqXHR ) {

			if ( !data.status || data.status != 'success' ) {
				alert( mw.message('gwtoolset-save-mapping-error').escaped() );
			} else {
				alert( mw.message('gwtoolset-save-mapping-success').escaped() );
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
		saveMapping : function( mapping_name_to_use, mediawiki_template, wpEditToken, metadata_mappings ) {

			var self = this;

			$.ajax({
				type : 'POST',
				url : mw.config.get('wgArticlePath').replace("$1", "") + 'Special:GWToolset',
				data : {
					'gwtoolset-form' : 'metadata-mapping-save',
					'mapping-name-to-use' : mapping_name_to_use,
					'mediawiki-template' : mediawiki_template,
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
				mediawiki_template = $('#gwtoolset-mediawiki-template').val(),
				wpEditToken = $('#wpEditToken').val(),
				metadata_mappings = self.$form.find('select').serializeArray();

			evt.preventDefault();

			if ( mapping_name_to_use.length > 3 ) {

				self.saveMapping( mapping_name_to_use, mediawiki_template, wpEditToken, metadata_mappings );

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
				.append('<img src="/skins/common/images/ajax-loader.gif"/><br/>')
				.append( mw.msg('gwtoolset-loading') );
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

			this.setConsole();
			this.addFormListener();
			this.addSaveMappingButton();

		}


	}


	gwtoolset.init();


}( jQuery ));

