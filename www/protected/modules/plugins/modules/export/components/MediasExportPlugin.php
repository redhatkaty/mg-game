<?php // -*- tab-width:2; indent-tabs-mode:nil -*-
/**
 *
 * @BEGIN_LICENSE
 *
 * Metadata Games - A FOSS Electronic Game for Archival Data Systems
 * Copyright (C) 2013 Mary Flanagan, Tiltfactor Laboratory
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License
 * as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this program.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * @END_LICENSE
 *
 */

Yii::import('ext.php-metadata-toolkit.XMPAppend');

/************************************************************************
 *  Debugging/Logging Functions
 ************************************************************************/

function debug_log($message)
{
    // If debugging is active, we'll direct logging messages to the
    // error log.
    //error_log($message);
}

/************************************************************************
 *  End Debugging/Logging Functions
 ************************************************************************/

class MediasExportPlugin extends MGExportPlugin
{
    public $enableOnInstall = true;

    private $output_directory = NULL;

    function init()
    {
        parent::init();
    }

    /**
     * Adds a checkbox that allows to activate/disactivate the use of the plugin on the
     * export form.
     *
     * @param object $form the GxActiveForm rendering the export form
     * @param object $model the ExportForm instance holding the forms values
     *
     * @return string|void
     */
    function form(&$form, &$model)
    {
        $legend = CHtml::tag("legend", array(),
            Yii::t('app', 'Plugin: Media Export'));

        $value = $this->is_active() ? 1 : 0;
        $label = CHtml::label(Yii::t('app', 'Active'),
            'ExportForm_MediasExportPlugin_active');

        $buttons = CHtml::radioButtonList(
            "ExportForm[MediasExportPlugin][active]",
            $value,
            MGHelper::itemAlias("yes-no"),
            array("template" => '<div class="checkbox">{input} {label}</div>',
                "separator" => ""));

        return CHtml::tag("fieldset", array(),
            $legend .
                '<div class="row">' . $label . $buttons .
                '<div class="description">' .
                Yii::t('app',
                    "Export media in a zipped-up directory.") .
                '</div></div>');
    }

    // Provide pieces of information about the install for embedding
    // into files and medias.
    function systemInformation()
    {
        return array( // version
            Yii::app()->params['version'],
            // format
            Yii::app()->params['tags_csv_format'],
            // date
            date("r"),
            // system
            //
            // URLs should look something like:
            // "some.university.edu/mg/ "
            Yii::app()->createAbsoluteUrl(''));
    }

    /**
     * Creates needed subfolder and a README.txt with current information in the
     * temporary folder
     *
     * @param object $model the ExportForm instance
     * @param object $command the CDbCommand instance holding all information needed to retrieve the medias' data
     * @param string $tmp_folder the full path to the temporary folder
     *
     * @return int|void
     */
    function preProcess(&$model, &$command, $tmp_folder)
    {
        if (!$this->is_active()) {
            return 0;
        }
    }

