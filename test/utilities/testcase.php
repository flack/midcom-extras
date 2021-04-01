<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper methods for DM2 tests, use in openpsa_testcase children
 *
 * @package openpsa.test
 */
trait dm2_testcase
{
    public function set_dm2_formdata(midcom_helper_datamanager2_controller $controller, array $formdata)
    {
        $formname = substr($controller->formmanager->namespace, 0, -1);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $form_values = $controller->formmanager->form->exportValues();
        $_POST = array_merge($form_values, $formdata);

        $_POST['_qf__' . $formname] = '';
        $_POST['midcom_helper_datamanager2_save'] = [''];
        $_REQUEST = $_POST;
    }

    public function submit_dm2_form($controller_key, array $formdata, $component, array $args = [])
    {
        $this->reset_server_vars();
        $data = $this->run_handler($component, $args);
        $this->set_dm2_formdata($data[$controller_key], $formdata);

        try {
            $data = $this->run_handler($component, $args);
            if (array_key_exists($controller_key, $data)) {
                $this->assertEquals([], $data[$controller_key]->formmanager->form->_errors, 'Form validation failed');
            }
            $this->assertInstanceOf(midcom_response_relocate::class, $data['__openpsa_testcase_response'], 'Form did not relocate');
            return $data['__openpsa_testcase_response']->getTargetUrl();
        } catch (openpsa_test_relocate $e) {
            $url = $e->getMessage();
            $url = preg_replace('/^\//', '', $url);
            return $url;
        }
    }

    /**
     * same logic as submit_dm2_form, but this method does not expect a relocate
     */
    public function submit_dm2_no_relocate_form($controller_key, array $formdata, $component, array $args = [])
    {
        $this->reset_server_vars();
        $data = $this->run_handler($component, $args);
        $this->set_dm2_formdata($data[$controller_key], $formdata);
        $data = $this->run_handler($component, $args);

        $this->assertEquals([], $data[$controller_key]->formmanager->form->_errors, 'Form validation failed');

        return $data;
    }
}
