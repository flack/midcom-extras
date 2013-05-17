<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple date widget
 *
 * This widget is built around the PEAR QuickForm Date widget, which effectively
 * consists of a set of input fields for each part of the date/time. It is limited
 * to seconds precision therefore. Currently unsupported are the Day options (selects
 * Monday through Sunday) and 12-Hour Time formats (AM/PM time).
 *
 * This widget requires the date type or a subclass thereof.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>string format:</i> The format of the input fields, as outlined in the QuickForm
 *   documentation (referenced at the $format member). This defaults to 'dmY'.
 * - <i>int minyear:</i> Minimum Year available for selection (defaults to 2000).
 * - <i>int maxyear:</i> Maximum Year available for selection (defaults to 2010).
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_simpledate extends midcom_helper_datamanager2_widget
{
    /**
     * The format to use for display.
     *
     * @link http://pear.php.net/manual/en/package.html.html-quickform.html-quickform-date.html-quickform-date.php
     * @var string
     */
    var $format = 'dmY';

    /**
     * Minimum Year available for selection.
     *
     * @var int
     */
    var $minyear = 2010;

    /**
     * Maximum Year available for selection.
     *
     * @var int
     */
    var $maxyear = 2020;

    /**
     * First items for selections.
     *
     * @var array
     */
    var $first_items = array('d' => 'DD', 'm' => 'MM', 'Y' => 'YYYY');

    var $_items = array();
    var $_elements = array();

    /**
     * Validates the base type
     */
    public function _on_initialize()
    {
        if (! is_a($this->_type, 'midcom_helper_datamanager2_type_date'))
        {
            debug_add("Warning, the field {$this->name} is not a select type or subclass thereof, you cannot use the select widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        $this->_generate_items();

        return true;
    }

    /**
     * Adds a PEAR Date widget to the form
     */
    function add_elements_to_form($attributes)
    {
        for($i = 0; $i < strlen($this->format); $i++)
        {
            $key = $this->format{$i};

            $this->_elements[] = $this->_form->createElement
            (
                'select',
                $key,
                '',
                $this->_items[$key],
                array
                (
                    'class'         => 'dropdown',
                    'id'            => "{$this->_namespace}{$this->name}_{$key}",
                )
            );
        }

        $this->_form->addGroup($this->_elements, $this->name, $this->_translate($this->_field['title']), '');

        if ($this->_field['required'])
        {
            $errmsg = sprintf($this->_l10n->get('field %s is required'), $this->_field['title']);
            $this->_form->addGroupRule($this->name, $errmsg, 'nonzero', null, 3);
        }
        $this->_form->addRule($this->name, $this->_translate('validation failed: date'), 'checksimpledate');
    }

    private function _generate_items()
    {
        for($i = 0; $i < strlen($this->format); $i++)
        {
            switch ($this->format{$i})
            {
                case 'd':
                    $this->_items[$this->format{$i}] = array();
                    $this->_populate_first_item($this->format{$i});

                    for ($d=1; $d<=31; $d++)
                    {
                        $value = $d<10?"0{$d}":$d;
                        $this->_items[$this->format{$i}][$d] = $value;
                    }
                    break;
                case 'M':
                case 'm':
                case 'F':
                    $this->_items[$this->format{$i}] = array();
                    $this->_populate_first_item($this->format{$i});

                    for ($m = 1; $m <= 12; $m++)
                    {
                        $value = $m < 10 ? "0{$m}" : $m;
                        $this->_items[$this->format{$i}][$m] = $value;
                    }
                    break;
                case 'Y':
                    $this->_items[$this->format{$i}] = array();
                    $this->_populate_first_item($this->format{$i});

                    for ($y = $this->minyear; $y <= $this->maxyear; $y++)
                    {
                        $value = $y;
                        $this->_items[$this->format{$i}][$value] = $value;
                    }
                    break;
                case 'y':
                    $this->_items[$this->format{$i}] = array();
                    $this->_populate_first_item($this->format{$i});

                    for ($y = $this->minyear; $y <= $this->maxyear; $y++)
                    {
                        $value = substr($y, -2);
                        $this->_items[$this->format{$i}][$value] = $value;
                    }
                    break;
                case 'H':
                    $this->_items[$this->format{$i}] = array();
                    $this->_populate_first_item($this->format{$i});

                    for ($h = 0; $h <= 12; $h++)
                    {
                        $value = $h < 10 ? "0{$h}" : $h;
                        $this->_items[$this->format{$i}][$h] = $value;
                    }
                    break;
                case 'i':
                    $this->_items[$this->format{$i}] = array();
                    $this->_populate_first_item($this->format{$i});

                    for ($m = 0; $m <= 59; $m++)
                    {
                        $value = $m < 10 ? "0{$m}" : $m;
                        $this->_items[$this->format{$i}][$m] = $value;
                    }
                    break;
                case 's':
                    $this->_items[$this->format{$i}] = array();
                    $this->_populate_first_item($this->format{$i});

                    for ($s = 0; $s <= 59; $s++)
                    {
                        $value = $s < 10 ? "0{$s}" : $s;
                        $this->_items[$this->format{$i}][$s] = $value;
                    }
                    break;
            }
        }
    }

    private function _populate_first_item($key)
    {
        if (isset($this->first_items[$key]))
        {
            $this->_items[$key][] = $this->first_items[$key];
        }
        else
        {
            $this->_items[$key][] = '';
        }
    }

    /**
     * The default call parses the format string and retrieves the corresponding
     * information from the Date class of the type.
     */
    public function get_default()
    {
        if (null === $this->_type->value)
        {
            return null;
        }
        $defaults = Array();
        for ($i = 0; $i < strlen($this->format); $i++)
        {
            switch ($this->format{$i})
            {
                case 'd':
                    $format = 'j';
                    break;
                case 'M':
                case 'm':
                case 'F':
                    $format = 'n';
                    break;
                case 'Y':
                    $format = 'Y';
                    break;
                case 'H':
                    $format = 'G';
                    break;
            }
            if ($this->_type->is_empty())
            {
                $defaults[$this->format{$i}] = '';
            }
            else
            {
                $value = (int) $this->_type->value->format($format);
                if ($value == 0)
                {
                    $defaults[$this->format{$i}] = '';
                }
                else
                {
                    $defaults[$this->format{$i}] = $value;
                }
            }
        }

        return Array($this->name => $defaults);
    }

    function sync_type_with_widget($results)
    {
        if (! $results[$this->name])
        {
            return;
        }

        $year = 0;
        $month = 0;
        $day = 0;
        $hour = 0;
        $minute = 0;
        $second = 0;

        foreach ($results[$this->name] as $formatter => $value)
        {
            if ($value == '')
            {
                continue;
            }
            switch ($formatter)
            {
                case 'd':
                    $day = $value;
                    break;

                case 'M':
                case 'm':
                case 'F':
                    $month = $value;
                    break;

                case 'y':
                    if ($value < 30)
                    {
                        $value += 2000;
                    }
                    else
                    {
                        $value += 1900;
                    }
                    // ** FALL THROUGH **

                case 'Y':
                    $year = $value;
                    break;

                case 'H':
                    $hour = $value;
                    break;

                case 'i':
                    $minute = $value;
                    break;

                case 's':
                    $second = $value;
                    break;
            }
        }
        $this->_type->value->setDate((int) $year, (int) $month, (int) $day);
        $this->_type->value->setTime((int) $hour, (int) $minute, (int) $second);
    }

    /**
     * Renders the date using an ISO syntax
     */
    public function render_content()
    {
        $with_date = false;
        $with_time = false;

        for ($i = 0; $i < strlen($this->format); $i++)
        {
            switch ($this->format{$i})
            {
                case 'd':
                case 'M':
                case 'm':
                case 'F':
                case 'Y':
                case 'y':
                    $with_date = true;
                    break;

                case 'H':
                case 'i':
                case 's':
                    $with_time = true;
                    break;
            }
        }
        $format_string = '';
        if ($with_date)
        {
            $format_string .= '%Y-%m-%d';
        }
        if (   $with_date
            && $with_time)
        {
            $format_string .= ' ';
        }
        if ($with_time)
        {
            $format_string .= '%T';
        }

        return $this->_type->value->format($format_string);
    }
}
?>