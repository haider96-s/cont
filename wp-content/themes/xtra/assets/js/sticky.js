jQuery( function( $ ) {
	"use strict";

	if ( $( '.header_is_sticky' ).length ) {

		var body = $( document.body ),
			inla = $( '.inner_layout' ),
			abar = $( '.admin-bar' ).length ? 32 : 0;

		/* Sticky */
		setTimeout( function() {

			$( '.header_is_sticky' ).each( function( n, x ) {

				var header_sticky 	= $( x ),
					header_5 		= $( '.header_5' ),
					fixed_side 		= $( '.fixed_side_left' ).length ? 'right' : $( '.fixed_side_left' ).length ? 'left' : '',
					lastScrollTop 	= 0, 
					st, sticky_func,
					h1h = 0,
					h2h = 0,
					h3h = 0,
					scrollTop = header_sticky.offset().top,
					h_offset = header_sticky.position(),
					sidebar = $( '.cz_sticky .sidebar_primary > div, .cz_sticky .row > aside > div, .cz_sticky_col:not(.images) > div, .column-xtra-sticky > div' ),
					smart_sticky = function( scroll ) {

						if ( header_sticky.hasClass( 'smart_sticky' ) || ( $( '.cz_fixed_footer' ).length && $( '.page_content' ).length && ( $( '.page_content' ).offset().top + $( '.page_content' ).height() <= $( window ).scrollTop() + header_sticky.height() ) ) ) {

							st = scroll.scrollTop();

							var oHeight = header_sticky.outerHeight();

							if ( st > ( h_offset.top + oHeight + 100 ) && st > lastScrollTop ) {

								sidebar.css( 'margin-top', '-' + oHeight + 'px' );
								header_sticky.css( 'transform', 'translateY(-' + ( oHeight + 20 ) + 'px)' );

							} else if ( header_sticky.hasClass( 'header_5' ) && st < 200 ) {

								header_sticky.css( 'transform', '' );

							} else if ( st < lastScrollTop ) {

								sidebar.css( 'margin-top', '' );
								header_sticky.css( 'transform', 'none' );

							}

							lastScrollTop = st;

						}
						
					};

				if ( header_sticky.hasClass( 'header_5' ) ) {

					header_5.addClass( 'onSticky' );

					$( window ).on( 'scroll.sticky', function(e) {

						var ph = header_5.height(),
							il = inla.width();

						// Fixed side compatible.
						if ( fixed_side ) {
							header_sticky.css( fixed_side, '0' );
						}

						if ( $( window ).scrollTop() >= ph ) {
							header_5.css( 'transform', 'none' ).css( 'width', il + 'px' );
						} else {
							header_5.css( 'transform', 'translateY(-' + ( ph + 20 ) + 'px)' ).css( 'width', il + 'px' );
						}

						smart_sticky( $( this ) );

					});

				} else if ( header_sticky.length ) {

					/* Add corpse */
					if ( ! header_sticky.prev( '.Corpse_Sticky').length ) {
						header_sticky.before( '<div class="Corpse_Sticky' + ( header_sticky.hasClass( 'header_4' ) ? ' cz_sticky_corpse_for_header_4' : '' ) + '" aria-hidden="true"></div>' );
					}

					var scroll_down,
						scroll_top,
						new_scrollTop,
						cz_sticky_h12 = $( '.cz_sticky_h12' ).length,
						cz_sticky_h13 = $( '.cz_sticky_h13' ).length,
						cz_sticky_h23 = $( '.cz_sticky_h23' ).length,
						cz_sticky_h123 = $( '.cz_sticky_h123' ).length;

					sticky_func = function(e) {

						if ( header_sticky.hasClass( 'header_4' ) && header_sticky.css( 'display' ) == 'none' ) {
							return;
						}

						new_scrollTop = scrollTop;

						if ( $( '.header_1' ).length ) {
							h1h = $( '.header_1' ).outerHeight();
						}
						if ( $( '.header_2' ).length ) {
							h2h = $( '.header_2' ).outerHeight();
						}
						if ( $( '.header_3' ).length ) {
							h3h = $( '.header_3' ).outerHeight();
						}

						if ( cz_sticky_h12 && header_sticky.hasClass( 'header_2' ) ) {
							new_scrollTop = scrollTop + 1 - h1h;
						} else if ( cz_sticky_h13 && header_sticky.hasClass( 'header_3' ) ) {
							new_scrollTop = scrollTop + 1 - h1h;
						} else if ( cz_sticky_h23 && header_sticky.hasClass( 'header_3' ) ) {
							new_scrollTop = scrollTop + 1 - h2h;
						} else if ( cz_sticky_h123 ) {

							if ( header_sticky.hasClass( 'header_2' ) ) {
								new_scrollTop = scrollTop + 1 - h1h;
							}

							if ( header_sticky.hasClass( 'header_3' ) ) {
								new_scrollTop = scrollTop + 1 - ( h1h + h2h );
							}

						}

						abar = $( '.xtra-preview-header' ).is( ':visible' ) ? 54 : abar;

						scroll_top = $( window ).scrollTop() + abar;

						if ( scroll_top === abar && ( body.hasClass( 'admin-bar' ) || body.hasClass( 'compose-mode' ) ) ) {
							scroll_top = -abar;
						}

						scroll_down = scroll_top > new_scrollTop;

						// Fixed side compatible.
						if ( fixed_side ) {
							header_sticky.css( fixed_side, '0' );
						}

						// Sticky width.
						var il = inla.width();

						smart_sticky( $( this ) );
						header_sticky.css( 'width', il + 'px' );

						if ( scroll_down ) {
							header_sticky.prev( '.Corpse_Sticky' ).css({
								'height': header_sticky.outerHeight() + 'px'
							});
							header_sticky.delay( 100 ).addClass( 'onSticky' );
						} else {

							header_sticky.css( 'marginTop', '' ).removeClass( 'onSticky' ).prev( '.Corpse_Sticky').css({
								'height': 'auto'
							});

							header_sticky.css( 'width', '' );

						}

						if ( scroll_down && cz_sticky_h12 && header_sticky.hasClass( 'header_2' ) ) {
							$( '.header_2' ).css( 'marginTop', h1h );
						} else if ( scroll_down && cz_sticky_h13 && header_sticky.hasClass( 'header_3' ) ) {
							$( '.header_3' ).css( 'marginTop', h1h );
						} else if ( scroll_down && cz_sticky_h23 && header_sticky.hasClass( 'header_3' ) ) {
							$( '.header_3' ).css( 'marginTop', h2h );
						} else if ( cz_sticky_h123 ) {
							if ( scroll_down && header_sticky.hasClass( 'header_2' ) ) {
								$( '.header_2' ).css( 'marginTop', h1h );
							}
							if ( scroll_down && header_sticky.hasClass( 'header_3' ) ) {
								$( '.header_3' ).css( 'marginTop', ( h1h + h2h ) );
							}
						}
					};

					$( window ).off( 'scroll.cz_sticky_' + n ).on( 'scroll.cz_sticky_' + n, sticky_func );
					$( window ).off( 'resize.cz_sticky_' + n ).on( 'resize.cz_sticky_' + n, sticky_func );
				}

			});

		}, 750 );

	}

});