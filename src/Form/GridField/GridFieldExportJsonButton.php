<?php
/**
 * This file contains the "GridFieldExportJsonButton".
 *
 * @category SilverStripe_Project
 * @package SDLT
 * @author  Catalyst I.T. SilverStripe Team 2020 <silverstripedev@catalyst.net.nz>
 * @copyright NZ Transport Agency
 * @license BSD-3
 * @link https://www.catalyst.net.nz
 */

namespace NZTA\SDLT\Form\GridField;

use SilverStripe\Forms\GridField\GridField_ColumnProvider;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use Ergebnis\Json\Printer\Printer;
use SilverStripe\Core\Environment;
use NZTA\SDLT\Job\SendExportJsonDataEmailJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use SilverStripe\Security\Security;
use NZTA\SDLT\Helper\ClassSpec;

/**
 * Class GridFieldExportJsonButton
 * Json file export button
 */
class GridFieldExportJsonButton implements GridField_ActionProvider, GridField_ColumnProvider
{
    /**
     * @param GridField  $gridField  gridField
     * @param DataObject $record     record
     * @param string     $columnName columnName
     *
     * @return string
     */
    public function getTitle($gridField, $record, $columnName)
    {
        return 'Export';
    }

    /**
     * @param GridField  $gridField gridField
     * @param DataObject $record    record
     *
     * @return GridField_FormAction|null
     */
    public function getExportAction($gridField, $record)
    {
        if (!$record->canEdit()) {
            return;
        }

        return GridField_FormAction::create(
            $gridField,
            'export'.$record->ID,
            false,
            'export',
            ['Record' => $record]
        )
            ->addExtraClass('btn btn-secondary btn--no-text no-ajax font-icon-down-circled action_export')
            ->setAttribute('classNames', 'font-icon-down-circled')
            ->setDescription('Export')
            ->setAttribute('aria-label', 'Export');
    }

    /**
     * @param GridField  $gridField  gridField
     * @param DataObject $record     record
     * @param string     $columnName columnName
     *
     * @return string|null the attribles for the action
     */
    public function getExtraData($gridField, $record, $columnName)
    {
        $field = $this->getExportAction($gridField, $record);

        if (!$field) {
            return;
        }

        return $field->getAttributes();
    }

    /**
     * @param GridField  $gridField  gridField
     * @param DataObject $record     record
     * @param string     $columnName columnName
     *
     * @return string
     */
    public function getGroup($gridField, $record, $columnName)
    {
        return GridField_ActionMenuItem::DEFAULT_GROUP;
    }

    /**
     * Add a column 'Delete'
     *
     * @param GridField $gridField gridField
     * @param array     $columns   columns
     *
     * @return void
     */
    public function augmentColumns($gridField, &$columns)
    {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }

    /**
     * Return any special attributes that will be used for FormField::create_tag()
     *
     * @param GridField  $gridField  gridField
     * @param DataObject $record     record
     * @param string     $columnName columnName
     *
     * @return array
     */
    public function getColumnAttributes($gridField, $record, $columnName)
    {
        return ['class' => 'grid-field__col-compact'];
    }

    /**
     * Add the title
     *
     * @param GridField $gridField  gridField
     * @param string    $columnName columnName
     *
     * @return array
     */
    public function getColumnMetadata($gridField, $columnName)
    {
        if ($columnName === 'Actions') {
            return ['title' => ''];
        }
    }

    /**
     * Which columns are handled by this component
     *
     * @param GridField $gridField gridField
     *
     * @return array
     */
    public function getColumnsHandled($gridField)
    {
        return ['Actions'];
    }

    /**
     * @param GridField  $gridField  gridField
     * @param DataObject $record     record
     * @param string     $columnName columnName
     *
     * @return string|null the HTML for the column
     */
    public function getColumnContent($gridField, $record, $columnName)
    {
        $field = $this->getExportAction($gridField, $record);

        if (!$field) {
            return;
        }

        return $field->Field();
    }

    /**
     * Which GridField actions are this component handling
     *
     * @param GridField $gridField gridField
     *
     * @return array
     */
    public function getActions($gridField)
    {
        return ['export'];
    }

    /**
     * Handle the actions and apply any changes to the GridField
     *
     * @param GridField $gridField  gridField
     * @param string    $actionName actionName
     * @param array     $arguments  arguments
     * @param array     $data       Form data
     *
     * @throws ValidationException
     * @return HTTPRequest
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName !== 'export') {
            retutn;
        }

        Environment::increaseTimeLimitTo(60);

        $now = date("d-m-Y-H-i");
        $fileName = "export-$now.json";

        $dataClass = $gridField->getList()->dataClass;

        $jsonData = $dataClass::export_record($arguments['Record']);

        if (empty($jsonData)) {
            retutn;
        }
        // If the data is longer than 100000, create a queue job and sent the file in an email,
        // if not, download the file directly.
        if (strlen($jsonData) > 100000) {
            $user = Security::getCurrentUser();
            $queuedJobService = QueuedJobService::create();
            $queuedJobService->queueJob(
                new SendExportJsonDataEmailJob(
                    $jsonData,
                    ClassSpec::short_name($dataClass),
                    $arguments['Record']->Name,
                    $user->Email,
                    $user->Name,
                    $fileName
                ),
                date('Y-m-d H:i:s', time() + 30)
            );
            $form = $gridField->getForm();
            $form->sessionMessage(
                sprintf('Data is created and will be sent to your email address %s soon.',
                    $user->Email),
                'good'
            );
            return $gridField->redirectBack();
        } else {
            //Provides a JSON printer, allowing for flexible indentation.
            $printer = new Printer();

            $fileData = $printer->print(
                $jsonData,
                '  '
            );
            return HTTPRequest::send_file($fileData, $fileName, 'text/json');
        }
    }
}
