<?php
/**
 * Adds a table of contents to your pages based on h1, h2, h3 and h4 tags. Useful for documention-centric sites.
 *
 * @author Automattic, modifed by Nacin
 */
class WPorg_Handbook_TOC {
	protected $post_type = 'handbook';

	protected $styles = '<style> .toc-jump { text-align: right; font-size: 12px; } .page .toc-heading { margin-top: -50px; padding-top: 50px !important; }</style>';

	function __construct() {
		add_action( 'template_redirect', array( $this, 'load_filters' ) );
	}

	function load_filters() {
		if ( is_singular( $this->post_type ) )
			add_filter( 'the_content', array( $this, 'add_toc' ) );
	}

	function add_toc( $content ) {
		$toc = '';

		$items = $this->get_tags( 'h([1-4])', $content );

		for ( $i = 1; $i <= 4; $i++ )
			$content = $this->add_ids_and_jumpto_links( "h$i", $content );

		if ( $items )
			$contents_header = $pages_header = 'h' . $items[0][2]; // Duplicate the first <h#> tag in the document.
		else
			$pages_header = 'h3';

		if ( $items ) {
			$toc .= $this->styles;
			$toc .= '<div class="table-of-contents">';
			$toc .= "<$contents_header>Topics</$contents_header><ul class=\"items\">";
			$last_item = false;
			foreach ( $items as $item ) {
				if ( $last_item ) {
					if ( $last_item < $item[2] )
						$toc .= "\n<ul>\n";
					elseif ( $last_item > $item[2] )
						$toc .= "\n</ul></li>\n";
					else
						$toc .= "</li>\n";
				}

				$last_item = $item[2];
				$toc .= '<li><a href="#' . sanitize_title_with_dashes($item[3])  . '">' . $item[3]  . '</a>';
			}
			$toc .= "</ul>\n</div>\n";
		}

		return $toc . $content;
	}

	protected function add_ids_and_jumpto_links( $tag, $content ) {
		$items = $this->get_tags( $tag, $content );
		$first = true;

		foreach ($items as $item) {
			$replacement = '';
			$matches[] = $item[0];
			$id = sanitize_title_with_dashes($item[2]);

			if ( ! $first ) {
				$replacement .= '<p class="toc-jump"><a href="#top">Top &uarr;</a></p>';
			} else {
				$first = false;
			}

			$replacement .= sprintf( '<%1$s class="toc-heading" id="%2$s">%3$s <a href="#%2$s" class="anchor">#</a></%1$s>', $tag, $id, $item[2] );
			$replacements[] = $replacement;
		}

		$content = str_replace( $matches, $replacements, $content );

		return $content;
	}

	private function get_tags( $tag, $content = '' ) {
		if ( empty( $content ) )
			$content = get_the_content();
		preg_match_all( "/(<{$tag}>)(.*)(<\/{$tag}>)/", $content, $matches, PREG_SET_ORDER );
		return $matches;
	}
}

