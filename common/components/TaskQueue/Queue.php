<?php 
namespace common\components\TaskQueue;

use Yii\base\Component;
/**
 * 队列
 * @author nathan yuaccp2@163.com
 * @since 
 * @date 2017-05-25
 */
class Queue extends Component{
	public $hostname = '127.0.0.1';
	public $port     = '6379';
	public $username = 'root';
	public $password = '';
	public $database = '1';
	/**
	 * 初始化
	 * @return [type] [description]
	 */
	public function init()
	{
		parent::init();
		$dns = sprintf("redis://%s:%s@%s:%s/%d", 
							$this->username, 
							$this->password, 
							$this->hostname, 
							$this->port, 
							$this->database);
		\Resque::setBackend($dns);
	}
	/**
	 * 加入列队
	 * @param  string $job    任务名称 class name
	 * @param  array  $data   Job 需要的参数
	 * @param  string $queue  队列名称
	 * @param  string $prefix KEY前缀 使用场景，多个应用使用同个redis数据库
	 * @return string         Job hash id
	 */
	public function enqueue(string $job, array $data = [], $queue = 'default', $prefix = '')
	{
		$namespace = '\\common\\components\\TaskQueue\\Job\\';
		if($prefix){
	        \Resque_Redis::prefix($prefix);			
		}
		$jobId = \Resque::enqueue($queue, $namespace . $job, $data, true);
		return $jobId;	
	}

	/**
	 * 任务状态
	 * @param  [type] $job   [description]
	 * @param  string $queue [description]
	 * @return [type]        [description]
	 */
	public function getJobStatus($job, $return = 'val'){
		$result = false;
		$status = new \Resque_Job_Status($job);
		if($status->isTracking()){
			$result = $status->get();
		}

		if($return == 'desc'){
			return $this->getJobStatusDec($result);
		}

		return $result;
	}
	/**
	 * 任务状态描述
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	public function getJobStatusDec($status)
	{
		$remark = [
			\Resque_Job_Status::STATUS_WAITING  =>'waiting',
			\Resque_Job_Status::STATUS_RUNNING  =>'running',
			\Resque_Job_Status::STATUS_FAILED   =>'failed',
			\Resque_Job_Status::STATUS_COMPLETE =>'complete',
		];
		if($status === false){
			return 'not tracking';
		}
		return isset($remark[$status]) ? $remark[$status] : 'invalid';
	}
}
?>