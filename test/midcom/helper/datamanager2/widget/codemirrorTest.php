<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once OPENPSA_TEST_ROOT . 'midcom/helper/datamanager2/__helper/dm2.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_datamanager2_widget_codemirrorTest extends openpsa_testcase
{
    public function test_get_default()
    {
        $dm2_helper = new midcom_extras_test_dm2_helper;
        $widget = $dm2_helper->get_widget('codemirror', 'php');

        $this->assertNull($widget->get_default(), 'nullstorage test failed');

        $dm2_helper->defaults = ['test_codemirror_1' => 'TEST'];
        $widget = $dm2_helper->get_widget('codemirror', 'php');

        $this->assertEquals('TEST', $widget->get_default(), 'nullstorage/default test failed');

        $topic = new midcom_db_topic;
        $dm2_helper = new midcom_extras_test_dm2_helper($topic);
        $widget = $dm2_helper->get_widget('codemirror', 'php', ['storage' => 'extra']);

        $this->assertNull($widget->get_default(), 'create test failed');

        $dm2_helper->defaults = ['test_codemirror_1' => 'TEST'];
        $widget = $dm2_helper->get_widget('codemirror', 'php', ['storage' => 'extra']);

        $this->assertEquals('TEST', $widget->get_default(), 'create/default test failed');

        $topic = $this->create_object('midcom_db_topic');
        $dm2_helper = new midcom_extras_test_dm2_helper($topic);
        $widget = $dm2_helper->get_widget('codemirror', 'php', ['storage' => 'extra']);

        $this->assertEquals('', $widget->get_default(), 'simple test failed, ');

        $topic->extra = 'TEST';

        $dm2_helper = new midcom_extras_test_dm2_helper($topic);
        $widget = $dm2_helper->get_widget('codemirror', 'php', ['storage' => 'extra']);

        $this->assertEquals('TEST', $widget->get_default(), 'simple/storage test failed');
    }
}
