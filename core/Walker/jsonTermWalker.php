<?php
/**
 * WPLogger: jsonTermWalker
 *
 * walker for making terms in json format with children
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Walker;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use Walker_Category;
use WP_Term;

/**
 * Class jsonTermWalker
 *
 * @package WPLogger
 */
class jsonTermWalker extends Walker_Category
{
    /**
     * Starts the element output.
     *
     * @since 1.0.0
     *
     * @see Walker::start_el()
     *
     * @param string  $output   Used to append additional content (passed by reference).
     * @param WP_Term $term     term data object.
     * @param int     $depth    Optional. Depth of category in reference to parents. Default 0.
     * @param array   $args     Optional. An array of arguments. See wp_list_categories(). Default empty array.
     * @param int     $id       Optional. ID of the current category. Default 0.
     */
    public function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 )
    {
        $term_name = apply_filters(
            'list_cats',
            esc_attr( $term->name ),
            $term
        );

        if ( !$term_name ) return;

        $item  = '{"id":' . $term->term_id;
        $item .= ',"name":"' . $term_name . '"';

        $children = get_terms( array(
            'parent'     => $term->term_id,
            'taxonomy'   => $term->taxonomy,
            'hide_empty' => true
        ) );
        if ( $children )
            foreach ( $children as $child )
                $term->count += $child->count;

        $item   .= ',"count":' . $term->count;
        $output .= $item;
    }

    /**
     * Ends the element output, if needed.
     *
     * @since 1.0.0
     *
     * @see Walker::end_el()
     *
     * @param string $output Used to append additional content (passed by reference).
     * @param object $page   Not used.
     * @param int    $depth  Optional. Depth of category. Not used.
     * @param array  $args   Optional. An array of arguments. Only uses 'list' for whether should append
     *                       to output. See wp_list_categories(). Default empty array.
     */
    public function end_el( &$output, $page, $depth = 0, $args = array() )
    {
            $output .= '},';
    }

    /**
     * Starts the list before the elements are added.
     *
     * @since 1.0.0
     *
     * @see Walker::start_lvl()
     *
     * @param string $output Used to append additional content. Passed by reference.
     * @param int    $depth  Optional. Depth of category. Used for tab indentation. Default 0.
     * @param array  $args   Optional. An array of arguments. Will only append content if style argument
     *                       value is 'list'. See wp_list_categories(). Default empty array.
     */
    public function start_lvl( &$output, $depth = 0, $args = array() )
    {
        $output .= ',"children":[';
    }

    /**
     * Ends the list of after the elements are added.
     *
     * @since 1.0.0
     *
     * @see Walker::end_lvl()
     *
     * @param string $output Used to append additional content. Passed by reference.
     * @param int    $depth  Optional. Depth of category. Used for tab indentation. Default 0.
     * @param array  $args   Optional. An array of arguments. Will only append content if style argument
     *                       value is 'list'. See wp_list_categories(). Default empty array.
     */
    public function end_lvl( &$output, $depth = 0, $args = array() )
    {
        $output  = rtrim( $output,',' );
        $output .= ']';
    }
}