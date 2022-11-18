/* global eedeeWooProductSlider */

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { compose } = wp.compose;
const { withSelect,	withDispatch } = wp.data;
const { Component, Fragment } = wp.element;
const {
} = wp.blocks;
const {
	InspectorControls,
} = wp.editor;
const {
	withNotices,
	PanelBody,
	SelectControl,
	RangeControl,
} = wp.components;

import ProductSlider from '../components/ProductSlider';

const PRODUCT_ORDER_BY = [
	{ value: 'date', label: __( 'Date' ) },
	{ value: 'popularity', label: __( 'Popularity' ) },
	{ value: 'rating', label: __( 'Rating' ) },
];

const SLIDER_STYLES = [
	{ value: 'duke', label: __( 'Style 1 (Duke)' ) },
	{ value: 'zoe', label: __( 'Style 2 (Zoe)' ) },
	{ value: 'steve', label: __( 'Style 3 (Steve)' ) },
];

class EedeeWooProductSlider extends Component {
	constructor() {
		super( ...arguments );

		this.setAttributes = this.setAttributes.bind( this );

		this.getControls = this.getControls.bind( this );
		this.getInspectorControls = this.getInspectorControls.bind( this );
		this.getSliderContent = this.getSliderContent.bind( this );

		this.setSliderStyle = this.setSliderStyle.bind( this );
		this.setOrderBy = this.setOrderBy.bind( this );
		this.setCount = this.setCount.bind( this );

		this.state = {
			loaded: false,
		};
	}

	componentDidMount() {
		// this.getProducts();
	}

	setAttributes( attributes ) {
		this.props.setAttributes( attributes );
	}

	setSliderStyle( sliderStyle ) {
		this.setAttributes( { sliderStyle } );
	}

	setOrderBy( orderBy ) {
		this.setAttributes( { orderBy } );
	}

	setCount( count ) {
		this.setAttributes( { count } );
	}

	getInspectorControls() {
		const {
			sliderStyle,
			orderBy,
			count,
		} = this.props.attributes;

		return (
			<InspectorControls >
				<PanelBody
					title={ __( 'Product Selection' ) }
					className="ewps-controls-products"
				>
					<RangeControl
						label={ __( 'Number of Products' ) }
						value={ count }
						onChange={ this.setCount }
						min={ 0 }
						max={ 10 }
						step={ 1 }
					/>
					<SelectControl
						label={ __( 'Order Products By' ) }
						value={ orderBy }
						options={ PRODUCT_ORDER_BY }
						onChange={ this.setOrderBy }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Slider Themes' ) }
					className="ewps-controls-theme"
				>
					<SelectControl
						label={ __( 'Slider Theme' ) }
						value={ sliderStyle }
						options={ SLIDER_STYLES }
						onChange={ this.setSliderStyle }
					/>
				</PanelBody>
			</InspectorControls>
		);
	}

	getControls() {

	}

	getSliderContent() {
		const {
			align,
			orderBy,
			count,
		} = this.props.attributes;

		return (
			<ProductSlider
				align={ align }
				orderBy={ orderBy }
				count={ count }
				disabled
			/>
		);
	}

	render() {
		const { className, noticeUI, attributes } = this.props;
		const { sliderStyle } = attributes;

		const classes = classnames(
			'has-dots',
			{
				[ `${ className }` ]: true,
				[ `effect-${ sliderStyle }` ]: true,
			}
		);

		if ( ! eedeeWooProductSlider.isWooActive ) {
			return (
				<div className={ `${ classes } woo-not-found` }>
					Woo Commerce Not Found. <a href={ `${ eedeeWooProductSlider.adminPluginsUrl }?s=woocommerce&tab=search&type=term` }>Please install it to use Woo Product Slider.</a>
				</div>
			);
		}

		return (
			<Fragment>
				{ this.getControls() }
				{ this.getInspectorControls() }
				{ noticeUI }

				<div className={ classes }>
					{ this.getSliderContent() }
				</div>
			</Fragment>
		);
	}
}

export default compose( [
	withDispatch( ( ) => {
		return {};
	} ),
	withSelect( ( ) => {
		return {};
	} ),
	withNotices,
] )( EedeeWooProductSlider );
