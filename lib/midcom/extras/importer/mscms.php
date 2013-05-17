<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package net.nehmer.static
 */
class net_nehmer_static_import_mscms_folder
{
    var $name = '';
    var $title = '';
    var $has_index = false;
    var $component = 'net.nehmer.static';
    var $folders = array();
    var $files = array();
}

/**
 * @package net.nehmer.static
 */
class net_nehmer_static_import_mscms_file
{
    var $name = '';
    var $title = '';
    var $abstract = '';
    var $content = '';
    var $schema = 'default';
}

/**
 * @package net.nehmer.static
 */
class net_nehmer_static_import_mscms
{
    public function __construct()
    {
        $this->purifier = new HTMLPurifier();
        $this->purifier->config->set('HTML', 'EnableAttrID', true);
        $this->purifier->config->set('HTML', 'Doctype', 'XHTML 1.0 Strict');
        $this->purifier->config->set('HTML', 'TidyLevel', 'light');
        $this->purifier->config->set('Core', 'EscapeNonASCIICharacters', true);

        $this->purifier2 = new HTMLPurifier();
        $this->purifier2->config->set('HTML', 'Doctype', 'XHTML 1.0 Strict');
        $this->purifier2->config->set('HTML', 'TidyLevel', 'heavy');
        $this->purifier2->config->set('Core', 'EscapeNonASCIICharacters', true);
    }

    function parse_file($path)
    {
        $file = new net_nehmer_static_import_mscms_file();
        $file->name = str_replace('.htm', '', basename($path));

        $mscms_data = file_get_contents($path);

        // HTMLpurifier doesn't allow IDs starting with underscore
        $mscms_data = str_replace('_ctl0', 'ctl', $mscms_data);
        $mscms_data = str_replace('span', 'div', $mscms_data);

        // Sanitize XHTML here
        $mscms_data = $this->purifier->purify($mscms_data);
        $simplexml = @simplexml_load_string($mscms_data);
        if (!$simplexml)
        {
            return;
        }

        $page_types = $simplexml->xpath('//*/div[@id="page"]');
        foreach ($page_types[0]->attributes() as $name => $value)
        {
            if ($name == 'class')
            {
                switch ($name)
                {
                    case 'sectionFrontPage':
                        $file->schema = 'areafront';
                        break;

                    case 'page':
                    default:
                        $file->schema = 'default';
                        break;
                }
            }
        }

        $titles = $simplexml->xpath("//*/div[@id='ctl_ContentPlaceHolder1_RadEditorPlaceHolderControl2']");
        if (isset($titles[0]))
        {
            $file->title = (string) $titles[0];
        }

        if (empty($file->title))
        {
            $titles = $simplexml->xpath("//*/h1");
            if (isset($titles[0]))
            {
                $file->title = (string) $titles[0];
            }
        }

        if (empty($file->title))
        {
            $file->title = ucfirst($file->name);
        }

        if ($file->schema == 'areafront')
        {
            $contents = $simplexml->xpath("//*/div[@id='ctl_ContentPlaceHolder1_MainContent1']");
        }
        else
        {
            $contents = $simplexml->xpath("//*/div[@id='ctl_ContentPlaceHolder1_RadEditorPlaceHolderControl3']");
        }

        foreach ($contents as $content)
        {
            $content_string = (string) $content;
            if (!empty($content_string))
            {
                $file->content .= (string) $this->purifier2->purify($content->asXml());
            }
        }

        $abstracts = $simplexml->xpath("//*/div[@id='ctl_ContentPlaceHolder1_RadEditorPlaceHolderControl7']");
        foreach ($abstracts as $abstract)
        {
            $abstract_string = (string) $abstract;
            if (!empty($abstract_string))
            {
                $file->abstract .= (string) $this->purifier2->purify($abstract->asXml());
            }
        }

        return $file;
    }

    function list_files($path)
    {
        $directory = dir($path);

        $folder = new net_nehmer_static_import_mscms_folder();
        $folder->name = basename($path);
        $folder->title = ucfirst(basename($path));

        if (file_exists("{$path}/site.xml"))
        {
            // This is the "site link mapping file"
            $this->parse_sitexml("{$path}/site.xml");
        }

        while (false !== ($entry = $directory->read()))
        {
            if (substr($entry, 0, 1) == '.')
            {
                // Ignore dotfiles
                continue;
            }

            if (is_dir("{$path}/{$entry}"))
            {
                // Recurse deeper
                $folder->folders[] = $this->list_files("{$path}/{$entry}");
            }
            else
            {
                $path_parts = pathinfo($entry);

                if ($path_parts['basename'] == 'index.htm')
                {
                    $folder->has_index = true;
                }

                if ($path_parts['extension'] == 'htm')
                {
                    $file = $this->parse_file("{$path}/{$entry}");
                    if (!is_null($file))
                    {
                        $folder->files[] = $file;
                    }
                }
            }
        }

        $directory->close();

        return $folder;
    }

    function import_folder($folder, $parent_id)
    {
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', (int) $parent_id);
        $qb->add_constraint('name', '=', $folder->name);
        $existing = $qb->execute();
        if (   count($existing) > 0
            && $existing[0]->up == $parent_id)
        {
            $topic = $existing[0];
            echo "Using existing topic {$topic->name} (#{$topic->id}) from #{$topic->up}\n";
        }
        else
        {
            $topic = new midcom_db_topic();
            $topic->up = $parent_id;
            $topic->name = $folder->name;
            if (!$topic->create())
            {
                echo "Failed to create folder {$folder->name}: " . midcom_connection::get_error_string() . "\n";
                return false;
            }
            echo "Created folder {$topic->name} (#{$topic->id}) under #{$topic->up}\n";
        }

        $topic->extra = $folder->title;
        $topic->component = $folder->component;
        $topic->update();

        if ($folder->component == 'net.nehmer.static')
        {
            if (!$folder->has_index)
            {
                $topic->set_parameter('net.nehmer.static', 'autoindex', 1);
            }
            else
            {
                $topic->delete_parameter('net.nehmer.static', 'autoindex');
            }
        }

        foreach ($folder->files as $file)
        {
            $this->import_file($file, $topic->id);
        }

        foreach ($folder->folders as $subfolder)
        {
            $this->import_folder($subfolder, $topic->id);
        }

        return true;
    }

    function import_file($file, $parent_id)
    {
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $parent_id);
        $qb->add_constraint('name', '=', $file->name);
        $existing = $qb->execute();
        if (   count($existing) > 0
            && $existing[0]->topic == $parent_id)
        {
            $article = $existing[0];
            echo "Using existing article {$article->name} (#{$article->id}) from #{$article->topic}\n";
        }
        else
        {
            $article = new midcom_db_article();
            $article->topic = $parent_id;
            $article->name = $file->name;
            if (!$article->create())
            {
                echo "Failed to create article {$article->name}: " . midcom_connection::get_error_string() . "\n";
                return false;
            }
            echo "Created article {$article->name} (#{$article->id}) under #{$article->topic}\n";
        }

        $article->title = $file->title;
        $article->abstract = $file->abstract;
        $article->content = $file->content;

        $article->set_parameter('midcom.helper.datamanager2', 'schema_name', $file->schema);
        flush();

        return $article->update();
    }
}
?>
