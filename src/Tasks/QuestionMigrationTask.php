<?php
/**
 * In RM#67766, we changed the field "Question" into "QuestionHeading"
 * in Question.php class. This task is created to help migrate "Question" data
 * to "QuestionHeading".
 *
 * @category SilverStripe_Project
 * @package SDLT
 * @author Catalyst IT <silverstripedev@catalyst.net.nz>
 * @copyright NZ Transport Agency
 * @license BSD-3
 * @link https://nzta.govt.nz
 **/
namespace NZTA\SDLT\Tasks;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\Queries\SQLUpdate;

class QuestionMigrationTask extends BuildTask {

    /**
     * Title of this task
     * @var string
     */
    public $title = 'Migrate Question data into Question Heading Task';

    /**
     * Segment of this task
     * @var string
     */
    private static $segment = 'QuestionMigrationTask';

    /**
     * Description of this task
     * @var string
     */
    public $description = 'In RM#67766, we changed the field "Question" into "QuestionHeading"
        in Question.php class. This task is created to help migrate "Question" data
        to "QuestionHeading".';

        /**
         * Default "run" method, required when implementing BuildTask
         *
         * @param HTTPRequest $request default parameter
         * @return void
         */
        public function run($request)
        {
            $update = SQLUpdate::create('"Question"');
            $update->assignSQL('"QuestionHeading"', 'Question');
            $update->execute();
            print("Migrate Question data into Question Heading Task is done.\n");
        }
}
