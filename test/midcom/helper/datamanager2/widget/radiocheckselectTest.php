<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once __DIR__ . '/../__helper/dm2.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_datamanager2_widget_radiocheckselectTest extends openpsa_testcase
{
    public function test_get_default_radio()
    {
        $config = [
            'type_config' => [
                'allow_multiple' => false,
                'options' => [
                    1 => 'value 1',
                    2 => 'value 2'
                ]
            ]
        ];

        $default_values = [
            'test_radiocheckselect_1' => 2
        ];

        midcom::get()->auth->request_sudo('midcom.helper.datamanager2');

        $dm2_helper = new midcom_extras_test_dm2_helper;
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertNull($widget->get_default(), 'nullstorage test failed');

        $dm2_helper->defaults = ['test_radiocheckselect_1' => 2];
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertEquals($default_values, $widget->get_default(), 'nullstorage/default test failed');

        $config['storage'] = 'type';
        $event = new org_openpsa_calendar_event_dba;
        $dm2_helper = new midcom_extras_test_dm2_helper($event);
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertNull($widget->get_default(), 'create test failed');

        $dm2_helper->defaults = ['test_radiocheckselect_1' => 2];
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertEquals($default_values, $widget->get_default(), 'create/default test failed');

        $event = $this->create_object('org_openpsa_calendar_event_dba', [
            'start' => time() - 60 * 60,
            'end' => time() + 60 * 60
        ]);
        $dm2_helper = new midcom_extras_test_dm2_helper($event);
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertEquals('', $widget->get_default(), 'simple test failed');
        $event->type = 2;
        $event->update();

        $dm2_helper = new midcom_extras_test_dm2_helper($event);
        //Lazy workaround to reuse the default array from above
        $dm2_helper->get_widget('radiocheckselect', 'select', $config);
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertEquals($default_values, $widget->get_default(), 'simple/storage test failed');

        midcom::get()->auth->drop_sudo();
    }

    public function test_get_default_checkbox()
    {
        $config = [
            'type_config' => [
                'allow_multiple' => true,
                'options' => [
                    1 => 'value 1',
                    2 => 'value 2'
                ]
            ]
        ];

        $default_values = [
            'test_radiocheckselect_1' => [2 => true]
        ];

        midcom::get()->auth->request_sudo('midcom.helper.datamanager2');

        $dm2_helper = new midcom_extras_test_dm2_helper;
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertNull($widget->get_default(), 'nullstorage test failed');

        $dm2_helper->defaults = ['test_radiocheckselect_1' => [2]];
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertEquals($default_values, $widget->get_default(), 'nullstorage/default test failed');

        $config['storage'] = 'extra';
        $event = new org_openpsa_calendar_event_dba;
        $dm2_helper = new midcom_extras_test_dm2_helper($event);
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertNull($widget->get_default(), 'create test failed');

        $dm2_helper->defaults = ['test_radiocheckselect_1' => [2]];
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertEquals($default_values, $widget->get_default(), 'create/default test failed');

        $event = $this->create_object('org_openpsa_calendar_event_dba', [
            'start' => time() - 60 * 60,
            'end' => time() + 60 * 60
        ]);
        $dm2_helper = new midcom_extras_test_dm2_helper($event);
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertEquals('', $widget->get_default(), 'simple test failed');
        $event->extra = serialize([2]);

        $dm2_helper = new midcom_extras_test_dm2_helper($event);
        //Lazy workaround to reuse the default array from above
        $dm2_helper->get_widget('radiocheckselect', 'select', $config);
        $widget = $dm2_helper->get_widget('radiocheckselect', 'select', $config);

        $this->assertEquals($default_values, $widget->get_default(), 'simple/storage test failed');

        midcom::get()->auth->drop_sudo();
    }
}
