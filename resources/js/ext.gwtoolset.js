(function ( $ ) {
	'use strict';

	var gwtoolset,
		digitTest = /^\d+$/,
		keyBreaker = /([^\[\]]+)|(\[\])/g,
		messages = {
			backLink: mw.message( 'gwtoolset-back-link-option' ).escaped(),
			cancel: mw.message( 'gwtoolset-cancel' ).escaped(),
			developerIssue: mw.message( 'gwtoolset-developer-issue' ).escaped(),
			loading: mw.message( 'gwtoolset-loading' ).escaped(),
			save: mw.message( 'gwtoolset-save' ).escaped(),
			saveMapping: mw.message( 'gwtoolset-save-mapping' ).escaped(),
			saveMappingFailed: mw.message( 'gwtoolset-save-mapping-failed' ).escaped(),
			saveMappingName: mw.message( 'gwtoolset-save-mapping-name' ).escaped(),
			saveMappingSucceeded: mw.message( 'gwtoolset-save-mapping-succeeded' ).escaped(),
			step2Heading: mw.message( 'gwtoolset-step-2-heading' ).escaped()
		},
		paramTest = /([^?#]*)(#.*)?$/,
		plus = /\+/g;

	/**
	 * @add jQuery.String
	 * @author justinbmeyer
	 * @see https://github.com/jupiterjs/jquerymx/blob/master/lang/string/deparam/deparam.js
	 */
	$.String = $.extend( $.String || {}, {
		/**
		 * @function deparam
		 *
		 * Takes a string of name value pairs and returns a Object literal that represents those params.
		 *
		 * @param {string} params a string like <code>"foo=bar&person[age]=3"</code>
		 * @return {Object} A JavaScript Object that represents the params:
		 *
		 *     {
		 *       foo: "bar",
		 *       person: {
		 *         age: "3"
		 *       }
		 *     }
		 */
		deparam: function ( params ) {
			if ( !params || !paramTest.test( params ) ) {
				return {};
			}

			var current,
				i,
				j,
				key,
				lastPart,
				pair,
				part,
				parts,
				value,
				data = {},
				pairs = params.split( '&' );

			for ( i = 0; i < pairs.length; i++ ) {
				current = data;
				pair = pairs[i].split( '=' );

				// if we find foo=1+1=2
				if ( pair.length !== 2 ) {
					pair = [ pair[0], pair.slice( 1 ).join( '=' ) ];
				}

				key = decodeURIComponent( pair[0].replace( plus, ' ' ) );
				value = decodeURIComponent( pair[1].replace( plus, ' ' ) );
				parts = key.match( keyBreaker );

				for ( j = 0; j < parts.length - 1; j++ ) {
					part = parts[ j ];

					if ( !current[ part ] ) {
						// if what we are pointing to looks like an array
						current[ part ] = digitTest.test( parts[ j + 1 ] ) || parts[ j + 1 ] === '[]' ? [] : {};
					}

					current = current[ part ];
				}

				lastPart = parts[ parts.length - 1 ];

				if ( lastPart === '[]' ) {
					current.push( value );
				} else {
					current[lastPart] = value;
				}
			}

			return data;
		}
	} );

	gwtoolset = {

		displayDebugOutput: true,
		$dialog: $( '<div>' )
			.attr( 'id', 'dialog' )
			.dialog( {
				autoOpen: false,
				modal: true,
				resizable: false,
				close: function () {
					gwtoolset.closeDialog();
				}
			} ),
		emptyConsole: {
			log: function () {
			}
		},
		$form: $( '#gwtoolset-form' ),
		$ajaxLoader: $( '<div>' )
			.attr( 'id', 'gwtoolset-loader' )
			.html(
				$( '<p>' )
					.append( $.createSpinner( { size: 'large', type: 'block' } ) )
					.append( messages.loading )
			),
		$templateTableTbody: $( '#template-table > tbody' ),
		$saveMappingButton: $( '<tr>' )
			.html(
				$( '<td>' )
					.attr( 'colspan', 3 )
					.css( 'text-align', 'right' )
					.html(
						$( '<span>' )
							.attr( {
								id: 'save-mapping',
								title: messages.saveMapping
							} )
							.text( messages.saveMapping )
					)
			),
		$buttons: {
			$add: $( '<img>' )
				.attr( 'src', mw.config.get('wgExtensionAssetsPath') + '/GWToolset/resources/images/b_snewtbl.png' )
				.addClass( 'gwtoolset-metadata-button' ),
			$subtract: $( '<img>' )
				.attr( 'src', mw.config.get('wgExtensionAssetsPath') + '/GWToolset/resources/images/b_drop.png' )
				.addClass( 'gwtoolset-metadata-button' )
		},
		$backText: $( '#back-text' ),
		$step2Link: $( '#step2-link' ),

		addAjaxLoader: function () {
			this.$ajaxLoader.hide();
			this.$form.prepend( this.$ajaxLoader );
		},

		addBackLinks: function () {
			this.$backText.replaceWith( this.createBackLink( { title: messages.backLink } ) );
			this.$step2Link.replaceWith( this.createBackLink( { title: messages.step2Heading } ) );
		},

		addButtons: function () {
			$( '.button-add' ).html( this.$buttons.$add.clone().on( 'click', this.handleButtonAddClick ) );
			$( '.button-subtract' ).html( this.$buttons.$subtract.clone().on( 'click', this.handleButtonSubtractClick ) );
		},

		addFormListener: function () {
			if ( this.$form.length < 1 ) {
				return;
			}

			this.addAjaxLoader();
			this.$form.on( 'submit', this.handleFormSubmit );
		},

		addSaveMappingButton: function () {
			if ( this.$templateTableTbody.length === 1 ) {
				this.$templateTableTbody.append( this.$saveMappingButton );
				this.$saveMappingButton.on( 'click', this.handleSaveMappingClick );
			}
		},

		closeDialog: function () {
			gwtoolset.$dialog.dialog( {
				buttons: null,
				dialogClass: null,
				title: null
			} );

			$( document ).off( 'keyup', gwtoolset.handleDialogKeyUp );
		},

		/**
		 * creates an <a> link that will take the user back one page in the browser history
		 * the method uses options.title for the link title and text, yet also allows
		 * you to specify unique values if desired
		 *
		 * @param {Object} options
		 *
		 */
		createBackLink: function ( options ) {
			return $( '<a>' )
				.attr( 'href', '#' )
				.attr( 'title', options.title || '' )
				.text( options.text || options.title || '' )
				.on( 'click', function ( evt ) {
					evt.preventDefault();
					history.back();
				} );
		},

		handleAjaxError: function () {
			gwtoolset.openDialog( { msg: messages.developerIssue } );
			console.log( arguments );
		},

		/**
		 * @param {Object} data
		 * @param {string} textStatus
		 * @param {Object} jqXHR
		 */
		handleAjaxSuccess: function ( data, textStatus, jqXHR ) {
			if ( data.ok !== true || !textStatus || !jqXHR ) {
				gwtoolset.openDialog( { msg: messages.saveMappingFailed } );
			} else {
				gwtoolset.openDialog( { msg: messages.saveMappingSucceeded } );
			}
		},

		/**
		 * @param {Event} evt
		 */
		handleButtonAddClick: function ( evt ) {
			var $target = $( this ).closest( 'tr' ),
				$tdButton = $( '<td>' )
					.addClass( 'button-subtract' )
					.html( gwtoolset.$buttons.$subtract.clone().on( 'click', gwtoolset.handleButtonSubtractClick ) ),
				$row = $( '<tr>' );

			evt.preventDefault();

			$target.children().each( function () {
				var $tdElm,
				$elm = $( this );

				if ( $elm.find('label').length === 1 ) {
					$row.append( $( '<td>' ) );
				} else if ( $elm.hasClass( 'button-add' ) ) {
					$row.append( $tdButton );
				} else {
					$tdElm = $elm.clone();
					$tdElm.find( 'input' ).val( '' );
					$tdElm.find( 'option' ).prop( 'selected', false );
					$row.append( $tdElm );
				}
			});

			$row.insertAfter( $target );
		},

		/**
		 * @param {Event} evt
		 */
		handleButtonSubtractClick: function ( evt ) {
			evt.preventDefault();
			$( this ).closest( 'tr' ).remove();
		},

		/**
		 * @param {Event} evt
		 */
		handleDialogKeyUp: function ( evt ) {
			var buttons = evt.data.buttons;

			if ( evt.keyCode === 13 ) {
				buttons[0].click.apply( dialog );
			}
		},

		handleFormSubmit: function () {
			gwtoolset.$ajaxLoader.fadeIn();
		},

		/**
		 * @param {Event} evt
		 */
		handleSaveMappingClick: function ( evt ) {
			var buttons,
				$input = $( '<input>' )
					.attr( {
						id: 'mapping-name-to-use',
						value: $( '#metadata-mapping-name' ).val()
					} );

			evt.preventDefault();

			gwtoolset.openDialog( {
				options: {
					title: messages.saveMappingName,
					dialogClass: 'no-close',
					buttons : [
						{
							text: messages.save,
							click: function () {
								$( this ).dialog( 'close' );
								gwtoolset.saveMapping();
							}
						},
						{
							text: messages.cancel,
							click: function () {
								$( this ).dialog( 'close' );
							}
						}
					]
				},
				msg: $input
			} );

			buttons = gwtoolset.$dialog.dialog( 'option', 'buttons' );
			$( document ).on( 'keyup', { buttons: buttons }, gwtoolset.handleDialogKeyUp );
		},

		init: function () {
			gwtoolset.setConsole();
			gwtoolset.addBackLinks();
			gwtoolset.addFormListener();
			gwtoolset.addSaveMappingButton();
			gwtoolset.addButtons();
		},

		/**
		 * @param {Object} options
		 */
		openDialog: function ( options ) {
			gwtoolset.$dialog.html( options.msg );

			if ( options.options !== undefined ) {
				gwtoolset.$dialog.dialog( options.options );
			}

			gwtoolset.$dialog.dialog( 'open' );
		},

		/**
		 * sends the appropriate data to the server for the mapping to be created/updated
		 */
		saveMapping: function () {
			var mappingNameToUse = $( '#mapping-name-to-use' ).val(),
				mediawikiTemplateName = $( '#mediawiki-template-name' ).val(),
				wpEditToken = mw.user.tokens.get( 'editToken' ),
				metadataMappings = gwtoolset.$form.find( 'select' ).serialize();

			metadataMappings = $.String.deparam( metadataMappings );

			if ( mappingNameToUse === null || mappingNameToUse.length < 3 ) {
				return;
			}

			$.ajax( {
				type: 'POST',
				url: mw.util.wikiGetlink( 'Special:GWToolset' ),
				data: {
					'gwtoolset-form': 'metadata-mapping-save',
					'mapping-name-to-use': mappingNameToUse,
					'metadata-mappings': metadataMappings,
					'mediawiki-template-name': mediawikiTemplateName,
					'wpEditToken': wpEditToken
				},
				error: gwtoolset.handleAjaxError,
				success: gwtoolset.handleAjaxSuccess,
				complete: gwtoolset.handleAjaxComplete,
				timeout: 5000
			} );
		},

		setConsole: function () {
			if ( window.console === undefined || !this.displayDebugOutput ) {
				window.console = this.emptyConsole;
			}
		}

	};

	gwtoolset.init();

}( jQuery ));
