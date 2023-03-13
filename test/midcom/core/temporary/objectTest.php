<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_core_temporay_objectTest extends openpsa_testcase
{
    public function test_crud()
    {
        $object = new midcom_core_temporary_object;
        midcom::get()->auth->request_sudo('midcom.core');
        $this->assertTrue($object->create(), midcom_connection::get_error_string());
        $this->assertTrue($object->update(), midcom_connection::get_error_string());
        $this->assertTrue($object->delete(), midcom_connection::get_error_string());
        midcom::get()->auth->drop_sudo();
    }
}
