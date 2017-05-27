#!/bin/sh
RUN_PATH="/usr/local/php/bin/php"
MAIN_DIR="/data/www/vhosts/YiiFramework"
FILE_PATH="${MAIN_DIR}/yii_resque"
curdate=`date +%Y-%m-%d%%%H%M%S`
curmonth=`date +%Y-%m`
LOG_PATH="/data/www/logs/crontab_log"

if [ ! -d "${LOG_PATH}" ];then
    mkdir "${LOG_PATH}"
fi

LOG_FILE_PATH="${LOG_PATH}/task_job_${curmonth}.log"
MULT_LOG_FILE_PATH="${LOG_PATH}/task_job_mult_${curmonth}.log"

#flock -n /tmp/.task_job.lock -c "${RUN_PATH}  ${FILE_PATH} >> ${LOG_FILE_PATH}"

##PREFIX：前缀。在 Redis 数据库中为队列的 KEY 添加前缀，以方便多个 Worker 运行在同一个Redis 数据库中方便区分。默认为空
#export PREFIX=APP

##QUEUE: 需要执行的队列的名字
export QUEUE=default,crontabs

##REDIS_BACKEND：Redis 服务器的地址
# export REDIS_BACKEND=redis://root:123456@127.0.0.1:6379/10

##INTERVAL：在队列中循环的间隔时间，即完成一个任务后的等待时间，默认是5秒
export INTERVAL=1

##COUNT：需要创建的 Worker进程 的数量，默认是1个Worker进程。单例执行必须为1，fork是复制相同的进程
export COUNT=1

##VERBOSE：输出基本的调试信息
# export VERBOSE=1

##VVERBOSE：输出详细的调试信息
# export VVERBOSE=1

##BLOCKING:阻塞模式
# export BLOCKING=TRUE

##PIDFILE：手动指定 PID 文件的位置，适用于单 Worker 运行方式
export PIDFILE=/tmp/task_job.pid

##APP_INCLUDE：需要自动载入 PHP 文件路径，Worker 需要知道你的 Job 的位置并载入 Job

get_pid() {
    if [ -f $PIDFILE ]; then
        cat $PIDFILE
    fi
}

start() {
    local PID=$(get_pid)
    if [ ! -z $PID ]; then
        echo "php-resque($PID) is running."
        echo "You should stop it before you start."
        return
    fi

    touch $PIDFILE

    echo "Starting php-resque..."
    echo "Starting php-resque at:${curdate}" >> "${LOG_FILE_PATH}"    
    nohup $RUN_PATH $FILE_PATH >> "$LOG_FILE_PATH"  2>&1 &
}

stop() {
    local PID=$(get_pid)
    if [ -z $PID ]; then
        echo "php-resque is not running."
        return
    fi

    echo "Stopping php-resque..."
    echo "Stopping php-resque at:${curdate}" >> "${LOG_FILE_PATH}"    
    get_pid | xargs kill -9
    rm -f $PIDFILE
}

status() {
    local PID=$(get_pid)
    if [ ! -z $PID ]; then
        echo "php-resque($PID) is running."
    else
        echo "php-resque is not running."
    fi
}

multstart() {
	export QUEUE=default
	export INTERVAL=5
	export COUNT=5

    echo "Starting mult php-resque..."
    echo "Starting mult php-resque at:${curdate}" >> "${MULT_LOG_FILE_PATH}"    
    nohup $RUN_PATH $FILE_PATH >> "$MULT_LOG_FILE_PATH"  2>&1 &
}

multstop() {
    echo "Stopping mult php-resque..."
    echo "Stopping mult php-resque at:${curdate}" >> "${MULT_LOG_FILE_PATH}"    
    local PID=$(get_pid)
    local n=`ps -ef | grep resque | grep -v grep | grep -v "$PID" | awk '{print $2}' | wc -l`
    if [ 0 -eq $n ]; then
    	echo "mult php-resque is not running"
    else
	 	ps -ef | grep resque | grep -v grep | grep -v "$PID" | awk '{print $2}' | xargs kill -9 
    fi
}

case "$1" in
    start)
        start
        ;;

    stop)
        stop
        ;;

    restart)
        stop
        start
        ;;

    status)
        status
        ;;

    multstart)
        multstart
        ;;

    multstop)
        multstop
        ;;

    *)
        echo "Usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac

exit 0