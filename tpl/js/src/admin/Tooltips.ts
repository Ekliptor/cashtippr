import {CashTippr} from "../CashTippr";
import {AbstractModule} from "../AbstractModule";
import {WebHelpers} from "../WebHelpers";

export class Tooltips extends AbstractModule {

    constructor(cashtippr: CashTippr, webHelpers: WebHelpers) {
        super(cashtippr, webHelpers);
    }

    /**
     * Initializes status bar hover entries.
     */
    public initToolTips() {
        const window = this.cashtippr.window;
        const jQuery = this.cashtippr.$;
        const that = this;
        const tsfL10n = { // TODO localization
            states: {
                debug: false
            }
        }

        let touchBuffer = 0,
            inTouchBuffer = false;

        const setTouchBuffer = function() {
            inTouchBuffer = true;
            clearTimeout( touchBuffer );
            touchBuffer = setTimeout( function() {
                inTouchBuffer = false;
            }, 250 );
        }

        const setEvents = function( target, unset = false ) {

            unset = unset || false;

            let touchEvents = 'pointerdown.tsfTT touchstart.tsfTT click.tsfTT',
                $target = jQuery( target );

            if ( unset ) {
                $target.off( 'mousemove mouseleave mouseout ct-tooltip-update' );
                jQuery( document.body ).off( touchEvents );
            } else {
                $target.on( {
                    'mousemove'  : mouseMove,
                    'mouseleave' : mouseLeave,
                    'mouseout'   : mouseLeave,
                } );
                jQuery( document.body ).off( touchEvents ).on( touchEvents, touchRemove );
            }

            $target.on( 'ct-tooltip-update', updateDesc );
        }
        const unsetEvents = function( target ) {
            setEvents( target, true );
        }
        const updateDesc = function( event ) {
            if ( event.target.classList.contains( 'ct-tooltip-item' ) ) {
                let tooltipText = event.target.querySelector( '.ct-tooltip-text' );
                if ( tooltipText instanceof Element )
                    tooltipText.innerHTML = event.target.dataset.desc;
            }
        }
        const mouseEnter = function( event ) {
            let $hoverItem = jQuery( event.target ),
                desc = event.target.dataset.desc;

            if ( desc && 0 === $hoverItem.find( 'div' ).length ) {
                //= Remove any titles attached.
                event.target.title = "";

                let $tooltip = jQuery(
                    '<div class="ct-tooltip"><span class="ct-tooltip-text-wrap"><span class="ct-tooltip-text">'
                    + desc +
                    '</span></span><div class="ct-tooltip-arrow"></div></div>'
                );
                $hoverItem.append( $tooltip );

                let $boundary = $hoverItem.closest( '.ct-tooltip-boundary' );
                $boundary = $boundary.length && $boundary || jQuery( document.body );

                //= 9 = arrow (8) + shadow (1)
                let tooltipHeight = $hoverItem.outerHeight() + 9,
                    tooltipTop = $tooltip.offset().top - tooltipHeight,
                    boundaryTop = $boundary.offset().top - ( $boundary.prop( 'scrolltop' ) || 0 );

                if ( boundaryTop > tooltipTop ) {
                    $tooltip.addClass( 'ct-tooltip-down' );
                    $tooltip.css( 'top', tooltipHeight + 'px' );
                } else {
                    $tooltip.css( 'bottom', tooltipHeight + 'px' );
                }

                let $hoverItemWrap = $hoverItem.closest( '.ct-tooltip-wrap' ),
                    $textWrap = $tooltip.find( '.ct-tooltip-text-wrap' ),
                    $innerText = $textWrap.find( '.ct-tooltip-text' ),
                    hoverItemWrapWidth = $hoverItemWrap.width(),
                    textWrapWidth = $textWrap.outerWidth( true ),
                    textWidth = $innerText.outerWidth( true ),
                    textLeft = $textWrap.offset().left,
                    textRight = textLeft + textWidth,
                    boundaryLeft = $boundary.offset().left - ( $boundary.prop( 'scrollLeft' ) || 0 ),
                    boundaryRight = boundaryLeft + $boundary.outerWidth();

                //= RTL and LTR are normalized to abide to left.
                let direction = 'left';

                if ( textLeft < boundaryLeft ) {
                    //= Overflown over left boundary (likely window)
                    //= Add indent relative to boundary. 24px width of arrow / 2 = 12 middle
                    let horIndent = boundaryLeft - textLeft + 12,
                        basis = parseInt( $textWrap.css( 'flex-basis' ), 10 );

                    /**
                     * If the overflow is greater than the tooltip flex basis,
                     * the tooltip was grown. Shrink it back to basis and use that.
                     */
                    if ( horIndent < -basis )
                        horIndent = -basis;

                    $tooltip.css( direction, horIndent + 'px' );
                    $tooltip.data( 'overflow', horIndent );
                    $tooltip.data( 'overflowDir', direction );
                } else if ( textRight > boundaryRight ) {
                    //= Overflown over right boundary (likely window)
                    //= Add indent relative to boundary. Add 12px for visual appeal.
                    let horIndent = boundaryRight - textRight - hoverItemWrapWidth - 12,
                        basis = parseInt( $textWrap.css( 'flex-basis' ), 10 );

                    /**
                     * If the overflow is greater than the tooltip flex basis,
                     * the tooltip was grown. Shrink it back to basis and use that.
                     */
                    if ( horIndent < -basis )
                        horIndent = -basis;

                    $tooltip.css( direction, horIndent + 'px' );
                    $tooltip.data( 'overflow', horIndent );
                    $tooltip.data( 'overflowDir', direction );
                } else if ( hoverItemWrapWidth < 42 ) {
                    //= Small tooltip container. Add indent to make it visually appealing.
                    let indent = -15;
                    $tooltip.css( direction, indent + 'px' );
                    $tooltip.data( 'overflow', indent );
                    $tooltip.data( 'overflowDir', direction );
                } else if ( hoverItemWrapWidth > textWrapWidth ) {
                    //= Wrap is bigger than tooltip. Adjust accordingly.
                    let pagex = event.originalEvent && event.originalEvent.pageX || event.pageX, // iOS touch support,
                        hoverItemLeft = $hoverItemWrap.offset().left,
                        center = pagex - hoverItemLeft,
                        left = center - textWrapWidth / 2,
                        right = left + textWrapWidth;

                    if ( left < 0 ) {
                        //= Don't overflow left.
                        left = 0;
                    } else if ( right > hoverItemWrapWidth ) {
                        //= Don't overflow right.
                        //* Use textWidth instead of textWrapWidth as it gets squashed in flex.
                        left = hoverItemWrapWidth - textWidth;
                    }

                    $tooltip.css( direction, left + 'px' );
                    $tooltip.data( 'adjust', left );
                    $tooltip.data( 'adjustDir', direction );
                }
            }
        }
        const mouseMove = function( event ) {
            let $target = jQuery( event.target ),
                $tooltip = $target.find( '.ct-tooltip' ),
                $arrow = $tooltip.find( '.ct-tooltip-arrow' ),
                overflow = $tooltip.data( 'overflow' ),
                overflowDir = $tooltip.data( 'overflowDir' );

            overflow = parseInt( overflow, 10 );
            overflow = isNaN( overflow ) ? 0 : - Math.round( overflow );

            if ( overflow ) {
                //= Static arrow based on static overflow.
                $arrow.css( overflowDir, overflow + "px" );
            } else {
                let pagex = event.originalEvent && event.originalEvent.pageX || event.pageX, // iOS touch support
                    arrowBoundary = 7,
                    arrowWidth = 16,
                    $hoverItemWrap = $target.closest( '.ct-tooltip-wrap' ),
                    mousex = pagex - $hoverItemWrap.offset().left - arrowWidth / 2,
                    originalMousex = mousex,
                    $textWrap = $tooltip.find( '.ct-tooltip-text-wrap' ),
                    textWrapWidth = $textWrap.outerWidth( true ),
                    adjust = $tooltip.data( 'adjust' ),
                    adjustDir = $tooltip.data( 'adjustDir' ),
                    boundaryRight = textWrapWidth - arrowWidth - arrowBoundary;

                //= mousex is skewed, adjust.
                adjust = parseInt( adjust, 10 );
                adjust = isNaN( adjust ) ? 0 : Math.round( adjust );
                if ( adjust ) {
                    adjust = 'left' === adjustDir ? -adjust : adjust;
                    mousex = mousex + adjust;

                    //= Use textWidth for right boundary if adjustment exceeds.
                    if ( boundaryRight - adjust > $hoverItemWrap.outerWidth( true ) ) {
                        let $innerText = $textWrap.find( '.ct-tooltip-text' ),
                            textWidth = $innerText.outerWidth( true );
                        boundaryRight = textWidth - arrowWidth - arrowBoundary;
                    }
                }

                if ( mousex <= arrowBoundary ) {
                    //* Overflown left.
                    $arrow.css( 'left', arrowBoundary + "px" );
                } else if ( mousex >= boundaryRight ) {
                    //* Overflown right.
                    $arrow.css( 'left', boundaryRight + "px" );
                } else {
                    //= Somewhere in the middle.
                    $arrow.css( 'left', mousex + "px" );
                }
            }
        }
        const mouseLeave = function( event ) {

            //* @see touchMove
            if ( inTouchBuffer )
                return;

            jQuery( event.target ).find( '.ct-tooltip' ).remove();
            unsetEvents( event.target );
        }
        /**
         * ^^^
         * These two methods conflict eachother in EdgeHTML.
         * Thusly, touch buffer.
         * vvv
         */
        const touchRemove = function( event ) {

            //* @see mouseLeave
            setTouchBuffer();

            let itemSelector = '.ct-tooltip-item',
                balloonSelector = '.ct-tooltip';

            let $target = jQuery( event.target ),
                $keepBalloon;

            if ( $target.hasClass( 'ct-tooltip-item' ) ) {
                $keepBalloon = $target.find( balloonSelector );
            }
            if ( ! $keepBalloon ) {
                let $children = $target.children( itemSelector );
                if ( $children.length ) {
                    $keepBalloon = $children.find( balloonSelector );
                }
            }

            if ( $keepBalloon && $keepBalloon.length ) {
                //= Remove all but this.
                jQuery( balloonSelector ).not( $keepBalloon ).remove();
            } else {
                //= Remove all.
                jQuery( balloonSelector ).remove();
            }
        }

        /**
         * Loads tooltips within wrapper.
         * @function
         */
        const loadToolTip = function( event ) {

            if ( inTouchBuffer )
                return;

            let isTouch = false;

            switch ( event.type ) {
                case 'mouseenter' :
                    //= Most likely, thus placed first.
                    break;

                case 'pointerdown' :
                case 'touchstart' :
                    isTouch = true;
                    break;

                default :
                    break;
            }

            if ( event.target.classList.contains( 'ct-tooltip-item' ) ) {
                //= Removes previous items and sets buffer.
                isTouch && touchRemove( event );

                mouseEnter( event );
                //= Initiate placement directly for Windows Touch or when overflown.
                mouseMove( event );

                setEvents( event.target );
            } else {
                //= Delegate or bubble, and go back to this method with the correct item.
                let item = event.target.querySelector( '.ct-tooltip-item:hover' ),
                    _event = new jQuery.Event( event.type );

                _event.pageX = event.originalEvent && event.originalEvent.pageX || event.pageX;

                if ( item ) {
                    if ( tsfL10n.states.debug ) console.log( 'Tooltip event warning: delegation' );
                    jQuery( item ).trigger( _event );
                } else {
                    if ( tsfL10n.states.debug ) console.log( 'Tooltip event warning: bubbling' );
                    jQuery( event.target ).closest( '.ct-tooltip-wrap' ).find( '.ct-tooltip-item:hover' ).trigger( _event );
                }
            }

            //* Stop further propagation.
            event.stopPropagation();
        }

        /**
         * Initializes tooltips.
         * @function
         */
        const initTooltips = function() {
            let $wrap = jQuery( '.ct-tooltip-wrap' );

            $wrap.off( 'mouseenter pointerdown touchstart' );
            $wrap.on( 'mouseenter pointerdown touchstart', '.ct-tooltip-item', loadToolTip );
        }
        initTooltips();
        jQuery( window ).on( 'ct-reset-tooltips', initTooltips );

        (function() {
            let e = jQuery( '#wpcontent' );
            that.addTooltipBoundary( e );
        })();
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    /**
     * Adds tooltip boundaries.
     */
    protected addTooltipBoundary( e ) {
        jQuery( e ).addClass( 'ct-tooltip-boundary' );
    }

    /**
     * Triggers tooltip reset.
     */
    protected _triggerTooltipReset() { // not used yet
        jQuery( window ).trigger( 'ct-reset-tooltips' );
    }

    /**
     * Triggers active tooltip update.
     */
    protected _triggerTooltipUpdate(item) { // not used yet
        jQuery( item ).trigger( 'ct-tooltip-update' );
    }
}
