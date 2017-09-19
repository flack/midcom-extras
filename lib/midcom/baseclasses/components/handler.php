<?php
/**
 * @package midcom.extras.baseclasses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler baseclass for DM2 convenience functions
 *
 * @package midcom.extras.baseclasses
 */
abstract class midcom_extras_baseclasses_components_handler extends midcom_baseclasses_components_handler
{
    /**
     * Helper function for quick access to diverse datamanager controllers.
     *
     * For this to work, the handler has to implement the respective DM2 interface
     *
     * @todo Maybe we should do a class_implements check here
     * @param string $type The controller type
     * @param midcom_core_dbaobject $object The object, if any
     * @return midcom_helper_datamanager2_controller The initialized controller
     */
    public function get_controller($type, $object = null)
    {
        switch ($type) {
            case 'simple':
                return midcom_helper_datamanager2_handler::get_simple_controller($this, $object);
            case 'nullstorage':
                return midcom_helper_datamanager2_handler::get_nullstorage_controller($this);
            case 'create':
                return midcom_helper_datamanager2_handler::get_create_controller($this);
            default:
                throw new midcom_error("Unsupported controller type: {$type}");
        }
    }
    /**
     * Default helper function for DM2 schema-related operations
     *
     * @return string The default DM2 schema name
     */
    public function get_schema_name()
    {
        return 'default';
    }
    /**
     * Default helper function for DM2 schema-related operations
     *
     * @return array The schema defaults
     */
    public function get_schema_defaults()
    {
        return [];
    }
}