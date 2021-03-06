<?php
namespace MailPoet\Test\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Migration;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Tasks\State;

class StateTest extends \MailPoetTest {

  /** @var State */
  private $tasks_state;

  function _before() {
    parent::_before();
    $this->tasks_state = new State();
  }

  function testItCanFetchBasicTasksData() {
    $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $this->createNewScheduledTask(Migration::TASK_TYPE);
    $data = $this->tasks_state->getLatestTasks();
    expect(count($data))->equals(2);
    expect($data[0]['id'])->equals(1);
    expect($data[0]['type'])->equals(SendingTask::TASK_TYPE);
    expect(is_int($data[0]['priority']))->true();
    expect(is_int($data[0]['updated_at']))->true();
    expect($data[0])->hasKey('scheduled_at');
    expect($data[0]['status'])->notEmpty();
    expect($data[0])->hasKey('newsletter');
  }

  function testItCanFilterTasksByType() {
    $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $this->createNewScheduledTask(Migration::TASK_TYPE);
    $data = $this->tasks_state->getLatestTasks(Migration::TASK_TYPE);
    expect(count($data))->equals(1);
    expect($data[0]['type'])->equals(Migration::TASK_TYPE);
  }

  function testItCanFilterTasksByStatus() {
    $this->createNewScheduledTask(SendingTask::TASK_TYPE, ScheduledTask::STATUS_COMPLETED);
    $this->createNewScheduledTask(SendingTask::TASK_TYPE, ScheduledTask::STATUS_PAUSED);
    $data = $this->tasks_state->getLatestTasks(null, [ScheduledTask::STATUS_COMPLETED]);
    expect(count($data))->equals(1);
    expect($data[0]['status'])->equals(ScheduledTask::STATUS_COMPLETED);
  }

  function testItFetchesNewsletterDataForSendingTasks() {
    $task = $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $newsletter = $this->createNewNewsletter();
    $this->createNewSendingQueue($task->id, $newsletter->id, 'Rendered Subject');
    $data = $this->tasks_state->getLatestTasks();
    expect($data[0]['newsletter']['newsletter_id'])->equals(1);
    expect($data[0]['newsletter']['queue_id'])->equals(1);
    expect($data[0]['newsletter']['subject'])->equals('Rendered Subject');
    expect($data[0]['newsletter']['preview_url'])->notEmpty();
  }

  function testItDoesNotFailForSendingTaskWithMissingNewsletterInconsistentData() {
    $task = $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $this->createNewSendingQueue($task->id);
    $data = $this->tasks_state->getLatestTasks();
    expect($data[0]['newsletter']['newsletter_id'])->equals(null);
    expect($data[0]['newsletter']['queue_id'])->equals(null);
    expect($data[0]['newsletter']['subject'])->equals(null);
    expect($data[0]['newsletter']['preview_url'])->equals(null);
  }

  function testItDoesNotFailForSendingTaskWithoutQueue() {
    $this->createNewScheduledTask(SendingTask::TASK_TYPE);
    $data = $this->tasks_state->getLatestTasks();
    expect(count($data))->equals(1);
  }

  function createNewScheduledTask($type, $status = ScheduledTask::STATUS_COMPLETED) {
    $task = ScheduledTask::create();
    $task->type = $type;
    $task->status = $status;
    return $task->save();
  }

  function createNewNewsletter($subject = 'Test Subject') {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->subject = $subject;
    return $newsletter->save();
  }

  function createNewSendingQueue($task_id, $newsletter_id = null, $rendered_subject = null) {
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter_id;
    $queue->task_id = $task_id;
    $queue->newsletter_rendered_subject = $rendered_subject;
    return $queue->save();
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
