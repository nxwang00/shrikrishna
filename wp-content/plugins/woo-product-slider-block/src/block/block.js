/**
 * BLOCK: woo--product-slider-block
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */
import classnames from 'classnames';

//  Import CSS.
import './style.scss';
import './editor.scss';
import './hoverEffects.scss';

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks

import edit from './edit';

/**
 * Register: aa Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */
registerBlockType( 'eedee/woo-product-slider-block', {
	// Block name. Block names must be string that contains a namespace prefix. Example: my-plugin/my-custom-block.
	title: __( 'Woo Product Slider' ), // Block title.
	icon: 'shield', // Block icon from Dashicons → https://developer.wordpress.org/resource/dashicons/.
	category: 'common', // Block category — Group blocks together based on common traits E.g. common, formatting, layout widgets, embed.
	keywords: [
		__( 'Product' ),
		__( 'Woocommerce' ),
		__( 'Slider' ),
	],
	supports: {
		align: [ 'wide', 'full' ],
	},

	attributes: {
		products: {
			type: 'array',
			default: [],
		},
		sliderStyle: {
			type: 'string',
			default: 'duke',
		},
		orderBy: {
			type: 'string',
			default: 'date',
		},
		count: {
			type: 'number',
			default: 5,
		},
	},

	edit,

	save: function( props ) {
		const { className, attributes } = props;
		const {
			sliderStyle,
			orderBy,
			count,
		} = attributes;

		const classes = classnames( {
			[ `${ className }` ]: true,
			[ `effect-${ sliderStyle }` ]: true,
		} );

		return (
			<div
				className={ classes }
				data-align={ props.attributes.align }
				data-order-by={ orderBy }
				data-count={ count }
			>
			</div>
		);
	},
} );
