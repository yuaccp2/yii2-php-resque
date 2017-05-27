<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;


/**
 * 任务队列
 * @author nathan yuaccp2@163.com
 * @since php-resque
 * @date 2017-05-25
 */
class JobQueueController extends Controller{

    /**
     * update user content auth 
     * @param string $ws_id employee ID
     */	
	public function actionIndex($job = 'PHP_Job', $queue_name = 'default', $prefix = '')
	{

		$data = array(
			'time' => time(),
			'array' => array(
				'test' => 'test',
			),
		);
		$job_id = Yii::$app->task_queue->enqueue($job, $data, $queue_name, $prefix);

		$status = Yii::$app->task_queue->getJobStatus($job_id, 'desc');
		echo sprintf("Job_id:%s, status is %s", $job_id, $status);
	}


}