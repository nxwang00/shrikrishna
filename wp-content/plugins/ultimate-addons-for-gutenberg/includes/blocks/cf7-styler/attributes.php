<?php
/**
 * Attributes File.
 *
 * @since 2.0.0
 *
 * @package uagb
 */

$field_border_attribute  = UAGB_Block_Helper::uag_generate_border_attribute( 'input' );
$button_border_attribute = UAGB_Block_Helper::uag_generate_border_attribute( 'btn' );

return array_merge(
	array(
		'block_id'                         => '',
		'align'                            => 'left',
		'formId'                           => '0',
		'fieldStyle'                       => 'box',
		'fieldHrPadding'                   => 10,
		'fieldVrPadding'                   => 10,
		'fieldBgColor'                     => '#fafafa',
		'fieldLabelColor'                  => '#333',
		'fieldInputColor'                  => '#333',
		'buttonAlignment'                  => 'left',
		'buttonAlignmentTablet'            => '',
		'buttonAlignmentMobile'            => '',
		'buttonVrPadding'                  => 10,
		'buttonHrPadding'                  => 25,
		'buttonTextColor'                  => '#333',
		'buttonBgColor'                    => '',
		'buttonTextHoverColor'             => '',
		'buttonBgHoverColor'               => '',
		'fieldSpacing'                     => '',
		'fieldSpacingTablet'               => '',
		'fieldSpacingMobile'               => '',
		'fieldLabelSpacing'                => '',
		'fieldLabelSpacingMobile'          => '',
		'fieldLabelSpacingTablet'          => '',
		'labelFontSize'                    => '',
		'labelFontSizeType'                => 'px',
		'labelFontSizeTablet'              => '',
		'labelFontSizeMobile'              => '',
		'labelFontFamily'                  => '',
		'labelFontWeight'                  => '',
		'labelFontStyle'                   => '',
		'labelTransform'                   => '',
		'labelDecoration'                  => '',
		'labelLineHeightType'              => 'px',
		'labelLineHeight'                  => '',
		'labelLineHeightTablet'            => '',
		'labelLineHeightMobile'            => '',
		'labelLoadGoogleFonts'             => false,
		'inputFontSize'                    => '',
		'inputFontSizeType'                => 'px',
		'inputFontSizeTablet'              => '',
		'inputFontSizeMobile'              => '',
		'inputFontFamily'                  => '',
		'inputFontWeight'                  => '',
		'inputFontStyle'                   => '',
		'inputTransform'                   => '',
		'inputDecoration'                  => '',
		'inputLineHeightType'              => 'px',
		'inputLineHeight'                  => '',
		'inputLineHeightTablet'            => '',
		'inputLineHeightMobile'            => '',
		'inputLoadGoogleFonts'             => false,
		'buttonFontSize'                   => '',
		'buttonFontSizeType'               => 'px',
		'buttonFontSizeTablet'             => '',
		'buttonFontSizeMobile'             => '',
		'buttonFontFamily'                 => '',
		'buttonFontWeight'                 => '',
		'buttonFontStyle'                  => '',
		'buttonTransform'                  => '',
		'buttonDecoration'                 => '',
		'buttonLineHeightType'             => 'px',
		'buttonLineHeight'                 => '',
		'buttonLineHeightTablet'           => '',
		'buttonLineHeightMobile'           => '',
		'buttonLoadGoogleFonts'            => false,
		'enableOveride'                    => true,
		'radioCheckSize'                   => '',
		'radioCheckSizeTablet'             => '',
		'radioCheckSizeMobile'             => '',
		'radioCheckBgColor'                => '',
		'radioCheckSelectColor'            => '',
		'radioCheckLableColor'             => '',
		'radioCheckBorderColor'            => '#abb8c3',
		'radioCheckBorderWidth'            => '',
		'radioCheckBorderWidthTablet'      => '',
		'radioCheckBorderWidthMobile'      => '',
		'radioCheckBorderWidthUnit'        => 'px',
		'radioCheckBorderRadius'           => '',
		'radioCheckFontSize'               => '',
		'radioCheckFontSizeType'           => 'px',
		'radioCheckFontSizeTablet'         => '',
		'radioCheckFontSizeMobile'         => '',
		'radioCheckFontFamily'             => '',
		'radioCheckFontWeight'             => '',
		'radioCheckFontStyle'              => '',
		'radioCheckTransform'              => '',
		'radioCheckDecoration'             => '',
		'radioCheckLineHeightType'         => 'px',
		'radioCheckLineHeight'             => '',
		'radioCheckLineHeightTablet'       => '',
		'radioCheckLineHeightMobile'       => '',
		'radioCheckLoadGoogleFonts'        => false,
		'validationMsgPosition'            => 'default',
		'validationMsgColor'               => '#ff0000',
		'validationMsgBgColor'             => '',
		'enableHighlightBorder'            => false,
		'highlightBorderColor'             => '#ff0000',
		'validationMsgFontSize'            => '',
		'validationMsgFontSizeType'        => 'px',
		'validationMsgFontSizeTablet'      => '',
		'validationMsgFontSizeMobile'      => '',
		'validationMsgFontFamily'          => '',
		'validationMsgFontWeight'          => '',
		'validationMsgFontStyle'           => '',
		'validationMsgTransform'           => '',
		'validationMsgDecoration'          => '',
		'validationMsgLineHeightType'      => 'em',
		'validationMsgLineHeight'          => '',
		'validationMsgLineHeightTablet'    => '',
		'validationMsgLineHeightMobile'    => '',
		'validationMsgLoadGoogleFonts'     => false,
		'successMsgColor'                  => '',
		'successMsgBgColor'                => '',
		'successMsgBorderColor'            => '',
		'errorMsgColor'                    => '',
		'errorMsgBgColor'                  => '',
		'errorMsgBorderColor'              => '',
		'msgBorderSize'                    => '',
		'msgBorderSizeUnit'                => 'px',
		'msgBorderRadius'                  => '',
		'msgVrPadding'                     => '',
		'msgHrPadding'                     => '',
		'msgFontSize'                      => '',
		'msgFontSizeType'                  => 'px',
		'msgFontSizeTablet'                => '',
		'msgFontSizeMobile'                => '',
		'msgFontFamily'                    => '',
		'msgFontWeight'                    => '',
		'msgFontStyle'                     => '',
		'msgTransform'                     => '',
		'msgDecoration'                    => '',
		'msgLineHeightType'                => 'em',
		'msgLineHeight'                    => '',
		'msgLineHeightTablet'              => '',
		'msgLineHeightMobile'              => '',
		'msgLoadGoogleFonts'               => false,
		'radioCheckBorderRadiusType'       => 'px',
		'msgBorderRadiusType'              => 'px',
		'fieldBorderRadiusType'            => 'px',
		'buttonBorderRadiusType'           => 'px',
		'messageTopPaddingTablet'          => '',
		'messageBottomPaddingTablet'       => '',
		'messageLeftPaddingTablet'         => '',
		'messageRightPaddingTablet'        => '',
		'messageTopPaddingMobile'          => '',
		'messageBottomPaddingMobile'       => '',
		'messageLeftPaddingMobile'         => '',
		'messageRightPaddingMobile'        => '',
		'messagePaddingTypeDesktop'        => 'px',
		'messagePaddingTypeTablet'         => 'px',
		'messagePaddingTypeMobile'         => 'px',
		'messageSpacingLink'               => false,
		'buttonTopPaddingTablet'           => '',
		'buttonBottomPaddingTablet'        => '',
		'buttonLeftPaddingTablet'          => '',
		'buttonRightPaddingTablet'         => '',
		'buttonTopPaddingMobile'           => '',
		'buttonBottomPaddingMobile'        => '',
		'buttonLeftPaddingMobile'          => '',
		'buttonRightPaddingMobile'         => '',
		'buttonPaddingTypeDesktop'         => 'px',
		'buttonPaddingTypeTablet'          => 'px',
		'buttonPaddingTypeMobile'          => 'px',
		'buttonSpacingLink'                => false,
		'fieldTopPaddingTablet'            => '',
		'fieldBottomPaddingTablet'         => '',
		'fieldLeftPaddingTablet'           => '',
		'fieldRightPaddingTablet'          => '',
		'fieldTopPaddingMobile'            => '',
		'fieldBottomPaddingMobile'         => '',
		'fieldLeftPaddingMobile'           => '',
		'fieldRightPaddingMobile'          => '',
		'fieldPaddingTypeDesktop'          => 'px',
		'fieldPaddingTypeTablet'           => 'px',
		'fieldPaddingTypeMobile'           => 'px',
		'fieldSpacingLink'                 => false,
		'labelLetterSpacing'               => '',
		'labelLetterSpacingTablet'         => '',
		'labelLetterSpacingMobile'         => '',
		'labelLetterSpacingType'           => 'px',
		'inputLetterSpacing'               => '',
		'inputLetterSpacingTablet'         => '',
		'inputLetterSpacingMobile'         => '',
		'inputLetterSpacingType'           => 'px',
		'radioCheckLetterSpacing'          => '',
		'radioCheckLetterSpacingTablet'    => '',
		'radioCheckLetterSpacingMobile'    => '',
		'radioCheckLetterSpacingType'      => 'px',
		'buttonLetterSpacing'              => '',
		'buttonLetterSpacingTablet'        => '',
		'buttonLetterSpacingMobile'        => '',
		'buttonLetterSpacingType'          => 'px',
		'validationMsgLetterSpacing'       => '',
		'validationMsgLetterSpacingTablet' => '',
		'validationMsgLetterSpacingMobile' => '',
		'validationMsgLetterSpacingType'   => 'px',
		'msgLetterSpacing'                 => '',
		'msgLetterSpacingTablet'           => '',
		'msgLetterSpacingMobile'           => '',
		'msgLetterSpacingType'             => 'px',
	),
	$field_border_attribute,
	$button_border_attribute
);
