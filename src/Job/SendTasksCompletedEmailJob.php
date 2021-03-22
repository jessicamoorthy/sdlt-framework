<?php

/**
 * This file contains the "SendTasksCompletedEmailJob" class.
 *
 * @category SilverStripe_Project
 * @package SDLT
 * @author  Catalyst I.T. SilverStripe Team 2018 <silverstripedev@catalyst.net.nz>
 * @copyright NZ Transport Agency
 * @license BSD-3
 * @link https://www.catalyst.net.nz
 */

namespace NZTA\SDLT\Job;

use SilverStripe\Control\Email\Email;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Symbiote\QueuedJobs\Services\QueuedJob;
use NZTA\SDLT\Model\QuestionnaireSubmission;
use NZTA\SDLT\Model\QuestionnaireEmail;

/**
 * A QueuedJob is specifically designed to be invoked the last task is approved
 * or completed in a submission
 */
class SendTasksCompletedEmailJob extends AbstractQueuedJob implements QueuedJob
{
    /**
     * @param QuestionnaireSubmission $questionnaireSubmission questionnaireSubmission
     */
    public function __construct($questionnaireSubmission = null)
    {
        $this->questionnaireSubmission = $questionnaireSubmission;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return sprintf(
            'Initialising tasks completed email for - %s (%d)',
            $this->questionnaireSubmission->Questionnaire()->Name,
            $this->questionnaireSubmission->ID
        );
    }

    /**
     * {@inheritDoc}
     * @return string
     */
    public function getJobType()
    {
        return QueuedJob::QUEUED;
    }

    /**
     * @return mixed void | null
     */
    public function process()
    {
        $submitterName = $this->questionnaireSubmission->SubmitterName;
        $submitterEmail = $this->questionnaireSubmission->SubmitterEmail;
        $this->sendEmail($submitterName, $submitterEmail);

        $this->isComplete = true;
    }

    /**
     * @param string $name    name
     * @param string $toEmail to Email
     *
     * @return null
     */
    public function sendEmail($name = '', $toEmail = '')
    {
        $emailDetails = QuestionnaireEmail::get()->first();
        $sub = $this->questionnaireSubmission->replaceVariable(
            $emailDetails->TasksCompletedEmailSubject
        );
        $from = $emailDetails->FromEmailAddress;

        $email = Email::create()
            ->setHTMLTemplate('Email\\EmailTemplate')
            ->setData([
                'Name' => $name,
                'Body' => $this->questionnaireSubmission->replaceVariable(
                    $emailDetails->TasksCompletedEmailBody,
                    $emailDetails->LinkPrefix
                ),
                'EmailSignature' => $emailDetails->EmailSignature
            ])
            ->setFrom($from)
            ->setTo($toEmail)
            ->setSubject($sub);

        $email->send();
    }
}