    /**
     * Retrieves the tags of the image, copies the image, and embeds its tags
     * regarding the tags threshold into the images header (making use of XMP)
     *
     * @param object $model the ExportForm instance
     * @param object $command the CDbCommand instance holding all information needed to retrieve the images' data
     * @param string $tmp_folder the full path to the temporary folder
     * @param int $media_id
     * @internal param int $image_id the id of the image that should be exported
     *
     * @return int|void
     */
    function process(&$model, &$command, $tmp_folder, $media_id)
    {
        if (!$this->is_active()) {
            return 0;
        }

        // These are the values we'll embed into the XMP metadata of each
        // exported media.
        list($version, $format, $date, $system) = $this->systemInformation();

        // Query the database to get the information about each media we
        // will be including in our export.
        $sql = "tu.media_id,COUNT(tu.id) tu_count,MIN(tu.weight) w_min,MAX(tu.weight) w_max,AVG(tu.weight) w_avg,SUM(tu.weight) as w_sum,t.tag,i.name,i.mime_type,inst.url";
        $command->selectDistinct($sql);

        $command->where(array('and', $command->where, 'tu.media_id = :mediaID'),
            array(":mediaID" => $media_id));
        $command->order('tu.media_id, t.tag');

        $info = $command->queryAll();
        $c = count($info);
        $tags = array();
        $url = '';

        // Copy all of the matching tags out of the query result and into
        // an array.
        for ($i = 0; $i < $c; $i++) {
            if ($i == 0) {
                $url = $info[$i]['url'];
            }
            $tags[] = $info[$i]['tag'];
        }

        // Extract the filename of the media from the query results array.
        $filename = $info[0]['name'];
        $mediaType = $info[0]['mime_type'];
        list($type1, $type2) = explode("/", $mediaType);
        $this->createOutputDirectory($tmp_folder, $type1);
        if ($type1 === "image") {
            $source_directory = preg_replace('/\/*$/', '', $url) . UPLOAD_PATH . "/images";
            $output_directory = $tmp_folder . "images";

            $output_filepath = "$output_directory/$filename";
            $source_filepath = "$source_directory/$filename";

            // Get the embedded XMP data from our image.
            $xmp = new XMPAppend();

            $header_data = $xmp->get_jpeg_header_data($source_filepath);

            $xmp_array = read_XMP_array_from_text(get_XMP_text($header_data));

            $existing_dc_metadata = $xmp->get_xmp_dc($xmp_array);

            // Following the formatting guidelines, create a string that
            // embeds not ony the tags, but also includes key information such
            // as a datestamp, version of mg, and installation location of the
            // mg server software.
            $description_blurb =
                "[org.tiltfactor.metadatagames_$version f$format ($date) " .
                    "(" . implode(", ", $tags) . ") installation: $system]";

            debug_log("process: Description blurb is: $description_blurb");

            // Append the new metadata to the old array (filling-in/creating
            // any missing metadata contents/structure necessary along the
            // way).
            $updated_dc_metadata =
                $xmp->append_to_xmp_dc($xmp_array,
                    array("description" => $description_blurb));

            // Put the tweaked XMP metadata back into the full metadata array.
            $XMP_array_as_text = write_XMP_array_to_text($updated_dc_metadata);

            // NOTE: To verify what's in the new XMP array, this text
            // representation can be written out to log file, etc. at this
            // point.
            //debug_log($XMP_array_as_text);

            $updated_header_data = put_XMP_text($header_data, $XMP_array_as_text);

            // Output the old header data.
            debug_log(get_XMP_text($updated_header_data));
            debug_log("---------- Old above, New below this line -------");
            debug_log(get_XMP_text($header_data));

            // Load the new metadata into the image.
            $result = $xmp->put_jpeg_header_data($source_filepath, $output_filepath, $updated_header_data);
        } else if ($type1 === "video") {
            $source_directory = preg_replace('/\/*$/', '', $url) . UPLOAD_PATH . "/videos";
            $output_directory = $tmp_folder . "videos";

            $output_filepath = "$output_directory/$filename";
            $source_filepath = "$source_directory/$filename";
            $this->downloadFile($source_filepath, $output_filepath);
        } else if ($type1 === "audio") {
            $source_directory = preg_replace('/\/*$/', '', $url) . UPLOAD_PATH . "/audios";
            $output_directory = $tmp_folder . "audios";

            $output_filepath = "$output_directory/$filename";
            $source_filepath = "$source_directory/$filename";
            $this->downloadFile($source_filepath, $output_filepath);
        }
    }

    private function downloadFile($url, $path)
    {
        $newfname = $path;
        $file = @fopen($url, "rb");
        if ($file) {
            $newf = fopen($newfname, "wb");

            if ($newf)
                while (!feof($file)) {
                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                }
        }

        if ($file) {
            fclose($file);
        }

        if ($newf) {
            fclose($newf);
        }
    }


    private function createOutputDirectory($tmp_folder, $type)
    {
        // Create the output directory for the medias.
        if ($type === "image") {
            $d = $tmp_folder . "images/";
        } else if ($type === "video") {
            $d = $tmp_folder . "videos/";
        } else if ($type === "audio") {
            $d = $tmp_folder . "audios/";
        }

        if (file_exists($d)) return;

        if (mkdir($d)) {
            $this->output_directory = $d;
        } else {
            // Can we throw an exception here?
        }

        list($version, $format, $date, $system) = $this->systemInformation();

        // Include a brief note in this new directory.

        $note = <<<EOT
# This directory contains an export of media files from an installation of
# Metadata Games, a metadata tagging system from Tiltfactor Laboratory.
# For more information, see http://tiltfactor.org/mg/
#
# The export process formats the tags stored in mg for each media file, and
# appends that formatted string to the XMP dc:description field as
# embedded metadata.
#
# This Export
# ------------
# Version: metadatagames_$version
# Format: $format
# Date: $date
# System: $system
#

EOT;

        file_put_contents($this->output_directory . "/README.txt", $note);
    }

}

?>
