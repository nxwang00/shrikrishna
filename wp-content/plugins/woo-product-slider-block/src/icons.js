const CheckIcon = props => (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" { ...props }>
		<rect x="0" fill="none" width="20" height="20" />
		<g>
			<path d="M14.83 4.89l1.34.94-5.81 8.38H9.02L5.78 9.67l1.34-1.25 2.57 2.4z" />
		</g>
	</svg>
);

const CartIcon = props => (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" { ...props }>
		<rect x="0" fill="none" width="20" height="20" />
		<g>
			<path d="M6 13h9c.55 0 1 .45 1 1s-.45 1-1 1H5c-.55 0-1-.45-1-1V4H2c-.55 0-1-.45-1-1s.45-1 1-1h3c.55 0 1 .45 1 1v2h13l-4 7H6v1zm-.5 3c.83 0 1.5.67 1.5 1.5S6.33 19 5.5 19 4 18.33 4 17.5 4.67 16 5.5 16zm9 0c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5-1.5-.67-1.5-1.5.67-1.5 1.5-1.5z" />
		</g>
	</svg>
);

const IconCartPlus = props => (
	<svg ariaHidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" { ...props }>
		<path fill="currentColor" d="M504.717 320H211.572l6.545 32h268.418c15.401 0 26.816 14.301 23.403 29.319l-5.517 24.276C523.112 414.668 536 433.828 536 456c0 31.202-25.519 56.444-56.824 55.994-29.823-.429-54.35-24.631-55.155-54.447-.44-16.287 6.085-31.049 16.803-41.548H231.176C241.553 426.165 248 440.326 248 456c0 31.813-26.528 57.431-58.67 55.938-28.54-1.325-51.751-24.385-53.251-52.917-1.158-22.034 10.436-41.455 28.051-51.586L93.883 64H24C10.745 64 0 53.255 0 40V24C0 10.745 10.745 0 24 0h102.529c11.401 0 21.228 8.021 23.513 19.19L159.208 64H551.99c15.401 0 26.816 14.301 23.403 29.319l-47.273 208C525.637 312.246 515.923 320 504.717 320zM408 168h-48v-40c0-8.837-7.163-16-16-16h-16c-8.837 0-16 7.163-16 16v40h-48c-8.837 0-16 7.163-16 16v16c0 8.837 7.163 16 16 16h48v40c0 8.837 7.163 16 16 16h16c8.837 0 16-7.163 16-16v-40h48c8.837 0 16-7.163 16-16v-16c0-8.837-7.163-16-16-16z">
		</path>
	</svg>
);

// const ShoppingCartPlus = ( { size } ) => (
// 	<svg xmlns="http://www.w3.org/2000/svg" width={ size } height={ size } viewBox={ `0 0 ${ size } ${ size }` } fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
// 		<rect id="backgroundrect" width="100%" height="100%" x="0" y="0" fill="none" stroke="none" className="" />
// 		<g className="currentLayer" >
// 			<circle cx="9" cy="21" r="1" id="svg_1" />
// 			<circle cx="20" cy="21" r="1" id="svg_2" />
// 			<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" id="svg_3" />
// 			<path fill="#000" stroke="#000000" strokeWidth="0.5" strokeDashoffset="" fillRule="nonzero" id="svg_4" d="M11.871522162424545,10.003838593665943 L13.571184877533994,10.003838593665943 L13.571184877533994,8.304175706377583 L15.314716186887598,8.304175706377583 L15.314716186887598,10.003838593665943 L17.01437907417595,10.003838593665943 L17.01437907417595,11.747370075198432 L15.314716186887598,11.747370075198432 L15.314716186887598,13.447032790307901 L13.571184877533994,13.447032790307901 L13.571184877533994,11.747370075198432 L11.871522162424545,11.747370075198432 L11.871522162424545,10.003838593665943 z" />
// 		</g>
// 	</svg>
// );

const ShoppingCartPlus = props => {
	const { color, size, ...otherProps } = props;
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width={ size }
			height={ size }
			viewBox="0 0 24 24"
			fill="none"
			stroke={ color }
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
			{ ...otherProps }
		>
			<circle cx="9" cy="21" r="1" />
			<circle cx="20" cy="21" r="1" />
			<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
			<path fill={ color } strokeWidth="0.5" strokeDashoffset="" fillRule="nonzero" id="svg_4" d="M11.871522162424545,10.003838593665943 L13.571184877533994,10.003838593665943 L13.571184877533994,8.304175706377583 L15.314716186887598,8.304175706377583 L15.314716186887598,10.003838593665943 L17.01437907417595,10.003838593665943 L17.01437907417595,11.747370075198432 L15.314716186887598,11.747370075198432 L15.314716186887598,13.447032790307901 L13.571184877533994,13.447032790307901 L13.571184877533994,11.747370075198432 L11.871522162424545,11.747370075198432 L11.871522162424545,10.003838593665943 z" />
		</svg>
	);
};

ShoppingCartPlus.defaultProps = {
	color: 'currentColor',
	size: '24',
};

export { CheckIcon, CartIcon, IconCartPlus, ShoppingCartPlus };
