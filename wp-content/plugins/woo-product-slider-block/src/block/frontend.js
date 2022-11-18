import ProductSlider from '../components/ProductSlider';

const { render } = wp.element;

const sliders = document.querySelectorAll( '.wp-block-eedee-woo-product-slider-block' );

sliders.forEach( ( slider ) => {
	const align = slider.dataset.align;
	const orderBy = slider.dataset.orderBy;
	const count = slider.dataset.count;

	const domSlides = slider.querySelectorAll( '.slide' );
	const slides = [];

	domSlides.forEach( slide => {
		slides.push( {
			productId: slide.dataset.productId,
			title: slide.dataset.title,
		} );
	} );

	render(
		<div className="slider-wrap">
			<ProductSlider
				align={ align }
				count={ count }
				orderBy={ orderBy }
			/>
		</div>,
		slider
	);
} );
