<?php
/**
 * Loading the webhook settings of a form.
 *
 * @package Cf7_To_Zapier
 */

namespace CFTZ\Tests\Unit;

use CFTZ\Tests\TestCase;
use CFTZ_Module_CF7;
use Cf7_To_Zapier;

class LoadSettingsTest extends TestCase {

    public function test_registers_the_settings_in_both_contact_form_7_filters() {
        // Issue #11: Contact Form 7 started loading properties through the second filter, and the settings were lost.
        $core   = new Cf7_To_Zapier();
        $module = new CFTZ_Module_CF7( $core );
        $module->run();

        foreach ( array( 'wpcf7_contact_form_properties', 'wpcf7_pre_construct_contact_form_properties' ) as $filter ) {
            $this->assertContains(
                array( 'filter', $filter, array( $module, 'wpcf7_contact_form_properties' ), 10, 2 ),
                $core->hooks,
                $filter
            );
        }
    }

    public function test_adds_the_defaults_and_keeps_saved_settings() {
        $module = new CFTZ_Module_CF7( new Cf7_To_Zapier() );

        $properties = $module->wpcf7_contact_form_properties( array( 'form' => '' ), null );
        $this->assertSame( CFTZ_Module_CF7::get_form_properties(), $properties[ CFTZ_Module_CF7::METADATA ] );

        $saved = array( CFTZ_Module_CF7::METADATA => array( 'activate' => '1' ) );
        $this->assertSame( $saved, $module->wpcf7_contact_form_properties( $saved, null ) );
    }
}
