<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Iconist_Sanitizer {

    private static $dangerous_tags = [
        'script', 'object', 'embed', 'iframe', 'frame', 'frameset',
        'applet', 'form', 'input', 'button', 'link', 'meta', 'base',
        'foreignobject',
    ];

    private static $dangerous_attrs = [
        'onload', 'onclick', 'onmouseover', 'onmouseout', 'onmousemove',
        'onfocus', 'onblur', 'onchange', 'onsubmit', 'onerror', 'onabort',
        'onunload', 'onresize', 'onscroll', 'onkeydown', 'onkeyup',
        'onkeypress', 'ontouchstart', 'ontouchend', 'ontouchmove',
    ];

    private static $url_attrs = [
        'href', 'xlink:href', 'src', 'action', 'data',
    ];

    public static function sanitize( $content ) {
        $dom = new DOMDocument();
        libxml_use_internal_errors( true );
        $loaded = $dom->loadXML( $content, LIBXML_NOWARNING | LIBXML_NOERROR );
        libxml_clear_errors();

        if ( ! $loaded ) {
            return false;
        }

        self::clean_node( $dom->documentElement );

        return $dom->saveXML( $dom->documentElement );
    }

    private static function clean_node( $node ) {
        if ( ! $node || $node->nodeType !== XML_ELEMENT_NODE ) {
            return;
        }

        if ( in_array( strtolower( $node->localName ), self::$dangerous_tags, true ) ) {
            if ( $node->parentNode ) {
                $node->parentNode->removeChild( $node );
            }
            return;
        }

        $attrs_to_remove = array();
        foreach ( $node->attributes as $attr ) {
            $name  = strtolower( $attr->name );
            $value = $attr->value;

            if ( in_array( $name, self::$dangerous_attrs, true ) ) {
                $attrs_to_remove[] = $attr->name;
                continue;
            }

            if ( in_array( $name, self::$url_attrs, true ) ) {
                $trimmed = ltrim( $value );
                if (
                    stripos( $trimmed, 'javascript:' ) === 0 ||
                    stripos( $trimmed, 'data:text/html' ) === 0 ||
                    stripos( $trimmed, 'vbscript:' ) === 0
                ) {
                    $attrs_to_remove[] = $attr->name;
                }
            }

            if ( $name === 'style' && stripos( $value, 'expression(' ) !== false ) {
                $attrs_to_remove[] = $attr->name;
            }
        }

        foreach ( $attrs_to_remove as $attr_name ) {
            if ( strpos( $attr_name, ':' ) !== false ) {
                list( $prefix, $local ) = explode( ':', $attr_name, 2 );
                $node->removeAttributeNS( $node->lookupNamespaceURI( $prefix ), $local );
            } else {
                $node->removeAttribute( $attr_name );
            }
        }

        if ( strtolower( $node->localName ) === 'style' ) {
            $css = $node->nodeValue;
            if ( preg_match( '/<\s*script|javascript:|expression\s*\(/i', $css ) ) {
                $node->nodeValue = '';
            }
        }

        $children = array();
        foreach ( $node->childNodes as $child ) {
            $children[] = $child;
        }

        foreach ( $children as $child ) {
            self::clean_node( $child );
        }
    }
}
