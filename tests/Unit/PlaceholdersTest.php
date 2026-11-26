<?php
/**
 * Reading placeholders and special mail tags from the settings.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use CFTZ\Tests\TestCase;
use CFTZ_Module_CF7;

class PlaceholdersTest extends TestCase {

    public function test_string_placeholders() {
        $this->assertSame(
            array(
                'your-name' => '[your-name]',
                '_date'     => '[_date]',
            ),
            ctz_get_string_placeholders( 'https://example.com/[your-name]/[_date]?again=[your-name]' )
        );

        $this->assertSame( array(), ctz_get_string_placeholders( 'https://example.com/hook' ) );
    }

    public function test_special_mail_tags_with_and_without_a_key() {
        $this->assertSame(
            array(
                '_date'      => '_date',
                'page-title' => '_post_title',
            ),
            CFTZ_Module_CF7::get_special_mail_tags_from_string( "[_date]\n[_post_title page-title]\n[]" )
        );
    }
}
